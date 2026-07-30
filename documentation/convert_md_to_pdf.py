import os
import re
import subprocess
import markdown

def convert_md_to_pdf():
    md_file_path = r"c:\Users\PC12\Project\rbakardinah\documentation\MANUAL_BOOK_RBA_HOSPITAL.md"
    html_file_path = r"c:\Users\PC12\Project\rbakardinah\documentation\MANUAL_BOOK_RBA_HOSPITAL.html"
    pdf_file_path = r"c:\Users\PC12\Project\rbakardinah\documentation\MANUAL_BOOK_RBA_HOSPITAL.pdf"
    doc_dir = r"c:\Users\PC12\Project\rbakardinah\documentation"

    with open(md_file_path, "r", encoding="utf-8") as f:
        md_text = f.read()

    # Replace mermaid block with a styled note or handle mermaid text gracefully
    def handle_mermaid(match):
        mermaid_code = match.group(1)
        return f'<div class="mermaid-box"><strong>Alur Diagram:</strong><pre>{mermaid_code}</pre></div>'
    
    md_text = re.sub(r'```mermaid\s*\n(.*?)```', handle_mermaid, md_text, flags=re.DOTALL)

    # Convert markdown to HTML
    html_content = markdown.markdown(
        md_text,
        extensions=['tables', 'fenced_code', 'toc', 'attr_list', 'nl2br']
    )

    # Fix image sources to absolute file:/// URLs
    def fix_img_src(match):
        img_src = match.group(1)
        if img_src.startswith('./'):
            abs_path = os.path.normpath(os.path.join(doc_dir, img_src))
        elif not os.path.isabs(img_src) and not img_src.startswith('http'):
            abs_path = os.path.normpath(os.path.join(doc_dir, img_src))
        else:
            abs_path = img_src
            
        abs_url = "file:///" + abs_path.replace('\\', '/')
        return f'src="{abs_url}"'

    html_content = re.sub(r'src=["\']([^"\']+)["\']', fix_img_src, html_content)

    # Styling for PDF
    css_styles = """
    <style>
        @page {
            size: A4;
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1e293b;
            line-height: 1.6;
            font-size: 11pt;
            margin: 0;
            padding: 0;
        }
        h1 {
            color: #1e3a8a;
            font-size: 22pt;
            border-bottom: 3px solid #1e3a8a;
            padding-bottom: 8px;
            margin-top: 20px;
            margin-bottom: 10px;
            text-align: center;
        }
        h2 {
            color: #1e3a8a;
            font-size: 15pt;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 25px;
            margin-bottom: 12px;
            page-break-after: avoid;
        }
        h3 {
            color: #0f172a;
            font-size: 12pt;
            margin-top: 18px;
            margin-bottom: 8px;
            page-break-after: avoid;
        }
        p {
            margin-bottom: 10px;
            text-align: justify;
        }
        strong {
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10pt;
            page-break-inside: avoid;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            color: #1e3a8a;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 15px auto;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            page-break-inside: avoid;
        }
        .mermaid-box {
            background-color: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 12px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 9pt;
            page-break-inside: avoid;
        }
        ul, ol {
            margin-top: 5px;
            margin-bottom: 12px;
            padding-left: 20px;
        }
        li {
            margin-bottom: 4px;
        }
        hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }
        .cover-sub {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            color: #3b82f6;
            margin-top: -10px;
            margin-bottom: 25px;
        }
    </style>
    """

    full_html = f"""<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manual Book RBA Hospital - RSUD Kardinah</title>
    {css_styles}
</head>
<body>
    {html_content}
</body>
</html>
"""

    with open(html_file_path, "w", encoding="utf-8") as f:
        f.write(full_html)

    print(f"HTML successfully generated at {html_file_path}")

    # Use Microsoft Edge to convert HTML to PDF
    edge_path = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
    if not os.path.exists(edge_path):
        edge_path = r"C:\Program Files\Microsoft\Edge\Application\msedge.exe"

    cmd = [
        edge_path,
        "--headless",
        "--disable-gpu",
        "--no-pdf-header-footer",
        f"--print-to-pdf={pdf_file_path}",
        html_file_path
    ]

    result = subprocess.run(cmd, capture_output=True, text=True)
    if os.path.exists(pdf_file_path):
        print(f"PDF successfully generated at {pdf_file_path}")
    else:
        print(f"Failed to generate PDF: {result.stderr}")

if __name__ == '__main__':
    convert_md_to_pdf()
