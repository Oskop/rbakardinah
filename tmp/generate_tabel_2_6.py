import os
import sys
import json
import csv
import re
import time
import pymupdf

OUTPUT_DIR = os.path.join(os.getcwd(), 'data', 'permendagri_108_2016')
os.makedirs(OUTPUT_DIR, exist_ok=True)

PDF_PATH = 'raw/Permendagri 108 Tahun 2016 (1).pdf'
doc = pymupdf.open(PDF_PATH)

def clean_uraian(s):
    if not s:
        return ""
    s = re.sub(r'\s+', ' ', s).strip()
    
    replacements = [
        ('ASETLANCAR', 'ASET LANCAR'),
        ('ASE TLANCAR', 'ASET LANCAR'),
        ('ALATKEDOKTERANGIG!', 'ALAT KEDOKTERAN GIGI'),
        ('ALATKEDOKTERANGIGI', 'ALAT KEDOKTERAN GIGI'),
        ('ALAT KEDOKTERANTHT', 'ALAT KEDOKTERAN THT'),
        ('PERLENGKAPANPENDUNGKUNG', 'PERLENGKAPAN PENDUKUNG'),
        ('PERLENGKAPANPENDUKUNG', 'PERLENGKAPAN PENDUKUNG'),
        ('PENYIMPANPERLENGKAPANKANTOR', 'PENYIMPAN PERLENGKAPAN KANTOR'),
        ('PANGATURTELEKOMUNIKASI', 'PENGATUR TELEKOMUNIKASI'),
        ('PERALATANLABORATORIUM', 'PERALATAN LABORATORIUM'),
        ('OCEANARIUMjOBSERVATORIUM', 'OCEANARIUM/OBSERVATORIUM'),
        ('AKUMULASIPENYUSUTANBANGUNANPENGAMAN', 'AKUMULASI PENYUSUTAN BANGUNAN PENGAMAN'),
        ('PENANGGULANGANBENCANAALAM', 'PENANGGULANGAN BENCANA ALAM'),
        ('AKUMULASIPENYUSUTANBANGUNANPENGEMBANGAN', 'AKUMULASI PENYUSUTAN BANGUNAN PENGEMBANGAN'),
        ('DANAIR', 'DAN AIR'),
        ('lSI TABUNG', 'ISI TABUNG'),
        ('lKAN', 'IKAN'),
        ('T ERNAK', 'TERNAK'),
        ('TERNAK/BIBIT l KAN', 'TERNAK/BIBIT IKAN'),
        ('TERNAK/BIBIT lKAN', 'TERNAK/BIBIT IKAN'),
        ('PERSEDlAA N', 'PERSEDIAAN'),
        ('PERSEDlAAN', 'PERSEDIAAN'),
        ('PERSEDIAA N', 'PERSEDIAAN'),
        ('KEGlATAN', 'KEGIATAN'),
        ('PISCESjIKAN', 'PISCES/IKAN'),
        ('SUVENIRjCENDERA', 'SUVENIR/CENDERA'),
        ('ALATjBAHAN', 'ALAT/BAHAN'),
        ('DOKUMENj ADMINISTRASI', 'DOKUMEN/ADMINISTRASI'),
        ('DOKUMENjADMINISTRASI', 'DOKUMEN/ADMINISTRASI'),
        ('PERALATANTRANSLATOR', 'PERALATAN TRANSLATOR'),
        ('PERALATANPEMANCAR', 'PERALATAN PEMANCAR'),
        ('PERLENGKAPANDINAS', 'PERLENGKAPAN DINAS'),
        ('PERLENGKAPANSATW A', 'PERLENGKAPAN SATWA'),
        ('PERLENGKAPANSATWA', 'PERLENGKAPAN SATWA'),
        ('BAHANCETAK', 'BAHAN CETAK'),
        ('PIPAAIR', 'PIPA AIR'),
        ('PIPAASBES', 'PIPA ASBES'),
        ('PIPABAJA', 'PIPA BAJA'),
        ('PIPABETO N', 'PIPA BETON'),
        ('PIPABETON', 'PIPA BETON'),
        ('PIPAFIB ER', 'PIPA FIBER'),
        ('PIPAFIBER', 'PIPA FIBER'),
        ('PIPAPLAST IKP VC', 'PIPA PLASTIK PVC'),
        ('PIPAPLASTIK PVC', 'PIPA PLASTIK PVC'),
        ('PIPABEKAS', 'PIPA BEKAS'),
        ('PROSESLA INNYA', 'PROSES LAINNYA'),
        ('ASET LAIN-LAIN LAINYA', 'ASET LAIN-LAIN LAINNYA'),
        ('Aspa!', 'Aspal'),
        ('Tege!', 'Tegel'),
        ('GIG!', 'GIGI'),
        ('Kurai', 'Kursi'),
        ('KU1-siTamu', 'Kursi Tamu'),
        ('Lernari', 'Lemari'),
        ('D5t....', 'Dst....'),
    ]
    for old, new in replacements:
        s = s.replace(old, new)
        
    # Replace j when followed by uppercase letter or preceded by letters
    s = re.sub(r'([a-zA-Z]+)j([A-Z][a-zA-Z]*)', r'\1/\2', s)
    s = re.sub(r'([a-zA-Z]{3,})j\s+([A-Z])', r'\1/ \2', s)
    
    # Replace exclamation mark at end of word with 'l'
    s = re.sub(r'([a-zA-Z])!(?=\s|$|\))', r'\1l', s)
    
    # Clean OCR noise symbols like ~, `, ^, etc.
    s = re.sub(r'[\~\\`\^"\'\|\.]{2,}', '', s)
    s = re.sub(r'\s+', ' ', s).strip()
    return s

