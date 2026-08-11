import pdfplumber
import os

folder = r"C:\Users\workw\Documents\WXWork\1688857385809879\WeDrive\上海网域包装印刷有限公司\独立站共享资料\独立站资料\faq和专业知识"
output_dir = "storage/from-tool/pdf-extracts"
os.makedirs(output_dir, exist_ok=True)

for f in sorted(os.listdir(folder)):
    if f.endswith('.pdf'):
        path = os.path.join(folder, f)
        basename = os.path.splitext(f)[0]
        out_path = os.path.join(output_dir, f"{basename}.txt")
        print(f"Extracting: {f}")
        try:
            with pdfplumber.open(path) as pdf:
                full_text = []
                for i, page in enumerate(pdf.pages):
                    text = page.extract_text()
                    if text:
                        full_text.append(f"--- Page {i+1} ---\n{text}")
                with open(out_path, 'w', encoding='utf-8') as out:
                    out.write('\n\n'.join(full_text))
                print(f"  -> {out_path}")
        except Exception as e:
            print(f"Error: {e}")
