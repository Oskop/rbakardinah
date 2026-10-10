import re

def clean_j(s):
    # Only replace j when followed by uppercase letter, or when preceded by uppercase letter and followed by letter
    s = re.sub(r'([a-zA-Z]+)j([A-Z][a-zA-Z]*)', r'\1/\2', s)
    return s

def clean_exclamation(s):
    # End of word exclamation mark preceded by letter
    # If GIG! -> GIGI
    s = s.replace("GIG!", "GIGI")
    # if ends with lowercase letter + ! -> replace with l
    s = re.sub(r'([a-zA-Z])!(?=\s|$|\))', r'\1l', s)
    return s

test_words = [
    "BatuanjGeologi",
    "MenterijGubernur",
    "Helicakj Bajaj",
    "Meja", "Kerja", "Jembatan", "Baja", "Uji", "Biji", "Anjing", "Pengkajian",
    "Aspa!", "Tege!", "ALATKEDOKTERANGIG!", "Spate!", "Abdomina!"
]

for tw in test_words:
    res = clean_exclamation(clean_j(tw))
    print(f"{tw:25} -> {res}")
