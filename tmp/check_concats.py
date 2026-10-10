import sys
sys.path.insert(0, '.')
import tmp.test_extractor as te

for r in te.t25:
    if 'ASETLANCAR' in r['uraian']:
        print("Found:", r)
    # Check if any long words look concatenated
    words = r['uraian'].split()
    for w in words:
        if len(w) > 20 and not '-' in w and not '/' in w:
            print("Long word:", w, "in", r['kode'], r['uraian'])
