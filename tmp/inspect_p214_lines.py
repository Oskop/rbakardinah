import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')
page214 = doc[213]

words = page214.get_text("words")
words = [w for w in words if 490 < w[1] < 630]
words.sort(key=lambda w: (round(w[1] / 6) * 6, w[0]))

from itertools import groupby
for k, g in groupby(words, key=lambda w: round(w[1] / 6)):
    lw = list(g)
    lw.sort(key=lambda w: w[0])
    print(" | ".join(f"({w[0]:.0f}){w[4]}" for w in lw))