def normalize_code_word(w_str):
    w_clean = w_str.strip("'\"`.,;:-")
    if w_clean == 'lD4': return '104'
    if w_clean == 'lD7': return '107'
    if w_clean == 'OlD': return '010'
    return w_clean

print("Extracting Tabel 2.6 (pages 95 to 662)...")
start_time = time.time()

rows = []
for p in range(94, 662): # index 94 = Page 95, index 661 = Page 662
    page = doc[p]
    min_y = 340 if p == 94 else 220
    words = [w for w in page.get_text("words") if min_y < w[1] < 840 and "peraturan.go.id" not in w[4]]
    
    code_words = []
    for w in words:
        if w[0] < 325:
            norm = normalize_code_word(w[4])
            if norm.isdigit():
                code_words.append((w[0], w[1], w[2], w[3], norm))
                
    if not code_words:
        continue
    code_words.sort(key=lambda w: (w[1], w[0]))
    
    # Cluster code words into visual row anchors
    clusters = []
    for w in code_words:
        if not clusters or abs(w[1] - clusters[-1]["y_center"]) > 6.0:
            clusters.append({"words": [w], "y_center": w[1]})
        else:
            clusters[-1]["words"].append(w)
            clusters[-1]["y_center"] = sum(cw[1] for cw in clusters[-1]["words"]) / len(clusters[-1]["words"])
            
    boundaries = []
    for i in range(len(clusters)):
        top = (clusters[i-1]["y_center"] + clusters[i]["y_center"]) / 2 if i > 0 else clusters[i]["y_center"] - 10
        bottom = (clusters[i]["y_center"] + clusters[i+1]["y_center"]) / 2 if i < len(clusters) - 1 else clusters[i]["y_center"] + 15
        boundaries.append((top, bottom))
        
    uraian_words = [w for w in words if w[0] >= 325]
    
    for i, c in enumerate(clusters):
        top, bottom = boundaries[i]
        cwords = c["words"]
        cwords.sort(key=lambda w: w[0])
        
        # Col ranges:
        # Akun: < 145
        # Kelompok: 145..172
        # Jenis: 172..195
        # Objek: 195..225
        # Rincian: 225..255
        # Sub Rincian: 255..290
        # Sub-Sub Rincian: 290..325
        akun = "".join(w[4] for w in cwords if w[0] < 145)
        kel = "".join(w[4] for w in cwords if 145 <= w[0] < 172)
        jen = "".join(w[4] for w in cwords if 172 <= w[0] < 195)
        obj = "".join(w[4] for w in cwords if 195 <= w[0] < 225)
        rin = "".join(w[4] for w in cwords if 225 <= w[0] < 255)
        sub = "".join(w[4] for w in cwords if 255 <= w[0] < 290)
        subsub = "".join(w[4] for w in cwords if 290 <= w[0] < 325)
        
        # In case Akun 1 or Kelompok 3 was faint / omitted on Page 139
        if not akun and (kel or jen):
            akun = "1"
            if not kel:
                kel = "3"
                
        row_uwords = [w for w in uraian_words if top <= w[1] < bottom]
        row_uwords.sort(key=lambda w: (round(w[1] / 3), w[0]))
        uraian = clean_uraian(" ".join(w[4] for w in row_uwords))
        
        parts = [p for p in [akun, kel, jen, obj, rin, sub, subsub] if p]
        code = ".".join(parts)
        level = len(parts)
        
        rows.append({
            "kode": code,
            "level": level,
            "kode_akun": akun or None,
            "kode_kelompok": kel or None,
            "kode_jenis": jen or None,
            "kode_objek": obj or None,
            "kode_rincian_objek": rin or None,
            "kode_sub_rincian_objek": sub or None,
            "kode_sub_sub_rincian_objek": subsub or None,
            "uraian": uraian
        })

elapsed = time.time() - start_time
print(f"Extracted {len(rows)} rows in {elapsed:.2f} seconds.")

# 1. Save JSON
json_path = os.path.join(OUTPUT_DIR, 'tabel_2_6_sub_sub_rincian_objek.json')
with open(json_path, 'w', encoding='utf-8') as f:
    json.dump(rows, f, ensure_ascii=False, indent=2)
