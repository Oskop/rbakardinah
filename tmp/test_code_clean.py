import pymupdf
from collections import Counter
import re

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# Let's test on page 139 and page 214 first
for p in [138, 213]:
    page = doc[p]
    min_y = 220
    words = [w for w in page.get_text("words") if min_y < w[1] < 840 and "peraturan.go.id" not in w[4]]
    
    # Clean code word candidate
    code_words = []
    for w in words:
        if w[0] < 325:
            clean_w = w[4].strip("'\"`.,;:-")
            if clean_w.isdigit():
                # create modified word tuple with clean digits
                code_words.append((w[0], w[1], w[2], w[3], clean_w))
                
    code_words.sort(key=lambda w: (w[1], w[0]))
    
    clusters = []
    for w in code_words:
        if not clusters or abs(w[1] - clusters[-1]["y_center"]) > 6.0:
            clusters.append({"words": [w], "y_center": w[1]})
        else:
            clusters[-1]["words"].append(w)
            clusters[-1]["y_center"] = sum(cw[1] for cw in clusters[-1]["words"]) / len(clusters[-1]["words"])
            
    print(f"\n--- Page {p+1} --- Total clusters: {len(clusters)}")
    for c in clusters:
        cwords = c["words"]
        cwords.sort(key=lambda w: w[0])
        print("y~{:.1f}: ".format(c["y_center"]) + " ".join(f"({w[0]:.0f}){w[4]}" for w in cwords))
