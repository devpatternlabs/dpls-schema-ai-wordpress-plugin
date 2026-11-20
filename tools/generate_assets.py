"""
Asset Generator for WordPress.org Plugin Directory.
Generates:
- icon-128x128.png
- icon-256x256.png
- banner-772x250.png
- banner-1544x500.png
"""
import os
from PIL import Image, ImageDraw, ImageFont

ASSETS_DIR = r"C:\xampp\htdocs\dpls-schema-ai-svn\assets"
os.makedirs(ASSETS_DIR, exist_ok=True)

FONT_DIR = os.path.join(os.environ.get("WINDIR", "C:\\Windows"), "Fonts")
font_bold_path = os.path.join(FONT_DIR, "segoeuib.ttf")
if not os.path.exists(font_bold_path):
    font_bold_path = os.path.join(FONT_DIR, "arialbd.ttf")

font_regular_path = os.path.join(FONT_DIR, "segoeui.ttf")
if not os.path.exists(font_regular_path):
    font_regular_path = os.path.join(FONT_DIR, "arial.ttf")

def create_gradient(width, height, color1, color2):
    base = Image.new("RGBA", (width, height), color1)
    top = Image.new("RGBA", (width, height), color2)
    mask = Image.new("L", (width, height))
    mask_data = []
    for y in range(height):
        for x in range(width):
            # Diagonal gradient
            ratio = (x / width * 0.7) + (y / height * 0.3)
            mask_data.append(int(255 * min(max(ratio, 0.0), 1.0)))
    mask.putdata(mask_data)
    return Image.composite(top, base, mask)

def draw_network_nodes(draw, offset_x, offset_y, scale=1.0):
    # Schema tree graph node illustration
    nodes = [
        (0, 0, 18 * scale, "#38bdf8"),       # Root (@context)
        (-70 * scale, 60 * scale, 14 * scale, "#818cf8"),   # Left branch (Type)
        (70 * scale, 60 * scale, 14 * scale, "#34d399"),    # Right branch (Entity)
        (-110 * scale, 130 * scale, 11 * scale, "#c084fc"), # Leaf 1
        (-30 * scale, 130 * scale, 11 * scale, "#38bdf8"),  # Leaf 2
        (40 * scale, 130 * scale, 11 * scale, "#f472b6"),   # Leaf 3
        (110 * scale, 130 * scale, 11 * scale, "#fbbf24"),  # Leaf 4
    ]
    edges = [
        (0, 1), (0, 2),
        (1, 3), (1, 4),
        (2, 5), (2, 6)
    ]
    
    # Draw connections
    for i1, i2 in edges:
        x1 = offset_x + nodes[i1][0]
        y1 = offset_y + nodes[i1][1]
        x2 = offset_x + nodes[i2][0]
        y2 = offset_y + nodes[i2][1]
        draw.line([(x1, y1), (x2, y2)], fill=(255, 255, 255, 120), width=int(3 * scale))

    # Draw nodes
    for x, y, r, color in nodes:
        nx = offset_x + x
        ny = offset_y + y
        # Outer glow
        draw.ellipse([nx - r - 4 * scale, ny - r - 4 * scale, nx + r + 4 * scale, ny + r + 4 * scale], fill=(255, 255, 255, 40))
        draw.ellipse([nx - r, ny - r, nx + r, ny + r], fill=color)

def draw_sparkle(draw, cx, cy, size, color):
    # AI 4-point sparkle star
    points = [
        (cx, cy - size),
        (cx + size * 0.25, cy - size * 0.25),
        (cx + size, cy),
        (cx + size * 0.25, cy + size * 0.25),
        (cx, cy + size),
        (cx - size * 0.25, cy + size * 0.25),
        (cx - size, cy),
        (cx - size * 0.25, cy - size * 0.25),
    ]
    draw.polygon(points, fill=color)

# -----------------
# 1. GENERATE ICON (256x256 & 128x128)
# -----------------
def generate_icon(size):
    img = create_gradient(size, size, (15, 23, 42, 255), (30, 27, 75, 255))
    draw = ImageDraw.Draw(img)

    scale = size / 256.0
    
    # Decorative subtle circles
    draw.ellipse([size * 0.1, size * 0.1, size * 0.9, size * 0.9], outline=(56, 189, 248, 50), width=int(2 * scale))
    
    # Structured Schema Graph
    draw_network_nodes(draw, size * 0.5, size * 0.32, scale=0.75 * scale)

    # AI Sparkles
    draw_sparkle(draw, size * 0.78, size * 0.22, 16 * scale, "#38bdf8")
    draw_sparkle(draw, size * 0.22, size * 0.26, 11 * scale, "#c084fc")
    draw_sparkle(draw, size * 0.82, size * 0.72, 10 * scale, "#f472b6")

    # Bottom Label: JSON-LD
    try:
        font_logo = ImageFont.truetype(font_bold_path, int(20 * scale))
    except Exception:
        font_logo = ImageFont.load_default()

    label = "{ JSON-LD }"
    bbox = draw.textbbox((0, 0), label, font=font_logo)
    w = bbox[2] - bbox[0]
    draw.text(((size - w) / 2, size * 0.82), label, fill="#38bdf8", font=font_logo)

    return img

