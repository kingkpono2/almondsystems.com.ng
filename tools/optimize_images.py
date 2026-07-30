#!/usr/bin/env python3
"""Optimise the images in assets/ for web delivery.

Safe by default: running it with no flags only reports what it would do.
Nothing is written until you pass --apply.

    python tools/optimize_images.py                    # dry run, show savings
    python tools/optimize_images.py --apply            # recompress in place
    python tools/optimize_images.py --apply --webp     # also write .webp siblings
    python tools/optimize_images.py --max-width 1600   # cap huge originals

In-place recompression keeps each file's name and format, so no HTML needs
changing. Format changes (PNG photo -> JPEG/WebP) are only ever reported,
never done automatically, because they would break existing references.

Requires Pillow:  python -m pip install --upgrade Pillow
"""
from __future__ import annotations

import argparse
import io
import os
import shutil
import sys

try:
    from PIL import Image
except ImportError:  # pragma: no cover
    sys.exit("Pillow is required:  python -m pip install --upgrade Pillow")

RASTER = {".jpg", ".jpeg", ".png"}
# Photographic content wider than this is downscaled; nothing is ever upscaled.
DEFAULT_MAX_WIDTH = 1920
DEFAULT_JPEG_QUALITY = 82
DEFAULT_WEBP_QUALITY = 80


def human(n: float) -> str:
    for unit in ("B", "KB", "MB"):
        if abs(n) < 1024 or unit == "MB":
            return f"{n:,.1f} {unit}" if unit != "B" else f"{int(n)} B"
        n /= 1024
    return f"{n:,.1f} MB"


def has_alpha(im: Image.Image) -> bool:
    return im.mode in ("RGBA", "LA", "PA") or "transparency" in im.info


def encode(im: Image.Image, fmt: str, quality: int) -> bytes:
    """Encode to bytes, stripping metadata."""
    buf = io.BytesIO()
    if fmt == "JPEG":
        im.convert("RGB").save(
            buf, "JPEG", quality=quality, optimize=True, progressive=True
        )
    elif fmt == "PNG":
        im.save(buf, "PNG", optimize=True, compress_level=9)
    elif fmt == "WEBP":
        im.save(buf, "WEBP", quality=quality, method=6)
    return buf.getvalue()


def process(path: str, args: argparse.Namespace) -> dict:
    before = os.path.getsize(path)
    row = {
        "path": path,
        "before": before,
        "after": before,
        "note": "",
        "webp": 0,
        "hint": "",
    }

    with Image.open(path) as im:
        im.load()
        fmt = (im.format or "").upper()
        w, h = im.size
        alpha = has_alpha(im)

        work = im
        resized = ""
        if args.max_width and w > args.max_width:
            new_h = round(h * args.max_width / w)
            work = im.resize((args.max_width, new_h), Image.LANCZOS)
            resized = f"{w}x{h}->{args.max_width}x{new_h} "

        # Recompress, keeping the original format so references stay valid.
        target = "PNG" if fmt == "PNG" else "JPEG"
        data = encode(work, target, args.quality)

        # A PNG holding a photo is the wrong container; report the upside only.
        if fmt == "PNG" and not alpha:
            as_jpeg = len(encode(work, "JPEG", args.quality))
            if as_jpeg < before * 0.7:
                row["hint"] = f"PNG->JPEG would be {human(as_jpeg)} ({100 - as_jpeg * 100 // before}% less)"

        if args.webp:
            row["webp"] = len(encode(work, "WEBP", args.webp_quality))

        # Only keep the new bytes if they are a real improvement.
        if len(data) < before:
            row["after"] = len(data)
            row["note"] = resized + "recompressed"
            if args.apply:
                if args.backup:
                    shutil.copy2(path, path + ".orig")
                with open(path, "wb") as fh:
                    fh.write(data)
        else:
            row["note"] = resized + "already optimal" if not resized else resized + "kept"
            if resized and args.apply:
                # Downscale was requested and is worth keeping even if bytes grew.
                row["after"] = len(data)
                if args.backup:
                    shutil.copy2(path, path + ".orig")
                with open(path, "wb") as fh:
                    fh.write(data)

        if args.webp and args.apply and row["webp"]:
            side = os.path.splitext(path)[0] + ".webp"
            with open(side, "wb") as fh:
                fh.write(encode(work, "WEBP", args.webp_quality))

    return row


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("roots", nargs="*", default=["assets"], help="directories to scan (default: assets)")
    ap.add_argument("--apply", action="store_true", help="actually write files (default: dry run)")
    ap.add_argument("--backup", action="store_true", help="keep <file>.orig alongside each rewrite")
    ap.add_argument("--webp", action="store_true", help="also emit .webp siblings")
    ap.add_argument("--quality", type=int, default=DEFAULT_JPEG_QUALITY, help=f"JPEG quality (default {DEFAULT_JPEG_QUALITY})")
    ap.add_argument("--webp-quality", type=int, default=DEFAULT_WEBP_QUALITY, help=f"WebP quality (default {DEFAULT_WEBP_QUALITY})")
    ap.add_argument("--max-width", type=int, default=DEFAULT_MAX_WIDTH, help=f"downscale wider images (default {DEFAULT_MAX_WIDTH}, 0 disables)")
    args = ap.parse_args()

    targets: list[str] = []
    for root in args.roots or ["assets"]:
        if os.path.isfile(root):
            targets.append(root)
            continue
        for dirpath, _dirs, files in os.walk(root):
            for name in files:
                if os.path.splitext(name)[1].lower() in RASTER:
                    targets.append(os.path.join(dirpath, name))
    targets.sort()

    if not targets:
        print("no images found")
        return 0

    print(f"{'DRY RUN - nothing written' if not args.apply else 'APPLYING CHANGES'}   ({len(targets)} images)\n")
    rows = [process(p, args) for p in targets]
    rows.sort(key=lambda r: r["before"] - r["after"], reverse=True)

    tb = ta = tw = 0
    for r in rows:
        tb += r["before"]
        ta += r["after"]
        tw += r["webp"]
        saved = r["before"] - r["after"]
        pct = f"-{saved * 100 // r['before']}%" if saved > 0 and r["before"] else "  ~"
        line = f"  {human(r['before']):>10} -> {human(r['after']):>10} {pct:>5}  {r['path']}"
        if r["webp"]:
            line += f"  [webp {human(r['webp'])}]"
        print(line)
        if r["hint"]:
            print(f"{'':>34}  hint: {r['hint']}")

    print(f"\n  total: {human(tb)} -> {human(ta)}   saved {human(tb - ta)}"
          f" ({(tb - ta) * 100 // tb if tb else 0}%)")
    if tw:
        print(f"  webp set would total {human(tw)} ({(tb - tw) * 100 // tb if tb else 0}% under current)")
    if not args.apply:
        print("\n  re-run with --apply to write these changes (add --backup to keep originals)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
