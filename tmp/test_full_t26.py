import pymupdf
import time
import re

start_time = time.time()
doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# Tabel 2.6 starts at page 95 (index 94) and ends at page 662 (index 661)
start_page = 94
end_page = 661 # inclusive

print(f"Scanning Tabel 2.6 from page {start_page + 1} to {end_page + 1} ({end_page - start_page + 1} pages)...")

total_rows = 0
empty_uraian = []
sample_rows = []

for p in range(start_page, end_page + 1):
    page = doc[p]
    min_y = 340 if p == 94 else 220
    words = [w for w in page.get_text("words") if min_y < w[1] < 840 and "peraturan.go.id" not in w[4]]
    
    code_words = [w for w in words if w[0] < 325 and w[4].isdigit()]
    if not code_words:
        continue
    code_words.sort(key=lambda w: (w[1], w[0]))
    
    # cluster code words
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
        
        # Col ranges:
        # akun < 145
        # kel 145..172
        # jen 172..195
        # obj 195..225
        # rin 225..255
        # sub 255..290
        # subsub 290..325
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
        
        total_rows += 1
        if not uraian:
            empty_uraian.append((p + 1, code, c["y_center"]))
            
        if total_rows % 2500 == 0:
            sample_rows.append((p + 1, code, level, uraian))

elapsed = time.time() - start_time
print(f"Extraction completed in {elapsed:.2f} seconds!")
print(f"Total rows extracted: {total_rows}")
print(f"Empty uraian count: {len(empty_uraian)}")
if empty_uraian:
    print("Sample empty uraian:", empty_uraian[:10])
print("\nSample extracted rows throughout the doc:")
for s in sample_rows:
    print(f"  Page {s[0]}: {s[1]:25} | Level {s[2]} | {s[3]}")
