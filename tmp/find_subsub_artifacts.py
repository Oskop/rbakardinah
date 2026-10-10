import pymupdf
import re

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# Check across all pages for code words with apostrophe or letter
found_apostrophes = []
for p in range(94, 662):
    page = doc[p]
    min_y = 340 if p == 94 else 220
    words = [w for w in page.get_text("words") if min_y < w[1] < 840 and "peraturan.go.id" not in w[4]]
    for w in words:
        if 290 <= w[0] < 325: # subsub column
            if "'" in w[4] or '"' in w[4] or any(c.isalpha() for c in w[4]):
                found_apostrophes.append((p + 1, w[0], w[1], w[4]))

print(f"Total subsub words with apostrophe or letters: {len(found_apostrophes)}")
for item in found_apostrophes:
    print(f"  Page {item[0]} (x={item[1]:.1f}, y={item[2]:.1f}): {repr(item[3])}")
