import sys
sys.path.insert(0, '.')
import tmp.test_extractor as te
from collections import Counter

t25 = te.t25
print(f"Total rows in Tabel 2.5: {len(t25)}")

# Level distribution
levels = Counter(r['level'] for r in t25)
print("Level distribution in Tabel 2.5:")
for lvl in sorted(levels):
    print(f"  Level {lvl}: {levels[lvl]} rows")

# Check unique codes
codes = [r['kode'] for r in t25]
dup_codes = [c for c, count in Counter(codes).items() if count > 1]
print(f"Duplicate codes count: {len(dup_codes)}")
if dup_codes:
    print("Sample duplicate codes:", dup_codes[:5])
    for d in dup_codes[:5]:
        matching = [r for r in t25 if r['kode'] == d]
        for m in matching:
            print(f"  Page {m['page']}: {m['kode']} -> {m['uraian']}")

print("\nFirst 5 rows of Tabel 2.5:")
for r in t25[:5]:
    print(f"  {r['kode']:20} | L{r['level']} | {r['uraian']}")

print("\nLast 5 rows of Tabel 2.5:")
for r in t25[-5:]:
    print(f"  {r['kode']:20} | L{r['level']} | {r['uraian']}")