print(f"Saved JSON: {json_path} (size: {os.path.getsize(json_path) / 1024:.1f} KB)")

# 2. Save CSV
csv_path = os.path.join(OUTPUT_DIR, 'tabel_2_6_sub_sub_rincian_objek.csv')
fieldnames = [
    'kode', 'level', 'kode_akun', 'kode_kelompok', 'kode_jenis',
    'kode_objek', 'kode_rincian_objek', 'kode_sub_rincian_objek',
    'kode_sub_sub_rincian_objek', 'uraian'
]
with open(csv_path, 'w', encoding='utf-8', newline='') as f:
    writer = csv.DictWriter(f, fieldnames=fieldnames)
    writer.writeheader()
    for r in rows:
        writer.writerow(r)
print(f"Saved CSV: {csv_path} (size: {os.path.getsize(csv_path) / 1024:.1f} KB)")

# 3. Save SQL Script
sql_path = os.path.join(OUTPUT_DIR, 'permendagri_108_tabel_2_6.sql')
with open(sql_path, 'w', encoding='utf-8') as f:
    f.write("-- Script DDL dan Data Import Permendagri No. 108 Tahun 2016\n")
    f.write("-- Tabel 2.6: Penggolongan dan Kodefikasi BMD Sampai Dengan Sub-Sub Rincian Objek\n")
    f.write("-- Target Database: MySQL 8.x / 8.4.x\n\n")
    f.write("SET FOREIGN_KEY_CHECKS = 0;\n")
    f.write("SET NAMES utf8mb4;\n\n")
    f.write("DROP TABLE IF EXISTS `permendagri_108_tabel_2_6`;\n")
    f.write("""CREATE TABLE `permendagri_108_tabel_2_6` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(50) NOT NULL,
  `level` TINYINT UNSIGNED NOT NULL,
  `kode_akun` VARCHAR(10) DEFAULT NULL,
  `kode_kelompok` VARCHAR(10) DEFAULT NULL,
  `kode_jenis` VARCHAR(10) DEFAULT NULL,
  `kode_objek` VARCHAR(10) DEFAULT NULL,
  `kode_rincian_objek` VARCHAR(10) DEFAULT NULL,
  `kode_sub_rincian_objek` VARCHAR(10) DEFAULT NULL,
  `kode_sub_sub_rincian_objek` VARCHAR(10) DEFAULT NULL,
  `uraian` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_kode` (`kode`),
  INDEX `idx_level` (`level`),
  INDEX `idx_kode_akun` (`kode_akun`),
  INDEX `idx_kode_kelompok` (`kode_kelompok`),
  INDEX `idx_kode_jenis` (`kode_jenis`),
  INDEX `idx_kode_objek` (`kode_objek`),
  INDEX `idx_kode_rincian` (`kode_rincian_objek`),
  INDEX `idx_kode_sub_rincian` (`kode_sub_rincian_objek`),
  INDEX `idx_kode_sub_sub_rincian` (`kode_sub_sub_rincian_objek`),
  FULLTEXT INDEX `ft_uraian` (`uraian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n""")

    # Multi-row batch inserts (batch size 500)
    batch_size = 500
    for i in range(0, len(rows), batch_size):
        batch = rows[i:i + batch_size]
        values_list = []
        for r in batch:
            val_akun = f"'{r['kode_akun']}'" if r['kode_akun'] else "NULL"
            val_kel = f"'{r['kode_kelompok']}'" if r['kode_kelompok'] else "NULL"
            val_jen = f"'{r['kode_jenis']}'" if r['kode_jenis'] else "NULL"
            val_obj = f"'{r['kode_objek']}'" if r['kode_objek'] else "NULL"
            val_rin = f"'{r['kode_rincian_objek']}'" if r['kode_rincian_objek'] else "NULL"
            val_sub = f"'{r['kode_sub_rincian_objek']}'" if r['kode_sub_rincian_objek'] else "NULL"
            val_subsub = f"'{r['kode_sub_sub_rincian_objek']}'" if r['kode_sub_sub_rincian_objek'] else "NULL"
            val_ur = r['uraian'].replace("\\", "\\\\").replace("'", "''")
            values_list.append(
                f"('{r['kode']}', {r['level']}, {val_akun}, {val_kel}, {val_jen}, {val_obj}, {val_rin}, {val_sub}, {val_subsub}, '{val_ur}')"
            )
        f.write(
            "INSERT INTO `permendagri_108_tabel_2_6` (`kode`, `level`, `kode_akun`, `kode_kelompok`, `kode_jenis`, `kode_objek`, `kode_rincian_objek`, `kode_sub_rincian_objek`, `kode_sub_sub_rincian_objek`, `uraian`) VALUES\n"
            + ",\n".join(values_list) + ";\n\n"
        )

    f.write("SET FOREIGN_KEY_CHECKS = 1;\n")
    f.write("-- Selesai import Tabel 2.6\n")

print(f"Saved SQL: {sql_path} (size: {os.path.getsize(sql_path) / 1024:.1f} KB)")
