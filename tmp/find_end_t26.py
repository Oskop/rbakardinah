import pymupdf
import re

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

# Check titles or headings across pages 500 to 669
for p in range(500, len(doc)):
    text = doc[p].get_text() or ""
    # Look for LAMPIRAN, TABEL, HURUF, KONVERSI, etc.
    matches = re.findall(r'(?:TABEL\s+[\d\.]+|LAMPIRAN|BAB\s+[IVXLCDM]+|[A-Z]\.\s+TABEL|KONVERSI|TABEL\s+KONVERSI)', text, re.IGNORECASE)
    if matches:
        first_few = [l.strip() for l in text.split('\n') if any(m.lower() in l.lower() for m in matches)]
        print(f"Page {p+1}: {matches} -> {first_few[:2]}")
