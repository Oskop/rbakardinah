import os
import sys
import json
import csv
import re
import pymupdf

OUTPUT_DIR = os.path.join(os.getcwd(), 'data', 'permendagri_108_2016')
os.makedirs(OUTPUT_DIR, exist_ok=True)

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

def clean_uraian(s):
    if not s:
        return ""
    s = re.sub(r'\s+', ' ', s).strip()
    replacements = [
        ('ASETLANCAR', 'ASET LANCAR'),
        ('ASE TLANCAR', 'ASET LANCAR'),
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
    ]
    for old, new in replacements:
        s = s.replace(old, new)
        
    s = re.sub(r'([A-Za-z]+)j([A-Za-z]+)', r'\1/\2', s)
    s = re.sub(r'[\~\\`\^"\'\|\.]{2,}', '', s)
    s = re.sub(r'\s+', ' ', s).strip()
    return s

def extract_tabel_2_2():
    page = doc[17] # Page 18
    words = [w for w in page.get_text('words') if 640 < w[1] < 850]
    words.sort(key=lambda w: (w[1], w[0]))
    
    lines = []
    for w in words:
        if not lines or abs(w[1] - lines[-1][0][1]) > 3.5:
            lines.append([w])
        else:
            lines[-1].append(w)
            
    rows = []
    for l in lines:
        l.sort(key=lambda w: w[0])
        akun = ''.join(w[4] for w in l if w[0] < 95 and w[4].isdigit())
        kelompok = ''.join(w[4] for w in l if 95 <= w[0] < 115 and w[4].isdigit())
        jenis = ''.join(w[4] for w in l if 115 <= w[0] < 145 and w[4].isdigit())
        uraian = clean_uraian(' '.join(w[4] for w in l if w[0] >= 145))
        
        parts = [p for p in [akun, kelompok, jenis] if p]
        code = '.'.join(parts)
        level = len(parts)
        rows.append({
            'kode': code,
            'level': level,
            'kode_akun': akun,
            'kode_kelompok': kelompok,
            'kode_jenis': jenis,
            'uraian': uraian
        })
    return rows

def extract_tabel_2_3():
    rows = []
    for p in range(18, 21): # Pages 19, 20, 21
        page = doc[p]
        words = [w for w in page.get_text('words') if 200 < w[1] < 850]
        code_words = [w for w in words if w[0] < 245 and w[4].isdigit()]
        code_words.sort(key=lambda w: (w[1], w[0]))
        
        clusters = []
        for w in code_words:
            if not clusters or abs(w[1] - clusters[-1]['y_center']) > 5.0:
                clusters.append({'words': [w], 'y_center': w[1]})
            else:
                clusters[-1]['words'].append(w)
                clusters[-1]['y_center'] = sum(cw[1] for cw in clusters[-1]['words']) / len(clusters[-1]['words'])
                
        boundaries = []
        for i in range(len(clusters)):
            top = (clusters[i-1]['y_center'] + clusters[i]['y_center']) / 2 if i > 0 else clusters[i]['y_center'] - 12
            bottom = (clusters[i]['y_center'] + clusters[i+1]['y_center']) / 2 if i < len(clusters) - 1 else clusters[i]['y_center'] + 15
            boundaries.append((top, bottom))
            
        uraian_words = [w for w in words if w[0] >= 245 and 'peraturan.go.id' not in w[4]]
        
        for i, c in enumerate(clusters):
            top, bottom = boundaries[i]
            cwords = c['words']
            akun = ''.join(w[4] for w in cwords if w[0] < 110)
            kelompok = ''.join(w[4] for w in cwords if 110 <= w[0] < 155)
            jenis = ''.join(w[4] for w in cwords if 155 <= w[0] < 200)
            objek = ''.join(w[4] for w in cwords if 200 <= w[0] < 245)
            
            row_uwords = [w for w in uraian_words if top <= w[1] < bottom]
            row_uwords.sort(key=lambda w: (round(w[1] / 3), w[0]))
            uraian = clean_uraian(' '.join(w[4] for w in row_uwords))
            
            parts = [p for p in [akun, kelompok, jenis, objek] if p]
            code = '.'.join(parts)
            level = len(parts)
            rows.append({
                'kode': code,
                'level': level,
                'kode_akun': akun,
                'kode_kelompok': kelompok,
                'kode_jenis': jenis,
                'kode_objek': objek,
                'uraian': uraian
            })
    return rows

