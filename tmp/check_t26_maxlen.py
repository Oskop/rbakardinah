import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# Check max uraian length
max_len = 0
longest_text = ""
longest_page = 0

for p in range(94, 662):
    page = doc[p]
    min_y = 340 if p == 94 else 220
    words = [w for w in page.get_text("words") if min_y < w[1] < 840 and "peraturan.go.id" not in w[4]]
    code_words = [w for w in words if w[0] < 325 and any(c.isdigit() for c in w[4])]
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
        row_uwords = [w for w in uraian_words if top <= w[1] < bottom]
        row_uwords.sort(key=lambda w: (round(w[1] / 3), w[0]))
        text = " ".join(w[4] for w in row_uwords).strip()
        if len(text) > max_len:
            max_len = len(text)
            longest_text = text
            longest_page = p + 1

print(f"Longest uraian: {max_len} chars (Page {longest_page}):")
print(f"  {repr(longest_text)}")
