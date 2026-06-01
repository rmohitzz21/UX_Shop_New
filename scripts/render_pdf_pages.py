from pathlib import Path
import argparse

import fitz
from PIL import Image, ImageDraw, ImageFont


def label_font(size=28):
    for candidate in ("C:/Windows/Fonts/arialbd.ttf", "C:/Windows/Fonts/calibrib.ttf"):
        if Path(candidate).exists():
            return ImageFont.truetype(candidate, size)
    return ImageFont.load_default()


def render_pages(pdf_path, output_dir):
    output_dir.mkdir(parents=True, exist_ok=True)
    document = fitz.open(pdf_path)
    rendered = []
    matrix = fitz.Matrix(1.5, 1.5)
    for index, page in enumerate(document):
        pixmap = page.get_pixmap(matrix=matrix, alpha=False)
        path = output_dir / f"page-{index + 1:03d}.png"
        pixmap.save(path)
        rendered.append(path)
    return rendered


def create_contact_sheets(page_paths, output_dir):
    sheets = []
    for group_index in range(0, len(page_paths), 4):
        group = page_paths[group_index : group_index + 4]
        sheet = Image.new("RGB", (1800, 1320), "#E5E7EB")
        draw = ImageDraw.Draw(sheet)
        for slot, page_path in enumerate(group):
            image = Image.open(page_path).convert("RGB")
            image.thumbnail((820, 1160))
            x = 55 + (slot % 2) * 875
            y = 80 + (slot // 2) * 600
            sheet.paste(image, (x, y))
            draw.text((x, 28 + (slot // 2) * 600), page_path.stem, fill="#111827", font=label_font())
        path = output_dir / f"sheet-{group_index // 4 + 1:02d}.png"
        sheet.save(path, quality=95)
        sheets.append(path)
    return sheets


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("pdf")
    parser.add_argument("output_dir")
    args = parser.parse_args()
    output_dir = Path(args.output_dir)
    pages = render_pages(Path(args.pdf), output_dir)
    sheets = create_contact_sheets(pages, output_dir)
    print(f"Rendered {len(pages)} page(s) and {len(sheets)} contact sheet(s) to {output_dir}")


if __name__ == "__main__":
    main()
