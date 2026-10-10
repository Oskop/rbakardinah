import pymupdf
import re

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

def clean_text(s):
    s = re.sub(r'\s+', ' ', s).strip()
    return s

def extract_tabel_2_5():
    rows = []
    # Pages 34 to 94 (0-indexed 33 to 93)
    for p in range(33, 94):
        page = doc[p]
        words = page.get_text('words')
        
        # On page 34, table starts at y > 420
        # On other pages, table starts at y > 240
        min_y = 420 if p == 33 else 240
        words = [w for w in words if min_y < w[1] < 840 and 'peraturan.go.id' not in w[4]]
        
        # Filter code words: digits at x < 290
        code_words = [w for w in words if w[0] < 290 and w[4].isdigit()]
        code_words.sort(key=lambda w: (w[1], w[0]))
        
        # Cluster code words into rows
        # Use y_thresh = 7.0 (to handle slight vertical jitter as seen on page 85)
        clusters = []
        for w in code_words:
            if not clusters or abs(w[1] - clusters[-1]['y_center']) > 7.0:
                clusters.append({'words': [w], 'y_center': w[1]})
            else:
                clusters[-1]['words'].append(w)
                clusters[-1]['y_center'] = sum(cw[1] for cw in clusters[-1]['words']) / len(clusters[-1]['words'])
                
        # Calculate boundaries
        boundaries = []
        for i in range(len(clusters)):
            top = (clusters[i-1]['y_center'] + clusters[i]['y_center']) / 2 if i > 0 else clusters[i]['y_center'] - 12
            bottom = (clusters[i]['y_center'] + clusters[i+1]['y_center']) / 2 if i < len(clusters) - 1 else clusters[i]['y_center'] + 15
            boundaries.append((top, bottom))
            
        uraian_words = [w for w in words if w[0] >= 290]
        
        for i, c in enumerate(clusters):
            top, bottom = boundaries[i]
            cwords = c['words']
            
            # Akun < 140
            # Kelompok 140..170
            # Jenis 170..195
            # Objek 195..225
            # Rincian 225..260
            # Sub Rincian 260..290
            akun = ''.join(w[4] for w in cwords if w[0] < 140)
            kelompok = ''.join(w[4] for w in cwords if 140 <= w[0] < 170)
            jenis = ''.join(w[4] for w in cwords if 170 <= w[0] < 195)
            objek = ''.join(w[4] for w in cwords if 195 <= w[0] < 225)
            rincian = ''.join(w[4] for w in cwords if 225 <= w[0] < 260)
            sub_rincian = ''.join(w[4] for w in cwords if 260 <= w[0] < 290)
            
            row_uwords = [w for w in uraian_words if top <= w[1] < bottom]
            row_uwords.sort(key=lambda w: (round(w[1] / 3), w[0]))
            uraian = clean_text(' '.join(w[4] for w in row_uwords))
            
            # Clean OCR noise if present
            # Remove isolated symbols like ,,~, ~v~ etc.
            uraian = re.sub(r'[\~\\`\^"\'\|\.]{2,}', '', uraian)
            uraian = clean_text(uraian)
            
            parts = [p for p in [akun, kelompok, jenis, objek, rincian, sub_rincian] if p]
            code = '.'.join(parts)
            level = len(parts)
            
            # Known OCR corrections on page 85 where OCR corrupted line 1 of Budidaya items:
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
                'uraian': uraian,
                'page': p + 1
            })
    return rows

t25 = extract_tabel_2_5()
print(f"Extracted Tabel 2.5: {len(t25)} rows")
empty_25 = [r for r in t25 if not r['uraian']]
print(f"Empty uraian in 2.5: {len(empty_25)}")
if empty_25:
    for e in empty_25:
        print(f"  Empty on page {e['page']}: {e['kode']}")