icon_256 = generate_icon(256)
icon_256.save(os.path.join(ASSETS_DIR, "icon-256x256.png"))
print("Generated icon-256x256.png")

icon_128 = generate_icon(128)
icon_128.save(os.path.join(ASSETS_DIR, "icon-128x128.png"))
print("Generated icon-128x128.png")

# -----------------
# 2. GENERATE BANNER (1544x500 & 772x250)
# -----------------
def generate_banner(width, height):
    img = create_gradient(width, height, (11, 15, 26, 255), (26, 32, 64, 255))
    draw = ImageDraw.Draw(img)
    scale = width / 1544.0

    # Draw right-side visual graph element
    draw_network_nodes(draw, width * 0.82, height * 0.35, scale=1.4 * scale)
    draw_sparkle(draw, width * 0.68, height * 0.25, 24 * scale, "#38bdf8")
    draw_sparkle(draw, width * 0.94, height * 0.20, 18 * scale, "#c084fc")
    draw_sparkle(draw, width * 0.73, height * 0.75, 16 * scale, "#34d399")
    draw_sparkle(draw, width * 0.92, height * 0.68, 20 * scale, "#f472b6")

    # Left-side Typography
    try:
        font_title = ImageFont.truetype(font_bold_path, int(52 * scale))
        font_subtitle = ImageFont.truetype(font_regular_path, int(24 * scale))
        font_badge = ImageFont.truetype(font_bold_path, int(15 * scale))
        font_author = ImageFont.truetype(font_regular_path, int(18 * scale))
    except Exception:
        font_title = ImageFont.load_default()
        font_subtitle = ImageFont.load_default()
        font_badge = ImageFont.load_default()
        font_author = ImageFont.load_default()

    # Small top tag
    tag_text = "WORDPRESS STRUCTURED DATA & SCHEMA.ORG ENGINE"
    draw.text((width * 0.08, height * 0.20), tag_text, fill="#38bdf8", font=font_badge)

    # Title
    title_text = "DPLS Schema AI"
    draw.text((width * 0.08, height * 0.28), title_text, fill="#ffffff", font=font_title)

    # Subtitle
    sub_text = "Automated JSON-LD generation with optional local AI enrichment."
    draw.text((width * 0.08, height * 0.46), sub_text, fill="#94a3b8", font=font_subtitle)

    # Feature badges
    badges = [
        "14+ Schema Types",
        "Deterministic Rules",
        "Optional Local Ollama AI",
        "WooCommerce Ready",
        "Zero Cloud Transmission"
    ]
    bx = width * 0.08
    by = height * 0.64
    for badge in badges:
        bbox = draw.textbbox((0, 0), badge, font=font_badge)
        bw = (bbox[2] - bbox[0]) + (24 * scale)
        bh = 32 * scale
        # Pill background
        draw.rounded_rectangle([bx, by, bx + bw, by + bh], radius=int(6 * scale), fill=(30, 41, 59, 200), outline=(56, 189, 248, 120), width=1)
        draw.text((bx + (12 * scale), by + (7 * scale)), badge, fill="#e2e8f0", font=font_badge)
        bx += bw + (14 * scale)
        if bx > width * 0.65:
            bx = width * 0.08
            by += bh + (10 * scale)

    # Author mark at bottom
    author_text = "By Dev Pattern Labs  |  100% Free & Open Source (GPLv2)"
    draw.text((width * 0.08, height * 0.85), author_text, fill="#64748b", font=font_author)

    return img

banner_1544 = generate_banner(1544, 500)
banner_1544.save(os.path.join(ASSETS_DIR, "banner-1544x500.png"))
print("Generated banner-1544x500.png")

banner_772 = generate_banner(772, 250)
banner_772.save(os.path.join(ASSETS_DIR, "banner-772x250.png"))
print("Generated banner-772x250.png")

print("All directory assets generated successfully in:", ASSETS_DIR)
