import sys
sys.path.insert(0, '.')
import tmp.consolidated_extractor as ce

for name, rows in [('2.2', ce.t22), ('2.3', ce.t23), ('2.4', ce.t24), ('2.5', ce.t25)]:
    max_len = max(len(r['uraian']) for r in rows)
    max_row = max(rows, key=lambda r: len(r['uraian']))
    print(f"Tabel {name} max uraian length: {max_len}")
    print(f"  Longest: {max_row['kode']} -> {max_row['uraian']}")
