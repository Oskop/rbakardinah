import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# Check page 96
page96 = doc[95] # index 95 = Page 96
words = [w for w in page96.get_text("words") if 220 < w[1] < 840 and "peraturan.go.id" not in w[4]]

# Find all code words at x < 325
code_words = [w for w in words if w[0] < 325 and w[4].isdigit()]
code_words.sort(key=lambda w: (w[1], w[0]))

# Cluster code words into row anchors
clusters = []
for w in code_words:
    if not clusters or abs(w[1] - clusters[-1]["y_center"]) > 6.0:
        clusters.append({"words": [w], "y_center": w[1]})
    else:
        clusters[-1]["words"].append(w)
        clusters[-1]["y_center"] = sum(cw[1] for cw in clusters[-1]["words"]) / len(clusters[-1]["words"])

print("Total code clusters on Page 96:", len(clusters))

# Calculate boundaries
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
    
    row_uwords = [w for w in uraian_words if top <= w[1] < bottom]
    row_uwords.sort(key=lambda w: (round(w[1] / 3), w[0]))
    uraian = " ".join(w[4] for w in row_uwords)
    
    code = ".".join(p for p in [akun, kel, jen, obj, rin, sub, subsub] if p)
    print(f"y={c['y_center']:.1f} | {code:25} | {uraian}")