def extract_tabel_2_4():
    rows = []
    for p in range(21, 33): # Pages 22 to 33
        page = doc[p]
        words = [w for w in page.get_text('words') if 200 < w[1] < 850]
        min_y = 330 if p == 21 else 220
        words = [w for w in words if w[1] > min_y and 'peraturan.go.id' not in w[4]]
            
        code_words = [w for w in words if w[0] < 300 and w[4].isdigit()]
        code_words.sort(key=lambda w: (w[1], w[0]))
        
        clusters = []
        for w in code_words:
            if not clusters or abs(w[1] - clusters[-1]['y_center']) > 5.5:
                clusters.append({'words': [w], 'y_center': w[1]})
            else:
                clusters[-1]['words'].append(w)
                clusters[-1]['y_center'] = sum(cw[1] for cw in clusters[-1]['words']) / len(clusters[-1]['words'])
                
        boundaries = []
        for i in range(len(clusters)):
            top = (clusters[i-1]['y_center'] + clusters[i]['y_center']) / 2 if i > 0 else clusters[i]['y_center'] - 12
            bottom = (clusters[i]['y_center'] + clusters[i+1]['y_center']) / 2 if i < len(clusters) - 1 else clusters[i]['y_center'] + 15
            boundaries.append((top, bottom))
            
        uraian_words = [w for w in words if w[0] >= 300]
        
        for i, c in enumerate(clusters):
            top, bottom = boundaries[i]
            cwords = c['words']
            akun = ''.join(w[4] for w in cwords if w[0] < 115)
            kelompok = ''.join(w[4] for w in cwords if 115 <= w[0] < 165)
            jenis = ''.join(w[4] for w in cwords if 165 <= w[0] < 210)
            objek = ''.join(w[4] for w in cwords if 210 <= w[0] < 255)
            rincian = ''.join(w[4] for w in cwords if 255 <= w[0] < 300)
            
            row_uwords = [w for w in uraian_words if top <= w[1] < bottom]
            row_uwords.sort(key=lambda w: (round(w[1] / 3), w[0]))
            uraian = clean_uraian(' '.join(w[4] for w in row_uwords))
            
            parts = [p for p in [akun, kelompok, jenis, objek, rincian] if p]
            code = '.'.join(parts)
            level = len(parts)
            rows.append({
                'kode': code,
                'level': level,
                'kode_akun': akun,
                'kode_kelompok': kelompok,
                'kode_jenis': jenis,
                'kode_objek': objek,
                'kode_rincian_objek': rincian,
                'uraian': uraian
            })
    return rows

