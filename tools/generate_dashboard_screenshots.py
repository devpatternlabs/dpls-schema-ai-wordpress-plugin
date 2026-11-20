"""
Generate High-Fidelity WordPress Admin Dashboard Screenshots (1280x800)
for WordPress.org directory listing assets.

1. screenshot-1.png: Overview Dashboard (Coverage metrics, status bars, quick scan)
2. screenshot-2.png: Content Table with "Schema (DPLS)" badges & Bulk Actions dropdown
3. screenshot-3.png: Post Editor Meta Box with "Google Rich Results Test" 1-click button
4. screenshot-4.png: Integrations Hub with "SEO Plugin Conflict Detector (Rank Math)" banner
"""

import os
from PIL import Image, ImageDraw, ImageFont

OUTPUT_DIR = r"C:\xampp\htdocs\dpls-schema-ai-svn\assets"
LOCAL_ASSETS_DIR = r"C:\xampp\htdocs\DPLSplugin\assets"
os.makedirs(OUTPUT_DIR, exist_ok=True)

FONT_DIR = os.path.join(os.environ.get("WINDIR", "C:\\Windows"), "Fonts")
font_bold = os.path.join(FONT_DIR, "segoeuib.ttf")
if not os.path.exists(font_bold):
    font_bold = os.path.join(FONT_DIR, "arialbd.ttf")

font_regular = os.path.join(FONT_DIR, "segoeui.ttf")
if not os.path.exists(font_regular):
    font_regular = os.path.join(FONT_DIR, "arial.ttf")

font_mono = os.path.join(FONT_DIR, "consola.ttf")
if not os.path.exists(font_mono):
    font_mono = font_regular

def get_font(size, bold=False, mono=False):
    path = font_mono if mono else (font_bold if bold else font_regular)
    try:
        return ImageFont.truetype(path, size)
    except Exception:
        return ImageFont.load_default()

def draw_wp_frame(draw, width, height, active_menu="Overview"):
    # 1. Top Admin Bar (height 32)
    draw.rectangle([0, 0, width, 32], fill="#1d2327")
    draw.text((15, 7), "W", fill="#ffffff", font=get_font(15, bold=True))
    draw.text((38, 8), "Local WordPress (Stars)", fill="#f0f0f1", font=get_font(12))
    draw.text((200, 8), "+ New", fill="#c3c4c7", font=get_font(12))
    draw.text((width - 150, 8), "Howdy, Administrator", fill="#c3c4c7", font=get_font(12))

    # 2. Left Sidebar (width 170)
    draw.rectangle([0, 32, 170, height], fill="#1d2327")
    
    menus = [
        "Dashboard", "Posts", "Media", "Pages", "Comments",
        "---",
        "DPLS Schema AI",
        "  Overview", "  Pages & Posts", "  Generator", "  Integrations", "  Settings",
        "---",
        "Plugins", "Users", "Tools", "Settings"
    ]
    
    y = 44
    for m in menus:
        if m == "---":
            draw.line([(10, y + 4), (160, y + 4)], fill="#2c3338", width=1)
            y += 10
            continue
            
        is_sub = m.startswith("  ")
        clean_m = m.strip()
        is_active = (clean_m == active_menu) or (active_menu in clean_m and is_sub)
        
        if is_active:
            draw.rectangle([0, y - 4, 170, y + 18], fill="#2271b1")
            draw.text((20 if is_sub else 14, y), clean_m, fill="#ffffff", font=get_font(11, bold=True))
        else:
            color = "#a7aaad" if not is_sub else "#8c8f94"
            draw.text((20 if is_sub else 14, y), clean_m, fill=color, font=get_font(11))
        y += 22

    # 3. Main Content Background
    draw.rectangle([170, 32, width, height], fill="#f0f0f1")

