import pymupdf
from collections import Counter
import re

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# OCR normalization for code words in digit columns
def normalize_code_word(w_str):
    w_clean = w_str.strip("'\"`.,;:-")
    # check known OCR digit misreads
    if w_clean == 'lD4': return '104'
    if w_clean == 'lD7': return '107'
    if w_clean == 'OlD': return '010'
    return w_clean

rows = []
for p in range(94, 662):
    page = doc[p]
    min_y = 340 if p == 94 else 220
    words = [w for w in page.get_text("words") if min_y < w[1] < 840 and "peraturan.go.id" not in w[4]]
    
    code_words = []
    for w in words:
        if w[0] < 325:
            norm = normalize_code_word(w[4])
            if norm.isdigit():
                code_words.append((w[0], w[1], w[2], w[3], norm, "'" in w[4]))
                
    if not code_words:
        continue
    code_words.sort(key=lambda w: (w[1], w[0]))
    
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
        
        akun = "".join(w[4] for w in cwords if w[0] < 145)
        kel = "".join(w[4] for w in cwords if 145 <= w[0] < 172)
        jen = "".join(w[4] for w in cwords if 172 <= w[0] < 195)
        obj = "".join(w[4] for w in cwords if 195 <= w[0] < 225)
        rin = "".join(w[4] for w in cwords if 225 <= w[0] < 255)
        sub = "".join(w[4] for w in cwords if 255 <= w[0] < 290)
        subsub = "".join(w[4] for w in cwords if 290 <= w[0] < 325)
        has_apostrophe = any(w[5] for w in cwords if 290 <= w[0] < 325)
        
        # If akun is missing but jen is 2 (e.g. page 139), inherit 1 and 3
        if not akun and (kel or jen):
            akun = "1"
            if not kel:
                kel = "3"
                
        row_uwords = [w for w in uraian_words if top <= w[1] < bottom]
        row_uwords.sort(key=lambda w: (round(w[1] / 3), w[0]))
        uraian = " ".join(w[4] for w in row_uwords).strip()
        
        parts = [p for p in [akun, kel, jen, obj, rin, sub, subsub] if p]
        code = ".".join(parts)
        level = len(parts)
        
        rows.append({
            "page": p + 1,
            "kode": code,
            "has_apostrophe": has_apostrophe,
            "level": level,
            "uraian": uraian
        })

print(f"Total rows: {len(rows)}")
codes = [r["kode"] for r in rows]
dup_codes = [c for c, count in Counter(codes).items() if count > 1]
print(f"Duplicate codes: {len(dup_codes)}")
for d in dup_codes:
    matches = [r for r in rows if r["kode"] == d]
    print(f"  Code {d} (count={len(matches)}):")
    for m in matches:
        print(f"    Page {m['page']} (apostrophe={m['has_apostrophe']}): {m['uraian']}")