def extract_tabel_2_5():
    rows = []
    for p in range(33, 94): # Pages 34 to 94
        page = doc[p]
        words = page.get_text('words')
        min_y = 420 if p == 33 else 240
        words = [w for w in words if min_y < w[1] < 840 and 'peraturan.go.id' not in w[4]]
        
        code_words = [w for w in words if w[0] < 290 and w[4].isdigit()]
        code_words.sort(key=lambda w: (w[1], w[0]))
        
        clusters = []
        for w in code_words:
            if not clusters or abs(w[1] - clusters[-1]['y_center']) > 7.0:
                clusters.append({'words': [w], 'y_center': w[1]})
            else:
                clusters[-1]['words'].append(w)
                clusters[-1]['y_center'] = sum(cw[1] for cw in clusters[-1]['words']) / len(clusters[-1]['words'])
                
        boundaries = []
        for i in range(len(clusters)):
            top = (clusters[i-1]['y_center'] + clusters[i]['y_center']) / 2 if i > 0 else clusters[i]['y_center'] - 12
            bottom = (clusters[i]['y_center'] + clusters[i+1]['y_center']) / 2 if i < len(clusters) - 1 else clusters[i]['y_center'] + 15
            boundaries.append((top, bottom))
            
        uraian_words = [w for w in words if w[0] >= 290]
        
        for i, c in enumerate(clusters):
            top, bottom = boundaries[i]
            cwords = c['words']
            
            akun = ''.join(w[4] for w in cwords if w[0] < 140)
            kelompok = ''.join(w[4] for w in cwords if 140 <= w[0] < 170)
            jenis = ''.join(w[4] for w in cwords if 170 <= w[0] < 195)
            objek = ''.join(w[4] for w in cwords if 195 <= w[0] < 225)
            rincian = ''.join(w[4] for w in cwords if 225 <= w[0] < 260)
            sub_rincian = ''.join(w[4] for w in cwords if 260 <= w[0] < 290)
            
            row_uwords = [w for w in uraian_words if top <= w[1] < bottom]
            row_uwords.sort(key=lambda w: (round(w[1] / 3), w[0]))
            uraian = clean_uraian(' '.join(w[4] for w in row_uwords))
            
            parts = [p for p in [akun, kelompok, jenis, objek, rincian, sub_rincian] if p]
            code = '.'.join(parts)
            level = len(parts)
            
            # Budidaya OCR replacements
            if code == '1.3.5.04.03.01' and not uraian.startswith('MOLLUSCA'):
                uraian = 'MOLLUSCA BUDIDAYA (KERANG, TIRAM, CUMI-CUMI, GURITA, SIPUT, DAN SEBANGSANYA)'
            elif code == '1.3.5.04.07.01' and not uraian.startswith('REPTILIA'):
                uraian = 'REPTILIA BUDIDAYA (BUAYA, PENYU, KURA-KURA, BIAWAK, ULAR AIR, DAN SEBANGSANYA)'
            elif code == '1.3.5.04.08.01' and not uraian.startswith('MAMMALIA'):
                uraian = 'MAMMALIA BUDIDAYA (PAUS, LUMBA-LUMBA, PESUT, DUYUNG, DAN SEBANGSANYA)'
            elif code == '1.3.5.04.09.01' and not uraian.startswith('ALGAE'):
                uraian = 'ALGAE BUDIDAYA (RUMPUT LAUT DAN TUMBUH-TUMBUHAN LAIN YANG HIDUP DI DALAM AIR/BIOTA LAINNYA)'
                
            rows.append({
                'kode': code,
                'level': level,
                'kode_akun': akun,
                'kode_kelompok': kelompok,
                'kode_jenis': jenis,
                'kode_objek': objek,
                'kode_rincian_objek': rincian,
                'kode_sub_rincian_objek': sub_rincian,
                'uraian': uraian
            })
    return rows

print("Extracting all tables...")
t22 = extract_tabel_2_2()
t23 = extract_tabel_2_3()
t24 = extract_tabel_2_4()
t25 = extract_tabel_2_5()

# 1. Export JSON
with open(os.path.join(OUTPUT_DIR, 'tabel_2_2_jenis.json'), 'w', encoding='utf-8') as f:
    json.dump(t22, f, ensure_ascii=False, indent=2)

with open(os.path.join(OUTPUT_DIR, 'tabel_2_3_objek.json'), 'w', encoding='utf-8') as f:
    json.dump(t23, f, ensure_ascii=False, indent=2)

with open(os.path.join(OUTPUT_DIR, 'tabel_2_4_rincian_objek.json'), 'w', encoding='utf-8') as f:
    json.dump(t24, f, ensure_ascii=False, indent=2)

with open(os.path.join(OUTPUT_DIR, 'tabel_2_5_sub_rincian_objek.json'), 'w', encoding='utf-8') as f:
    json.dump(t25, f, ensure_ascii=False, indent=2)

