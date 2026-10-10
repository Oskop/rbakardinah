import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# Check Page 175 (index 174)
print("=== Page 175 lines ===")
p175 = doc[174]
words175 = [w for w in p175.get_text("words") if 220 < w[1] < 840 and "peraturan.go.id" not in w[4]]
lines175 = []
for w in words175:
    if not lines175 or abs(w[1] - lines175[-1][0][1]) > 4.0:
        lines175.append([w])
    else:
        lines175[-1].append(w)
for l in lines175:
    l.sort(key=lambda w: w[0])
    print(f"y~{l[0][1]:.1f}: " + " | ".join(f"({w[0]:.1f}){w[4]}" for w in l))
