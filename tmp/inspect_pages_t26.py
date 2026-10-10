import pymupdf

doc = pymupdf.open('raw/Permendagri 108 Tahun 2016 (1).pdf')

for p in [95, 120, 200, 300, 400, 500, 600, 661]:
    page = doc[p]
    words = [w for w in page.get_text("words") if 150 < w[1] < 850]
    words.sort(key=lambda w: (w[1], w[0]))
    
    # find where first code word '1' at x < 145 appears
    first_1 = [w for w in words if w[0] < 145 and w[4] == '1']
    uraian_header = [w for w in words if 'URAIAN' in w[4].upper() or 'URALAN' in w[4].upper()]
    
    print(f"Page {p+1}:")
    if uraian_header:
        print(f"  Header URAIAN at y = {uraian_header[0][1]:.1f}")
    if first_1:
        print(f"  First code '1' at y = {first_1[0][1]:.1f}")
        
    # Check sample row with all 7 levels
    row_7 = []
    # group by y diff < 4
    lines = []
    for w in words:
        if not lines or abs(w[1] - lines[-1][0][1]) > 4.0:
            lines.append([w])
        else:
            lines[-1].append(w)
            
    for l in lines:
        code_words = [w for w in l if w[0] < 325 and w[4].isdigit()]
        if len(code_words) >= 7:
            print(f"  Sample 7-col row at y={l[0][1]:.1f}: " + " ".join(f"({w[0]:.0f}){w[4]}" for w in code_words) + " | " + " ".join(w[4] for w in l if w[0] >= 325))
            break