with open(os.path.join(OUTPUT_DIR, 'permendagri_108_master_level1_6.json'), 'w', encoding='utf-8') as f:
    json.dump(t25, f, ensure_ascii=False, indent=2)

# 2. Export CSV
def export_csv(filename, data, fieldnames):
    with open(os.path.join(OUTPUT_DIR, filename), 'w', encoding='utf-8', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        for r in data:
            writer.writerow(r)

export_csv('tabel_2_2_jenis.csv', t22, ['kode', 'level', 'kode_akun', 'kode_kelompok', 'kode_jenis', 'uraian'])
export_csv('tabel_2_3_objek.csv', t23, ['kode', 'level', 'kode_akun', 'kode_kelompok', 'kode_jenis', 'kode_objek', 'uraian'])
export_csv('tabel_2_4_rincian_objek.csv', t24, ['kode', 'level', 'kode_akun', 'kode_kelompok', 'kode_jenis', 'kode_objek', 'kode_rincian_objek', 'uraian'])
export_csv('tabel_2_5_sub_rincian_objek.csv', t25, ['kode', 'level', 'kode_akun', 'kode_kelompok', 'kode_jenis', 'kode_objek', 'kode_rincian_objek', 'kode_sub_rincian_objek', 'uraian'])
export_csv('permendagri_108_master_level1_6.csv', t25, ['kode', 'level', 'kode_akun', 'kode_kelompok', 'kode_jenis', 'kode_objek', 'kode_rincian_objek', 'kode_sub_rincian_objek', 'uraian'])

# 3. Generate SQL Script
sql_lines = [
    "-- Script DDL dan Data Import Permendagri No. 108 Tahun 2016",
    "-- Tabel 2.2, 2.3, 2.4, 2.5 dan Master Level 1-6",
    "-- Target Database: MySQL 8.x / 8.4.x",
    "SET FOREIGN_KEY_CHECKS = 0;",
    "SET NAMES utf8mb4;",
    "",
]

# Table 2.2
sql_lines.extend([
    "DROP TABLE IF EXISTS `permendagri_108_tabel_2_2`;",
    """CREATE TABLE `permendagri_108_tabel_2_2` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(50) NOT NULL UNIQUE,
  `level` TINYINT UNSIGNED NOT NULL,
  `kode_akun` VARCHAR(10) DEFAULT NULL,
  `kode_kelompok` VARCHAR(10) DEFAULT NULL,
  `kode_jenis` VARCHAR(10) DEFAULT NULL,
  `uraian` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_level` (`level`),
  INDEX `idx_kode_akun` (`kode_akun`),
  INDEX `idx_kode_kelompok` (`kode_kelompok`),
  INDEX `idx_kode_jenis` (`kode_jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;""",
    ""
])
for r in t22:
    val_akun = f"'{r['kode_akun']}'" if r['kode_akun'] else "NULL"
    val_kel = f"'{r['kode_kelompok']}'" if r['kode_kelompok'] else "NULL"
    val_jen = f"'{r['kode_jenis']}'" if r['kode_jenis'] else "NULL"
    val_ur = r['uraian'].replace("'", "''")
    sql_lines.append(f"INSERT INTO `permendagri_108_tabel_2_2` (`kode`, `level`, `kode_akun`, `kode_kelompok`, `kode_jenis`, `uraian`) VALUES ('{r['kode']}', {r['level']}, {val_akun}, {val_kel}, {val_jen}, '{val_ur}');")

# Table 2.3
sql_lines.extend([
    "",
    "DROP TABLE IF EXISTS `permendagri_108_tabel_2_3`;",
    """CREATE TABLE `permendagri_108_tabel_2_3` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(50) NOT NULL UNIQUE,
  `level` TINYINT UNSIGNED NOT NULL,
  `kode_akun` VARCHAR(10) DEFAULT NULL,
  `kode_kelompok` VARCHAR(10) DEFAULT NULL,
  `kode_jenis` VARCHAR(10) DEFAULT NULL,
  `kode_objek` VARCHAR(10) DEFAULT NULL,
  `uraian` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_level` (`level`),
  INDEX `idx_kode_objek` (`kode_objek`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;""",
    ""
])
for r in t23:
    val_akun = f"'{r['kode_akun']}'" if r['kode_akun'] else "NULL"
    val_kel = f"'{r['kode_kelompok']}'" if r['kode_kelompok'] else "NULL"
    val_jen = f"'{r['kode_jenis']}'" if r['kode_jenis'] else "NULL"
    val_obj = f"'{r['kode_objek']}'" if r['kode_objek'] else "NULL"
    val_ur = r['uraian'].replace("'", "''")
    sql_lines.append(f"INSERT INTO `permendagri_108_tabel_2_3` (`kode`, `level`, `kode_akun`, `kode_kelompok`, `kode_jenis`, `kode_objek`, `uraian`) VALUES ('{r['kode']}', {r['level']}, {val_akun}, {val_kel}, {val_jen}, {val_obj}, '{val_ur}');")

# Table 2.4
sql_lines.extend([
    "",
    "DROP TABLE IF EXISTS `permendagri_108_tabel_2_4`;",
    """CREATE TABLE `permendagri_108_tabel_2_4` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(50) NOT NULL UNIQUE,
  `level` TINYINT UNSIGNED NOT NULL,
  `kode_akun` VARCHAR(10) DEFAULT NULL,
  `kode_kelompok` VARCHAR(10) DEFAULT NULL,
  `kode_jenis` VARCHAR(10) DEFAULT NULL,
  `kode_objek` VARCHAR(10) DEFAULT NULL,
  `kode_rincian_objek` VARCHAR(10) DEFAULT NULL,
  `uraian` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_level` (`level`),
  INDEX `idx_kode_objek` (`kode_objek`),
  INDEX `idx_kode_rincian` (`kode_rincian_objek`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;""",
    ""
])
for r in t24:
    val_akun = f"'{r['kode_akun']}'" if r['kode_akun'] else "NULL"
    val_kel = f"'{r['kode_kelompok']}'" if r['kode_kelompok'] else "NULL"
    val_jen = f"'{r['kode_jenis']}'" if r['kode_jenis'] else "NULL"
    val_obj = f"'{r['kode_objek']}'" if r['kode_objek'] else "NULL"
    val_rin = f"'{r['kode_rincian_objek']}'" if r['kode_rincian_objek'] else "NULL"
    val_ur = r['uraian'].replace("'", "''")
    sql_lines.append(f"INSERT INTO `permendagri_108_tabel_2_4` (`kode`, `level`, `kode_akun`, `kode_kelompok`, `kode_jenis`, `kode_objek`, `kode_rincian_objek`, `uraian`) VALUES ('{r['kode']}', {r['level']}, {val_akun}, {val_kel}, {val_jen}, {val_obj}, {val_rin}, '{val_ur}');")

# Table 2.5
sql_lines.extend([
    "",
    "DROP TABLE IF EXISTS `permendagri_108_tabel_2_5`;",
    """CREATE TABLE `permendagri_108_tabel_2_5` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(50) NOT NULL UNIQUE,
  `level` TINYINT UNSIGNED NOT NULL,
  `kode_akun` VARCHAR(10) DEFAULT NULL,
  `kode_kelompok` VARCHAR(10) DEFAULT NULL,
  `kode_jenis` VARCHAR(10) DEFAULT NULL,
  `kode_objek` VARCHAR(10) DEFAULT NULL,
  `kode_rincian_objek` VARCHAR(10) DEFAULT NULL,
  `kode_sub_rincian_objek` VARCHAR(10) DEFAULT NULL,
  `uraian` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_level` (`level`),
  INDEX `idx_kode_objek` (`kode_objek`),
  INDEX `idx_kode_rincian` (`kode_rincian_objek`),
  INDEX `idx_kode_sub_rincian` (`kode_sub_rincian_objek`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;""",
    ""
])
for r in t25:
    val_akun = f"'{r['kode_akun']}'" if r['kode_akun'] else "NULL"
    val_kel = f"'{r['kode_kelompok']}'" if r['kode_kelompok'] else "NULL"
    val_jen = f"'{r['kode_jenis']}'" if r['kode_jenis'] else "NULL"
    val_obj = f"'{r['kode_objek']}'" if r['kode_objek'] else "NULL"
    val_rin = f"'{r['kode_rincian_objek']}'" if r['kode_rincian_objek'] else "NULL"
    val_sub = f"'{r['kode_sub_rincian_objek']}'" if r['kode_sub_rincian_objek'] else "NULL"
    val_ur = r['uraian'].replace("'", "''")
    sql_lines.append(f"INSERT INTO `permendagri_108_tabel_2_5` (`kode`, `level`, `kode_akun`, `kode_kelompok`, `kode_jenis`, `kode_objek`, `kode_rincian_objek`, `kode_sub_rincian_objek`, `uraian`) VALUES ('{r['kode']}', {r['level']}, {val_akun}, {val_kel}, {val_jen}, {val_obj}, {val_rin}, {val_sub}, '{val_ur}');")

# Unified Master table
sql_lines.extend([
    "",
    "DROP TABLE IF EXISTS `permendagri_108_master_level1_6`;",
    """CREATE TABLE `permendagri_108_master_level1_6` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(50) NOT NULL UNIQUE,
  `parent_kode` VARCHAR(50) DEFAULT NULL,
  `level` TINYINT UNSIGNED NOT NULL,
  `kode_akun` VARCHAR(10) DEFAULT NULL,
  `kode_kelompok` VARCHAR(10) DEFAULT NULL,
  `kode_jenis` VARCHAR(10) DEFAULT NULL,
  `kode_objek` VARCHAR(10) DEFAULT NULL,
  `kode_rincian_objek` VARCHAR(10) DEFAULT NULL,
  `kode_sub_rincian_objek` VARCHAR(10) DEFAULT NULL,
  `uraian` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_parent_kode` (`parent_kode`),
  INDEX `idx_level` (`level`),
  INDEX `idx_kode_objek` (`kode_objek`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;""",
    ""
])
for r in t25:
    parts = r['kode'].split('.')
    parent_kode = '.'.join(parts[:-1]) if len(parts) > 1 else None
    val_parent = f"'{parent_kode}'" if parent_kode else "NULL"
    val_akun = f"'{r['kode_akun']}'" if r['kode_akun'] else "NULL"
    val_kel = f"'{r['kode_kelompok']}'" if r['kode_kelompok'] else "NULL"
    val_jen = f"'{r['kode_jenis']}'" if r['kode_jenis'] else "NULL"
    val_obj = f"'{r['kode_objek']}'" if r['kode_objek'] else "NULL"
    val_rin = f"'{r['kode_rincian_objek']}'" if r['kode_rincian_objek'] else "NULL"
    val_sub = f"'{r['kode_sub_rincian_objek']}'" if r['kode_sub_rincian_objek'] else "NULL"
    val_ur = r['uraian'].replace("'", "''")
    sql_lines.append(f"INSERT INTO `permendagri_108_master_level1_6` (`kode`, `parent_kode`, `level`, `kode_akun`, `kode_kelompok`, `kode_jenis`, `kode_objek`, `kode_rincian_objek`, `kode_sub_rincian_objek`, `uraian`) VALUES ('{r['kode']}', {val_parent}, {r['level']}, {val_akun}, {val_kel}, {val_jen}, {val_obj}, {val_rin}, {val_sub}, '{val_ur}');")

sql_lines.extend([
    "",
    "SET FOREIGN_KEY_CHECKS = 1;",
    "-- Selesai import"
])

sql_file = os.path.join(OUTPUT_DIR, 'permendagri_108_tabel_2_2_sd_2_5.sql')
with open(sql_file, 'w', encoding='utf-8') as f:
    f.write('\n'.join(sql_lines))

print(f"Generated SQL file successfully: {sql_file}")
