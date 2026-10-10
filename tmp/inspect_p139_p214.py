import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# Inspect Page 139 (index 138)
print("=== Page 139 lines ===")
p139 = doc[138]
words139 = [w for w in p139.get_text("words") if 200 < w[1] < 840 and "peraturan.go.id" not in w[4]]
lines139 = []
for w in words139:
    if not lines139 or abs(w[1] - lines139[-1][0][1]) > 4.0:
        lines139.append([w])
    else:
        lines139[-1].append(w)
for l in lines139:
    l.sort(key=lambda w: w[0])
    print(f"y~{l[0][1]:.1f}: " + " | ".join(f"({w[0]:.1f}){w[4]}" for w in l))

# Inspect Page 214 (index 213)
print("\n=== Page 214 lines (around y=350 to 650) ===")
p214 = doc[213]
words214 = [w for w in p214.get_text("words") if 350 < w[1] < 650 and "peraturan.go.id" not in w[4]]
lines214 = []
for w in words214:
    if not lines214 or abs(w[1] - lines214[-1][0][1]) > 4.0:
        lines214.append([w])
    else:
        lines214[-1].append(w)
for l in lines214:
    l.sort(key=lambda w: w[0])
    print(f"y~{l[0][1]:.1f}: " + " | ".join(f"({w[0]:.1f}){w[4]}" for w in l))
