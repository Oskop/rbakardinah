import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

page95 = doc[94] # index 94 = Page 95
words = page95.get_text("words")
words = [w for w in words if w[1] > 200 and w[1] < 850]
words.sort(key=lambda w: (w[1], w[0]))

print("All words on page 95 sorted by y, x:")
# cluster into visual lines with diff < 4.0
lines = []
for w in words:
    if not lines or abs(w[1] - lines[-1][0][1]) > 4.0:
        lines.append([w])
    else:
        lines[-1].append(w)

for l in lines:
    l.sort(key=lambda w: w[0])
    print(f"y~{l[0][1]:.1f}: " + " | ".join(f"({w[0]:.1f}){w[4]}" for w in l))
