import pymupdf
import re

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# Check sample uraian across pages
ex_ex = []
for p in range(94, 662):
    page = doc[p]
    min_y = 340 if p == 94 else 220
    words = [w for w in page.get_text("words") if min_y < w[1] < 840 and "peraturan.go.id" not in w[4]]
    uraian_words = [w for w in words if w[0] >= 325]
    for w in uraian_words:
        if w[4].endswith("!") and len(w[4]) > 2:
            ex_ex.append((p+1, w[4]))
        if "j" in w[4] and not w[4].startswith("j") and not w[4].endswith("j") and any(c.isupper() for c in w[4]):
            ex_ex.append((p+1, w[4]))

print("Sample exclamation words:", [x for x in ex_ex if "!" in x[1]][:15])
print("Sample j-containing words:", [x for x in ex_ex if "j" in x[1]][:15])
