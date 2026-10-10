import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

for p in range(640, len(doc)):
    text = doc[p].get_text() or ""
    if "17 TAHUN 2007" in text or "PENERAPAN" in text or "AKRUAL" in text or "TABEL" in text:
        print(f"Page {p+1} matches!")