# -------------------------------------------------------------
# SCREENSHOT 1: OVERVIEW DASHBOARD
# -------------------------------------------------------------
def make_screenshot_1():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Overview")

    # Header
    draw.text((195, 52), "DPLS Schema AI — Overview Dashboard", fill="#1d2327", font=get_font(22, bold=True))
    draw.text((195, 82), "Real-time structured data coverage, schema distribution, and automatic maintenance.", fill="#646970", font=get_font(13))

    # Stat Cards Row
    cards = [
        ("Total Content", "48", "Pages & Posts scanned", "#2563eb", "#dbeafe"),
        ("Active Schema", "42", "Live in Google Search", "#059669", "#d1fae5"),
        ("Awaiting Review", "4", "New content proposals", "#d97706", "#fef3c7"),
        ("Validation Clean", "100%", "Zero Schema.org syntax errors", "#7c3aed", "#ede9fe")
    ]
    
    cx = 195
    for title, val, sub, color, bg in cards:
        draw.rounded_rectangle([cx, 115, cx + 245, 205], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
        draw.rounded_rectangle([cx, 115, cx + 6, 205], radius=3, fill=color)
        draw.text((cx + 18, 127), title, fill="#646970", font=get_font(12, bold=True))
        draw.text((cx + 18, 145), val, fill=color, font=get_font(28, bold=True))
        draw.text((cx + 18, 182), sub, fill="#94a3b8", font=get_font(11))
        cx += 265

    # Main Card: Coverage Progress & Schema Distribution
    draw.rounded_rectangle([195, 225, 1255, 450], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((215, 240), "Structured Data Health & Coverage", fill="#1d2327", font=get_font(16, bold=True))
    
    # Progress Bar (87.5%)
    draw.rounded_rectangle([215, 275, 1235, 305], radius=4, fill="#e2e8f0")
    draw.rounded_rectangle([215, 275, 215 + 890, 305], radius=4, fill="#059669")
    draw.text((225, 282), "87.5% of published content has verified Schema.org markup", fill="#ffffff", font=get_font(12, bold=True))

    # Types Distribution Grid
    types = [
        ("BlogPosting", "24 posts", "#3b82f6"),
        ("Product (WooCommerce)", "12 products", "#8b5cf6"),
        ("FAQPage", "3 pages", "#10b981"),
        ("AboutPage / Organization", "2 pages", "#f59e0b"),
        ("ContactPage", "1 page", "#ec4899"),
    ]
    
    tx = 215
    for tname, tcount, tcol in types:
        draw.rounded_rectangle([tx, 330, tx + 185, 420], radius=6, fill="#f8fafc", outline="#e2e8f0", width=1)
        draw.ellipse([tx + 14, 348, tx + 26, 360], fill=tcol)
        draw.text((tx + 34, 346), tname, fill="#1e293b", font=get_font(12, bold=True))
        draw.text((tx + 14, 380), tcount, fill="#64748b", font=get_font(14, bold=True))
        tx += 205

    # Bottom Actions Bar
    draw.rounded_rectangle([195, 470, 1255, 540], radius=6, fill="#eff6ff", outline="#bfdbfe", width=1)
    draw.text((215, 488), "⚡ Quick Automated Scan: Keep schemas synchronized with recent post updates.", fill="#1e40af", font=get_font(13))
    
    # Scan Button
    draw.rounded_rectangle([1040, 482, 1235, 526], radius=4, fill="#2563eb")
    draw.text((1070, 494), "Run Batch Scan Now", fill="#ffffff", font=get_font(13, bold=True))

    return img

# -------------------------------------------------------------
# SCREENSHOT 2: POSTS TABLE WITH SCHEMA BADGES & BULK ACTIONS
# -------------------------------------------------------------
def make_screenshot_2():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Posts")

    # Header
    draw.text((195, 52), "Posts", fill="#1d2327", font=get_font(22, bold=True))
    draw.rounded_rectangle([260, 52, 330, 78], radius=3, fill="#2271b1")
    draw.text((272, 57), "Add New", fill="#ffffff", font=get_font(12))

    # Filter row
    draw.text((195, 92), "All (48) | Published (42) | Drafts (4) | Trash (2)", fill="#2271b1", font=get_font(12))

    # Bulk Actions Dropdown (HIGHLIGHTING THE NEW FEATURE)
    draw.rounded_rectangle([195, 115, 420, 145], radius=3, fill="#ffffff", outline="#8c8f94", width=1)
    draw.text((205, 122), "Generate Schema (DPLS AI) ▼", fill="#1d2327", font=get_font(12, bold=True))
    
    draw.rounded_rectangle([430, 115, 480, 145], radius=3, fill="#f6f7f7", outline="#2271b1", width=1)
    draw.text((442, 122), "Apply", fill="#2271b1", font=get_font(12))

    # Dropdown Expanded Callout
    draw.rounded_rectangle([195, 145, 420, 215], radius=3, fill="#ffffff", outline="#2271b1", width=2)
    draw.rectangle([195, 145, 420, 175], fill="#2271b1")
    draw.text((205, 152), "✓ Generate Schema (DPLS AI)", fill="#ffffff", font=get_font(12, bold=True))
    draw.text((205, 185), "  Apply Schema Proposals (DPLS)", fill="#1d2327", font=get_font(12))

    # Table Header
    draw.rectangle([195, 230, 1255, 260], fill="#f6f7f7")
    draw.line([(195, 260), (1255, 260)], fill="#c3c4c7", width=1)
    
    draw.text((215, 238), "[ ] Title", fill="#1d2327", font=get_font(12, bold=True))
    draw.text((600, 238), "Schema (DPLS)", fill="#1d2327", font=get_font(12, bold=True))
    draw.text((820, 238), "Author", fill="#1d2327", font=get_font(12, bold=True))
    draw.text((950, 238), "Categories", fill="#1d2327", font=get_font(12, bold=True))
    draw.text((1120, 238), "Date", fill="#1d2327", font=get_font(12, bold=True))

    rows = [
        ("The Complete 2026 Guide to Schema.org SEO", "BlogPosting", "#059669", "#ecfdf5", "Active (v2)"),
        ("Frequently Asked Questions About Our Platform", "FAQPage", "#059669", "#ecfdf5", "Active (v1)"),
        ("Organic Hair Serum Formula 250ml", "Product", "#059669", "#ecfdf5", "WooCommerce Active"),
        ("10 Tips for Better Web Performance and Core Web Vitals", "BlogPosting", "#d97706", "#fef3c7", "Proposal Ready"),
        ("About Our Leadership and Mission Statement", "AboutPage", "#059669", "#ecfdf5", "Active (v1)"),
        ("How to Configure Local AI Models with Ollama", "HowTo", "#d97706", "#fef3c7", "Proposal Ready"),
    ]

    ry = 265
    for title, stype, bcol, bbg, btxt in rows:
        draw.rectangle([195, ry, 1255, ry + 55], fill="#ffffff")
        draw.line([(195, ry + 55), (1255, ry + 55)], fill="#f0f0f1", width=1)
        
        draw.text((215, ry + 12), "[ ] " + title, fill="#2271b1", font=get_font(13, bold=True))
        draw.text((235, ry + 32), "Edit | Quick Edit | Trash | View | DPLS Schema", fill="#8c8f94", font=get_font(10))

        # Schema Badge
        draw.rounded_rectangle([600, ry + 15, 780, ry + 40], radius=12, fill=bbg, outline=bcol, width=1)
        draw.text((615, ry + 20), "● " + stype + " — " + btxt, fill=bcol, font=get_font(11, bold=True))

        draw.text((820, ry + 20), "Admin", fill="#50575e", font=get_font(12))
        draw.text((950, ry + 20), "SEO, Tutorials", fill="#50575e", font=get_font(12))
        draw.text((1120, ry + 20), "Published\n2026/11/18", fill="#50575e", font=get_font(10))
        ry += 58

    return img

# -------------------------------------------------------------
# SCREENSHOT 3: EDITOR META BOX WITH 1-CLICK GOOGLE TEST
# -------------------------------------------------------------
def make_screenshot_3():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Posts")

    # Post Title header
    draw.text((195, 52), "Edit Post: The Complete 2026 Guide to Schema.org SEO", fill="#1d2327", font=get_font(18, bold=True))

    # Editor Meta Box Container
    draw.rounded_rectangle([195, 95, 1255, 760], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    
    # Meta Box Header
    draw.rectangle([195, 95, 1255, 135], fill="#f8fafc")
    draw.line([(195, 135), (1255, 135)], fill="#e2e8f0", width=1)
    draw.text((215, 107), "DPL Schema AI — Structured Data", fill="#1e293b", font=get_font(14, bold=True))

    # Status Bar
    draw.text((215, 150), "Schema Status:", fill="#1e293b", font=get_font(12, bold=True))
    draw.rounded_rectangle([310, 146, 420, 172], radius=12, fill="#ecfdf5", outline="#10b981", width=1)
    draw.text((325, 151), "● Active (v2)", fill="#065f46", font=get_font(11, bold=True))

    # Action Buttons (HIGHLIGHTING GOOGLE RICH RESULTS TEST BUTTON)
    draw.rounded_rectangle([530, 146, 640, 172], radius=3, fill="#f6f7f7", outline="#8c8f94", width=1)
    draw.text((540, 151), "🔍 Analyze", fill="#2271b1", font=get_font(11))

    draw.rounded_rectangle([650, 146, 780, 172], radius=3, fill="#f6f7f7", outline="#8c8f94", width=1)
    draw.text((660, 151), "⚙ Generate Proposal", fill="#2271b1", font=get_font(11))

    draw.rounded_rectangle([790, 146, 880, 172], radius=3, fill="#f6f7f7", outline="#8c8f94", width=1)
    draw.text((800, 151), "✓ Validate", fill="#2271b1", font=get_font(11))

    # Google Rich Results Test Button (PROMINENT BLUE OUTLINE)
    draw.rounded_rectangle([890, 146, 1140, 172], radius=3, fill="#eff6ff", outline="#2563eb", width=2)
    draw.text((905, 151), "↗ Google Rich Results Test", fill="#1d4ed8", font=get_font(11, bold=True))

    # Type Select Row
    draw.text((215, 190), "Assigned Schema Type:", fill="#1e293b", font=get_font(12, bold=True))
    draw.rounded_rectangle([370, 185, 580, 215], radius=3, fill="#ffffff", outline="#8c8f94", width=1)
    draw.text((385, 193), "BlogPosting (Recommended) ▼", fill="#1d2327", font=get_font(12))

    # JSON-LD Code Box
    draw.text((215, 230), "Schema.org JSON-LD Markup:", fill="#1e293b", font=get_font(12, bold=True))
    draw.text((215, 250), "Inspect or fine-tune properties. Changes are preserved and validated automatically.", fill="#64748b", font=get_font(11))

    code_bg = [215, 275, 1235, 680]
    draw.rounded_rectangle(code_bg, radius=4, fill="#1e1e1e")
    
    json_lines = [
        '{',
        '  "@context": "https://schema.org",',
        '  "@type": "BlogPosting",',
        '  "mainEntityOfPage": "https://example.com/2026/11/schema-seo-guide/",',
        '  "headline": "The Complete 2026 Guide to Schema.org SEO",',
        '  "description": "Learn how automated structured data and Schema.org JSON-LD boosts Google rich snippets.",',
        '  "datePublished": "2026-11-18T10:30:00+00:00",',
        '  "dateModified": "2026-11-20T08:15:00+00:00",',
        '  "author": {',
        '    "@type": "Person",',
        '    "name": "Dev Pattern Labs"',
        '  },',
        '  "publisher": {',
        '    "@type": "Organization",',
        '    "name": "Stars Tech Labs",',
        '    "logo": { "@type": "ImageObject", "url": "https://example.com/logo.png" }',
        '  }',
        '}'
    ]
    
    jy = 295
    for line in json_lines:
        color = "#9cdcfe" if '"' in line else "#d4d4d4"
        if "@type" in line or "@context" in line:
            color = "#4fc1ff"
        draw.text((235, jy), line, fill=color, font=get_font(13, mono=True))
        jy += 21

    # Footer Buttons
    draw.rounded_rectangle([215, 705, 340, 740], radius=3, fill="#f6f7f7", outline="#8c8f94", width=1)
    draw.text((230, 715), "📋 Copy JSON-LD", fill="#1d2327", font=get_font(12))

    draw.rounded_rectangle([1080, 705, 1235, 740], radius=3, fill="#2271b1")
    draw.text((1110, 715), "✓ Apply Schema", fill="#ffffff", font=get_font(12, bold=True))

    return img

# -------------------------------------------------------------
# SCREENSHOT 4: SEO CONFLICT DETECTOR & COMPATIBILITY
# -------------------------------------------------------------
def make_screenshot_4():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Integrations")

    # Header
    draw.text((195, 52), "DPLS Schema AI — Integrations & SEO Compatibility", fill="#1d2327", font=get_font(22, bold=True))
    draw.text((195, 82), "Configure seamless coexistence with general SEO plugins, WooCommerce, and Page Builders.", fill="#646970", font=get_font(13))

    # SEO CONFLICT DETECTOR WARNING BANNER (NEW FEATURE HIGHLIGHT)
    draw.rounded_rectangle([195, 115, 1255, 230], radius=6, fill="#ffffff", outline="#f59e0b", width=2)
    draw.rounded_rectangle([195, 115, 203, 230], radius=3, fill="#f59e0b")
    
    draw.text((220, 130), "🛡️ SEO Plugin Compatibility Alert — Rank Math SEO Detected", fill="#b45309", font=get_font(15, bold=True))
    draw.text((220, 155), "We detected Rank Math SEO active on your site. Having multiple plugins outputting competing Schema definitions\ncan cause duplicate entity warnings in Google Search Console.", fill="#475569", font=get_font(13))

    # Banner Buttons
    draw.rounded_rectangle([220, 195, 560, 225], radius=3, fill="#059669")
    draw.text((235, 202), "✓ Auto-Suppress Third-Party Schema (Recommended)", fill="#ffffff", font=get_font(11, bold=True))

    draw.rounded_rectangle([575, 195, 740, 225], radius=3, fill="#f8fafc", outline="#cbd5e1", width=1)
    draw.text((590, 202), "Manage Settings", fill="#334155", font=get_font(11))

    # Card 1: WooCommerce
    draw.rounded_rectangle([195, 250, 715, 500], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((215, 270), "🛍️ WooCommerce E-Commerce Integration", fill="#1e293b", font=get_font(15, bold=True))
    draw.text((215, 295), "Status: Active (WooCommerce 8.x detected)", fill="#059669", font=get_font(12, bold=True))
    draw.text((215, 325), "• Genuine SKU, price, stock availability extraction\n• Real customer review aggregation\n• Suppress basic WooCommerce schema to prevent duplicates", fill="#64748b", font=get_font(12))

    draw.rounded_rectangle([215, 430, 480, 465], radius=3, fill="#eff6ff", outline="#3b82f6", width=1)
    draw.text((230, 440), "[✓] Suppress core WooCommerce schema", fill="#1e40af", font=get_font(11, bold=True))

    # Card 2: Elementor
    draw.rounded_rectangle([735, 250, 1255, 500], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((755, 270), "🧩 Page Builder Adapters (Elementor)", fill="#1e293b", font=get_font(15, bold=True))
    draw.text((755, 295), "Status: Active (Elementor 3.25+ detected)", fill="#059669", font=get_font(12, bold=True))
    draw.text((755, 325), "• Deep widget tree inspection (_elementor_data)\n• Heading and text node parsing without executing shortcodes\n• Safe fallback when editor mode is idle", fill="#64748b", font=get_font(12))

    draw.rounded_rectangle([755, 430, 1020, 465], radius=3, fill="#eff6ff", outline="#3b82f6", width=1)
    draw.text((770, 440), "[✓] Enable Elementor tree scanner", fill="#1e40af", font=get_font(11, bold=True))

    # Card 3: Local AI (Ollama)
    draw.rounded_rectangle([195, 520, 1255, 750], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((215, 540), "🤖 Optional Local AI Inference (Ollama)", fill="#1e293b", font=get_font(15, bold=True))
    draw.text((215, 565), "Connect to a self-hosted Ollama server on localhost (127.0.0.1:11434).", fill="#64748b", font=get_font(12))
    draw.text((215, 595), "Endpoint: http://localhost:11434  |  Model: llama3:latest  |  Zero Cloud Transmission", fill="#334155", font=get_font(12, bold=True))
    
    draw.rounded_rectangle([215, 635, 360, 670], radius=3, fill="#2563eb")
    draw.text((235, 645), "Test AI Connection", fill="#ffffff", font=get_font(12, bold=True))

    return img

print("Generating screenshots...")
s1 = make_screenshot_1()
s1.save(os.path.join(OUTPUT_DIR, "screenshot-1.png"))
s1.save(os.path.join(LOCAL_ASSETS_DIR, "screenshot-1.png"))
print("Saved screenshot-1.png")

s2 = make_screenshot_2()
s2.save(os.path.join(OUTPUT_DIR, "screenshot-2.png"))
s2.save(os.path.join(LOCAL_ASSETS_DIR, "screenshot-2.png"))
print("Saved screenshot-2.png")

s3 = make_screenshot_3()
s3.save(os.path.join(OUTPUT_DIR, "screenshot-3.png"))
s3.save(os.path.join(LOCAL_ASSETS_DIR, "screenshot-3.png"))
print("Saved screenshot-3.png")

s4 = make_screenshot_4()
s4.save(os.path.join(OUTPUT_DIR, "screenshot-4.png"))
s4.save(os.path.join(LOCAL_ASSETS_DIR, "screenshot-4.png"))
print("Saved screenshot-4.png")

print("All screenshots generated successfully in SVN and local plugin directories!")
