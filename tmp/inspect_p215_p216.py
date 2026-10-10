import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# Check Page 215 (index 214) and Page 216 (index 215)
for p in [214, 215]:
    print(f"\n=== Page {p+1} lines ===")
    page = doc[p]
    words = [w for w in page.get_text("words") if 220 < w[1] < 840 and "peraturan.go.id" not in w[4]]
    lines = []
    for w in words:
        if not lines or abs(w[1] - lines[-1][0][1]) > 4.0:
            lines.append([w])
        else:
            lines[-1].append(w)
    for l in lines:
        l.sort(key=lambda w: w[0])
        print(f"y~{l[0][1]:.1f}: " + " | ".join(f"({w[0]:.1f}){w[4]}" for w in l))
