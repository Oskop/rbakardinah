import pymupdf
from collections import Counter
import re

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

start_page = 94
end_page = 661

rows = []
for p in range(start_page, end_page + 1):
    page = doc[p]
    min_y = 340 if p == 94 else 220
    words = [w for w in page.get_text("words") if min_y < w[1] < 840 and "peraturan.go.id" not in w[4]]
    
    code_words = [w for w in words if w[0] < 325 and w[4].isdigit()]
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
        
        akun = "".join(w[4] for w in cwords if w[0] < 145)
        kel = "".join(w[4] for w in cwords if 145 <= w[0] < 172)
        jen = "".join(w[4] for w in cwords if 172 <= w[0] < 195)
        obj = "".join(w[4] for w in cwords if 195 <= w[0] < 225)
        rin = "".join(w[4] for w in cwords if 225 <= w[0] < 255)
        sub = "".join(w[4] for w in cwords if 255 <= w[0] < 290)
        subsub = "".join(w[4] for w in cwords if 290 <= w[0] < 325)
        
        row_uwords = [w for w in uraian_words if top <= w[1] < bottom]
        row_uwords.sort(key=lambda w: (round(w[1] / 3), w[0]))
        uraian = " ".join(w[4] for w in row_uwords).strip()
        
        parts = [p for p in [akun, kel, jen, obj, rin, sub, subsub] if p]
        code = ".".join(parts)
        level = len(parts)
        
        rows.append({
            "page": p + 1,
            "kode": code,
            "level": level,
            "parts": parts,
            "uraian": uraian
        })

print(f"Total rows: {len(rows)}")

# Level distribution
levels = Counter(r["level"] for r in rows)
print("Level counts:")
for l, cnt in sorted(levels.items()):
    print(f"  Level {l}: {cnt} rows")

# Check duplicate codes
codes = [r["kode"] for r in rows]
dup_codes = [c for c, count in Counter(codes).items() if count > 1]
print(f"Total duplicate codes: {len(dup_codes)}")
if dup_codes:
    print("Sample duplicates:")
    for d in dup_codes[:5]:
        matches = [r for r in rows if r["kode"] == d]
        print(f"  Code {d}:")
        for m in matches:
            print(f"    Page {m['page']}: {m['uraian']}")

# Check any anomalous codes (e.g. not starting with 1)
non_1 = [r for r in rows if not r["kode"].startswith("1")]
print(f"Codes not starting with 1: {len(non_1)}")
if non_1:
    for n in non_1[:5]:
        print(f"  Page {n['page']}: {n['kode']} -> {n['uraian']}")
