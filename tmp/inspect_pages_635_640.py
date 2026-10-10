import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

for p in range(634, 640):
    print(f"\n==================== Page {p+1} ====================")
    text = doc[p].get_text() or ""
    lines = [l.strip() for l in text.split("\n") if l.strip()]
    for l in lines[:25]:
        print(repr(l))
    print("...")
    for l in lines[-10:]:
        print(repr(l))
