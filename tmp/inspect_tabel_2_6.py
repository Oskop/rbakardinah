import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')
print("Total pages in doc:", len(doc))

# Inspect page 95 (index 94)
page95 = doc[94]
words95 = [w for w in page95.get_text("words") if w[1] > 200 and w[1] < 850]
words95.sort(key=lambda w: (w[1], w[0]))

print("\n=== Page 95 sample words (y > 400) ===")
for w in [w for w in words95 if w[1] > 400][:35]:
    print(f"({w[0]:.1f}, {w[1]:.1f}): {w[4]}")

# Inspect last 3 pages (indices 666, 667, 668)
for p in range(666, len(doc)):
    print(f"\n=== Page {p+1} ===")
    lines = [l.strip() for l in (doc[p].get_text() or "").split("\n") if l.strip()]
    for l in lines[:10]:
        print(repr(l))
    print("...")
    for l in lines[-10:]:
        print(repr(l))
