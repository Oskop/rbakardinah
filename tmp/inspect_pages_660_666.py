import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

for p in range(659, 666):
    print(f"\n==================== Page {p+1} ====================")
    text = doc[p].get_text() or ""
    # print lines
    lines = [l.strip() for l in text.split("\n") if l.strip()]
    print("Total lines:", len(lines))
    print("First 15 lines:", lines[:15])
    print("Last 15 lines:", lines[-15:])
