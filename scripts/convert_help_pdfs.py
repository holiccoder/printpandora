#!/usr/bin/env python3
"""Convert the extracted PDF text files into HTML article bodies for seeding."""

import os
import re
from html import escape

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
EXTRACT_DIR = os.path.join(BASE_DIR, "storage", "from-tool", "pdf-extracts")
OUT_FILE = os.path.join(BASE_DIR, "storage", "from-tool", "help-center-articles.php")

ARTICLES = [
    {
        "slug": "letterpress-production-and-file-specs",
        "title": "活版印刷（Letterpress）制作与文件规范",
        "file": "Letterpress_活版印刷制作与文件规范_专业案例手册.txt",
    },
    {
        "slug": "business-card-size-and-notes",
        "title": "印刷名片文件尺寸与注意事项",
        "file": "印刷名片文件尺寸与注意事项.txt",
    },
    {
        "slug": "prepress-source-files-and-special-finishes",
        "title": "名片印前源文件与特殊工艺完稿手册",
        "file": "名片印前源文件与特殊工艺完稿手册-clean.txt",
    },
    {
        "slug": "foil-and-spot-uv-design-guide",
        "title": "名片热烫、冷烫与局部 UV 工艺设计指南",
        "file": "名片热烫冷烫与局部UV工艺设计指南.txt",
    },
]

FOOTER_PATTERNS = [
    re.compile(r"^--- Page \d+ ---$"),
    re.compile(r"LETTERPRESS GUIDE\s*[·\-]\s*\d+"),
    re.compile(r"名片印前与特殊工艺手册\s+成品"),
    re.compile(r"适用于标准横版名片"),
    re.compile(r"提交印刷前请按最后一页逐项检查"),
    re.compile(r"最终以印厂模板和打样为准"),
    re.compile(r"^\s*INKPAVO\s*(LOGO)?\s*$"),
]


def is_footer(line: str) -> bool:
    return any(p.search(line) for p in FOOTER_PATTERNS)


def clean_line(line: str) -> str:
    return line.replace("\u000c", "").replace("\r", "").strip()


def is_bullet(line: str) -> bool:
    return bool(re.match(r"^[\s]*[•·\-\*]\s+", line))


def strip_bullet(line: str) -> str:
    return re.sub(r"^[\s]*[•·\-\*]\s+", "", line).strip()


def is_section_number(line: str) -> bool:
    return bool(re.match(r"^\d{2}$", line.strip()))


def normalize_space(text: str) -> str:
    return re.sub(r"\s+", " ", text).strip()


def convert_file(path: str) -> str:
    with open(path, "r", encoding="utf-8") as f:
        raw_lines = f.readlines()

    lines = [clean_line(l) for l in raw_lines]
    # Remove footer/page-marker lines and standalone section numbers that are page numbers only.
    cleaned: list[str] = []
    for line in lines:
        if not line or is_footer(line):
            continue
        cleaned.append(line)

    # Group lines into blocks separated by blank lines (already split).
    # Merge a standalone "01" style section number into the next line as a heading.
    blocks: list[str] = []
    i = 0
    while i < len(cleaned):
        line = cleaned[i]
        if is_section_number(line) and i + 1 < len(cleaned):
            blocks.append(f"{line}. {cleaned[i + 1]}")
            i += 2
            continue
        blocks.append(line)
        i += 1

    # Build HTML
    html_parts: list[str] = []
    list_buffer: list[str] = []

    def flush_list():
        nonlocal list_buffer
        if list_buffer:
            html_parts.append("<ul>")
            for item in list_buffer:
                html_parts.append(f"<li>{item}</li>")
            html_parts.append("</ul>")
            list_buffer = []

    for block in blocks:
        text = normalize_space(block)
        if not text:
            continue

        if is_bullet(text):
            list_buffer.append(escape(strip_bullet(text)))
            continue

        flush_list()

        # Simple heuristic: if the block is short (<=35 chars) and contains no sentence-ending punctuation,
        # treat as a heading. Also if it starts with a digit-dot pattern like "01. ...".
        has_sentence_end = bool(re.search(r"[。！？；.!?;]$", text))
        is_heading = (
            (len(text) <= 45 and not has_sentence_end)
            or re.match(r"^\d{2}\.\s+", text)
            or re.match(r"^[A-D]\.\s+", text)
        )

        if is_heading:
            html_parts.append(f"<h2>{escape(text)}</h2>")
        else:
            html_parts.append(f"<p>{escape(text)}</p>")

    flush_list()

    return "\n".join(html_parts)


def main():
    out_lines = ['<?php\n\nreturn [']
    for article in ARTICLES:
        path = os.path.join(EXTRACT_DIR, article["file"])
        if not os.path.exists(path):
            raise FileNotFoundError(f"Missing extract file: {path}")
        body = convert_file(path)
        out_lines.append("    [")
        out_lines.append(f"        'slug' => {repr(article['slug'])},")
        out_lines.append(f"        'title' => {repr(article['title'])},")
        out_lines.append("        'body' => <<<'HTML'")
        out_lines.append(body)
        out_lines.append("HTML,")
        out_lines.append("    ],")
    out_lines.append("];\n")

    with open(OUT_FILE, "w", encoding="utf-8") as f:
        f.write("\n".join(out_lines))

    print(f"Wrote {OUT_FILE}")


if __name__ == "__main__":
    main()
