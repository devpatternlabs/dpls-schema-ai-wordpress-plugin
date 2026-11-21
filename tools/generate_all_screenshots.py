"""
Generate all 8 High-Fidelity WordPress Admin Dashboard Screenshots (1280x800)
matching exactly the 8 numbered descriptions in readme.txt under == Screenshots ==:

1. screenshot-1.png: Overview Dashboard (Real-time coverage & metrics)
2. screenshot-2.png: Content Table (Posts list with Schema badges & Bulk Actions)
3. screenshot-3.png: Post Editor Meta Box (1-click Google Rich Results Test)
4. screenshot-4.png: Schema Generator & JSON-LD Viewer (Interactive composer & AI comparison)
5. screenshot-5.png: System-wide Validation Hub (Audit checks & property warnings)
6. screenshot-6.png: Verified Organization Settings (Legal entity identity & logo)
7. screenshot-7.png: WooCommerce & SEO Integrations (E-commerce mapping & Rank Math conflict alert)
8. screenshot-8.png: Data Management & Backup Portability (JSON Export/Import & Safety)
"""

import os
from PIL import Image, ImageDraw, ImageFont

OUTPUT_DIR = r"C:\xampp\htdocs\dpls-schema-ai-svn\assets"
LOCAL_ASSETS_DIR = r"C:\xampp\htdocs\DPLSplugin\assets"
os.makedirs(OUTPUT_DIR, exist_ok=True)
os.makedirs(LOCAL_ASSETS_DIR, exist_ok=True)

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
        "  Overview", "  Pages & Posts", "  Generator", "  Validation", "  Automation", "  History", "  Integrations", "  Settings",
        "---",
        "Plugins", "Users", "Tools", "Settings"
    ]
    
    y = 44
    for m in menus:
        if m == "---":
            draw.line([(10, y + 4), (160, y + 4)], fill="#2c3338", width=1)
            y += 8
            continue
            
        is_sub = m.startswith("  ")
        clean_m = m.strip()
        is_active = (clean_m == active_menu) or (active_menu in clean_m and is_sub)
        
        if is_active:
            draw.rectangle([0, y - 3, 170, y + 17], fill="#2271b1")
            draw.text((20 if is_sub else 14, y), clean_m, fill="#ffffff", font=get_font(11, bold=True))
        else:
            color = "#a7aaad" if not is_sub else "#8c8f94"
            draw.text((20 if is_sub else 14, y), clean_m, fill=color, font=get_font(11))
        y += 20

    # 3. Main Content Background
    draw.rectangle([170, 32, width, height], fill="#f0f0f1")

def save_both(img, filename):
    img.save(os.path.join(OUTPUT_DIR, filename))
    img.save(os.path.join(LOCAL_ASSETS_DIR, filename))
    print(f"Saved {filename}")

# -------------------------------------------------------------
# 1. SCREENSHOT 1: OVERVIEW DASHBOARD
# -------------------------------------------------------------
def make_screenshot_1():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Overview")

    draw.text((195, 52), "DPLS Schema AI — Overview Dashboard", fill="#1d2327", font=get_font(22, bold=True))
    draw.text((195, 82), "Real-time structured data coverage, schema distribution, and automated maintenance.", fill="#646970", font=get_font(13))

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

    draw.rounded_rectangle([195, 225, 1255, 450], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((215, 240), "Structured Data Health & Coverage", fill="#1d2327", font=get_font(16, bold=True))
    
    draw.rounded_rectangle([215, 275, 1235, 305], radius=4, fill="#e2e8f0")
    draw.rounded_rectangle([215, 275, 215 + 890, 305], radius=4, fill="#059669")
    draw.text((225, 282), "87.5% of published content has verified Schema.org markup", fill="#ffffff", font=get_font(12, bold=True))

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

    draw.rounded_rectangle([195, 470, 1255, 540], radius=6, fill="#eff6ff", outline="#bfdbfe", width=1)
    draw.text((215, 488), "⚡ Quick Automated Scan: Keep schemas synchronized with recent post updates.", fill="#1e40af", font=get_font(13))
    draw.rounded_rectangle([1040, 482, 1235, 526], radius=4, fill="#2563eb")
    draw.text((1070, 494), "Run Batch Scan Now", fill="#ffffff", font=get_font(13, bold=True))

    return img

# -------------------------------------------------------------
# 2. SCREENSHOT 2: POSTS TABLE WITH SCHEMA BADGES & BULK ACTIONS
# -------------------------------------------------------------
def make_screenshot_2():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Posts")

    draw.text((195, 52), "Posts", fill="#1d2327", font=get_font(22, bold=True))
    draw.rounded_rectangle([260, 52, 330, 78], radius=3, fill="#2271b1")
    draw.text((272, 57), "Add New", fill="#ffffff", font=get_font(12))

    draw.text((195, 92), "All (48) | Published (42) | Drafts (4) | Trash (2)", fill="#2271b1", font=get_font(12))

    draw.rounded_rectangle([195, 115, 420, 145], radius=3, fill="#ffffff", outline="#8c8f94", width=1)
    draw.text((205, 122), "Generate Schema (DPLS AI) ▼", fill="#1d2327", font=get_font(12, bold=True))
    
    draw.rounded_rectangle([430, 115, 480, 145], radius=3, fill="#f6f7f7", outline="#2271b1", width=1)
    draw.text((442, 122), "Apply", fill="#2271b1", font=get_font(12))

    draw.rounded_rectangle([195, 145, 420, 215], radius=3, fill="#ffffff", outline="#2271b1", width=2)
    draw.rectangle([195, 145, 420, 175], fill="#2271b1")
    draw.text((205, 152), "✓ Generate Schema (DPLS AI)", fill="#ffffff", font=get_font(12, bold=True))
    draw.text((205, 185), "  Apply Schema Proposals (DPLS)", fill="#1d2327", font=get_font(12))

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

        draw.rounded_rectangle([600, ry + 15, 780, ry + 40], radius=12, fill=bbg, outline=bcol, width=1)
        draw.text((615, ry + 20), "● " + stype + " — " + btxt, fill=bcol, font=get_font(11, bold=True))

        draw.text((820, ry + 20), "Admin", fill="#50575e", font=get_font(12))
        draw.text((950, ry + 20), "SEO, Tutorials", fill="#50575e", font=get_font(12))
        draw.text((1120, ry + 20), "Published\n2026/11/18", fill="#50575e", font=get_font(10))
        ry += 58

    return img

# -------------------------------------------------------------
# 3. SCREENSHOT 3: EDITOR META BOX WITH 1-CLICK GOOGLE TEST
# -------------------------------------------------------------
def make_screenshot_3():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Posts")

    draw.text((195, 52), "Edit Post: The Complete 2026 Guide to Schema.org SEO", fill="#1d2327", font=get_font(18, bold=True))

    draw.rounded_rectangle([195, 95, 1255, 760], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.rectangle([195, 95, 1255, 135], fill="#f8fafc")
    draw.line([(195, 135), (1255, 135)], fill="#e2e8f0", width=1)
    draw.text((215, 107), "DPL Schema AI — Structured Data", fill="#1e293b", font=get_font(14, bold=True))

    draw.text((215, 150), "Schema Status:", fill="#1e293b", font=get_font(12, bold=True))
    draw.rounded_rectangle([310, 146, 420, 172], radius=12, fill="#ecfdf5", outline="#10b981", width=1)
    draw.text((325, 151), "● Active (v2)", fill="#065f46", font=get_font(11, bold=True))

    draw.rounded_rectangle([530, 146, 640, 172], radius=3, fill="#f6f7f7", outline="#8c8f94", width=1)
    draw.text((540, 151), "🔍 Analyze", fill="#2271b1", font=get_font(11))

    draw.rounded_rectangle([650, 146, 780, 172], radius=3, fill="#f6f7f7", outline="#8c8f94", width=1)
    draw.text((660, 151), "⚙ Generate Proposal", fill="#2271b1", font=get_font(11))

    draw.rounded_rectangle([790, 146, 880, 172], radius=3, fill="#f6f7f7", outline="#8c8f94", width=1)
    draw.text((800, 151), "✓ Validate", fill="#2271b1", font=get_font(11))

    # Google Rich Results Test Button
    draw.rounded_rectangle([890, 146, 1140, 172], radius=3, fill="#eff6ff", outline="#2563eb", width=2)
    draw.text((905, 151), "↗ Google Rich Results Test", fill="#1d4ed8", font=get_font(11, bold=True))

    draw.text((215, 190), "Assigned Schema Type:", fill="#1e293b", font=get_font(12, bold=True))
    draw.rounded_rectangle([370, 185, 580, 215], radius=3, fill="#ffffff", outline="#8c8f94", width=1)
    draw.text((385, 193), "BlogPosting (Recommended) ▼", fill="#1d2327", font=get_font(12))

    code_bg = [215, 235, 1235, 690]
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
    jy = 250
    for line in json_lines:
        color = "#9cdcfe" if '"' in line else "#d4d4d4"
        if "@type" in line or "@context" in line:
            color = "#4fc1ff"
        draw.text((235, jy), line, fill=color, font=get_font(13, mono=True))
        jy += 21

    draw.rounded_rectangle([215, 710, 340, 745], radius=3, fill="#f6f7f7", outline="#8c8f94", width=1)
    draw.text((230, 720), "📋 Copy JSON-LD", fill="#1d2327", font=get_font(12))

    draw.rounded_rectangle([1080, 710, 1235, 745], radius=3, fill="#2271b1")
    draw.text((1110, 720), "✓ Apply Schema", fill="#ffffff", font=get_font(12, bold=True))

    return img

# -------------------------------------------------------------
# 4. SCREENSHOT 4: SCHEMA GENERATOR & AI COMPARISON
# -------------------------------------------------------------
def make_screenshot_4():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Generator")

    draw.text((195, 52), "DPLS Schema AI — Schema Generator & JSON-LD Composer", fill="#1d2327", font=get_font(22, bold=True))
    draw.text((195, 82), "Compose structured data proposals and compare rule-based output against local AI models.", fill="#646970", font=get_font(13))

    # Left Column (Selection & AI Panel)
    draw.rounded_rectangle([195, 115, 600, 750], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((215, 135), "1. Target Content Selection", fill="#1e293b", font=get_font(15, bold=True))
    draw.rounded_rectangle([215, 160, 580, 190], radius=3, fill="#ffffff", outline="#8c8f94", width=1)
    draw.text((225, 168), "Frequently Asked Questions (Page #42) ▼", fill="#1e293b", font=get_font(12))

    draw.text((215, 210), "2. Desired Schema.org Type", fill="#1e293b", font=get_font(15, bold=True))
    draw.rounded_rectangle([215, 235, 580, 265], radius=3, fill="#ffffff", outline="#8c8f94", width=1)
    draw.text((225, 243), "FAQPage (Frequently Asked Questions) ▼", fill="#1e293b", font=get_font(12))

    draw.rounded_rectangle([215, 290, 580, 325], radius=4, fill="#2271b1")
    draw.text((320, 300), "⚙ Generate JSON-LD Proposal", fill="#ffffff", font=get_font(13, bold=True))

    # AI Analysis Box
    draw.rounded_rectangle([215, 350, 580, 550], radius=6, fill="#f0fdf4", outline="#86efac", width=1)
    draw.text((230, 365), "🤖 Local AI Recommendation (Ollama: llama3)", fill="#166534", font=get_font(13, bold=True))
    draw.text((230, 395), "• Recommended Type: FAQPage (Confidence: 94%)\n• Detected 6 structured Q&A pairs in content\n• Entity grounding: 100% verified against source text\n• Anti-hallucination check: 0 unverified properties", fill="#15803d", font=get_font(12))

    # Right Column (JSON-LD Preview & Test)
    draw.rounded_rectangle([620, 115, 1255, 750], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((640, 135), "Generated JSON-LD Structured Data", fill="#1e293b", font=get_font(15, bold=True))

    code_bg = [640, 170, 1235, 680]
    draw.rounded_rectangle(code_bg, radius=4, fill="#1e1e1e")
    json_lines = [
        '{',
        '  "@context": "https://schema.org",',
        '  "@type": "FAQPage",',
        '  "mainEntity": [',
        '    {',
        '      "@type": "Question",',
        '      "name": "How does DPLS Schema AI prevent duplicate markup?",',
        '      "acceptedAnswer": {',
        '        "@type": "Answer",',
        '        "text": "DPLS Schema AI auto-detects competing SEO plugins and suppresses duplicate tags."',
        '      }',
        '    }',
        '  ]',
        '}'
    ]
    jy = 190
    for line in json_lines:
        color = "#4fc1ff" if "@type" in line else "#9cdcfe"
        draw.text((660, jy), line, fill=color, font=get_font(13, mono=True))
        jy += 22

    draw.rounded_rectangle([640, 700, 770, 735], radius=3, fill="#2271b1")
    draw.text((665, 710), "✓ Apply Schema", fill="#ffffff", font=get_font(12, bold=True))

    draw.rounded_rectangle([785, 700, 970, 735], radius=3, fill="#eff6ff", outline="#2563eb", width=1)
    draw.text((800, 710), "↗ Test with Google", fill="#1d4ed8", font=get_font(12, bold=True))

    return img

# -------------------------------------------------------------
# 5. SCREENSHOT 5: SYSTEM-WIDE VALIDATION HUB
# -------------------------------------------------------------
def make_screenshot_5():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Validation")

    draw.text((195, 52), "DPLS Schema AI — System-wide Validation Hub", fill="#1d2327", font=get_font(22, bold=True))
    draw.text((195, 82), "Automated Schema.org syntax verification, missing required fields, and Google rich results compliance.", fill="#646970", font=get_font(13))

    # Audit Metrics
    cards = [
        ("Audited Items", "48", "All published pages & posts", "#2563eb"),
        ("100% Valid Syntax", "48", "Zero JSON parse errors", "#059669"),
        ("Google Ready", "46", "Meets rich snippet criteria", "#059669"),
        ("Recommendations", "2", "Missing recommended fields", "#d97706"),
    ]
    cx = 195
    for title, val, sub, color in cards:
        draw.rounded_rectangle([cx, 115, cx + 245, 195], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
        draw.text((cx + 18, 127), title, fill="#646970", font=get_font(12, bold=True))
        draw.text((cx + 18, 145), val, fill=color, font=get_font(26, bold=True))
        draw.text((cx + 18, 175), sub, fill="#94a3b8", font=get_font(11))
        cx += 265

    # Audit Table
    draw.rounded_rectangle([195, 215, 1255, 740], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((215, 235), "Recent Validation Results", fill="#1e293b", font=get_font(16, bold=True))
    
    draw.rectangle([215, 270, 1235, 300], fill="#f8fafc")
    draw.text((230, 278), "Page / Post Title", fill="#1e293b", font=get_font(12, bold=True))
    draw.text((650, 278), "Schema Type", fill="#1e293b", font=get_font(12, bold=True))
    draw.text((820, 278), "Validation Status", fill="#1e293b", font=get_font(12, bold=True))
    draw.text((1050, 278), "Actions", fill="#1e293b", font=get_font(12, bold=True))

    vrows = [
        ("The Complete 2026 Guide to Schema.org SEO", "BlogPosting", "✓ Valid (100% compliant)", "#059669"),
        ("Frequently Asked Questions About Our Platform", "FAQPage", "✓ Valid (Q&A syntax clean)", "#059669"),
        ("Organic Hair Serum Formula 250ml", "Product", "✓ Valid (Real price & offer mapped)", "#059669"),
        ("About Our Leadership and Mission Statement", "AboutPage", "✓ Valid (Organization mapped)", "#059669"),
        ("10 Tips for Better Web Performance", "BlogPosting", "⚠ Recommendation: Add featured image", "#d97706"),
        ("Contact Our Customer Support Team", "ContactPage", "✓ Valid (Phone & email verified)", "#059669"),
    ]
    vy = 305
    for title, stype, vstat, vcol in vrows:
        draw.line([(215, vy + 45), (1235, vy + 45)], fill="#f1f5f9", width=1)
        draw.text((230, vy + 12), title, fill="#2271b1", font=get_font(12, bold=True))
        draw.text((650, vy + 12), stype, fill="#475569", font=get_font(12))
        draw.text((820, vy + 12), vstat, fill=vcol, font=get_font(12, bold=True))
        draw.text((1050, vy + 12), "Re-validate | Inspect", fill="#2271b1", font=get_font(12))
        vy += 48

    return img

# -------------------------------------------------------------
# 6. SCREENSHOT 6: VERIFIED ORGANIZATION SETTINGS
# -------------------------------------------------------------
def make_screenshot_6():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Settings")

    draw.text((195, 52), "DPLS Schema AI — Organization & Publisher Settings", fill="#1d2327", font=get_font(22, bold=True))
    draw.text((195, 82), "Configure verified legal identity, logo, official social profiles, and contact endpoints for Google Knowledge Graph.", fill="#646970", font=get_font(13))

    draw.rounded_rectangle([195, 115, 1255, 740], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    
    draw.text((220, 140), "Organization Identity (Knowledge Graph)", fill="#1e293b", font=get_font(16, bold=True))

    fields = [
        ("Legal Entity Name:", "Dev Pattern Labs"),
        ("Official Website URL:", "https://devpatternlabs.com"),
        ("Organization Logo URL:", "https://devpatternlabs.com/wp-content/uploads/logo.png"),
        ("Entity Classification:", "Corporation / Organization"),
        ("Primary Contact Email:", "support@devpatternlabs.com"),
        ("Customer Support Phone:", "+1 (800) 555-0199"),
        ("Social Profiles (sameAs):", "https://twitter.com/devpatternlabs, https://linkedin.com/company/devpatternlabs"),
    ]
    fy = 175
    for label, val in fields:
        draw.text((220, fy), label, fill="#1e293b", font=get_font(12, bold=True))
        draw.rounded_rectangle([420, fy - 6, 950, fy + 26], radius=3, fill="#f8fafc", outline="#cbd5e1", width=1)
        draw.text((435, fy), val, fill="#334155", font=get_font(12))
        fy += 48

    draw.line([(220, fy + 10), (1230, fy + 10)], fill="#e2e8f0", width=1)
    fy += 25

    draw.text((220, fy), "Enabled Post Types:", fill="#1e293b", font=get_font(12, bold=True))
    draw.text((420, fy), "[✓] Posts (post)    [✓] Pages (page)    [✓] Products (product)", fill="#1e293b", font=get_font(12))
    
    fy += 45
    draw.text((220, fy), "Data Preservation:", fill="#1e293b", font=get_font(12, bold=True))
    draw.text((420, fy), "[✓] Preserve all Schema data and configurations on plugin deactivation & uninstall", fill="#059669", font=get_font(12, bold=True))

    draw.rounded_rectangle([220, 680, 400, 715], radius=3, fill="#2271b1")
    draw.text((260, 690), "Save Organization Settings", fill="#ffffff", font=get_font(12, bold=True))

    return img

# -------------------------------------------------------------
# 7. SCREENSHOT 7: WOOCOMMERCE & SEO INTEGRATIONS
# -------------------------------------------------------------
def make_screenshot_7():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Integrations")

    draw.text((195, 52), "DPLS Schema AI — Integrations & SEO Compatibility", fill="#1d2327", font=get_font(22, bold=True))
    draw.text((195, 82), "Prevent duplicate structured data from general SEO plugins and connect E-Commerce & Page Builders.", fill="#646970", font=get_font(13))

    # SEO Conflict Notice
    draw.rounded_rectangle([195, 115, 1255, 230], radius=6, fill="#ffffff", outline="#f59e0b", width=2)
    draw.rounded_rectangle([195, 115, 203, 230], radius=3, fill="#f59e0b")
    draw.text((220, 130), "🛡️ SEO Plugin Compatibility Alert — Rank Math SEO Detected", fill="#b45309", font=get_font(15, bold=True))
    draw.text((220, 155), "We detected Rank Math SEO active on your site. Having multiple plugins outputting competing Schema definitions\ncan cause duplicate entity warnings in Google Search Console.", fill="#475569", font=get_font(13))
    
    draw.rounded_rectangle([220, 195, 560, 225], radius=3, fill="#059669")
    draw.text((235, 202), "✓ Auto-Suppress Third-Party Schema (Recommended)", fill="#ffffff", font=get_font(11, bold=True))

    # WooCommerce Card
    draw.rounded_rectangle([195, 250, 715, 480], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((215, 270), "🛍️ WooCommerce E-Commerce Integration", fill="#1e293b", font=get_font(15, bold=True))
    draw.text((215, 295), "Status: Active (WooCommerce 8.x detected)", fill="#059669", font=get_font(12, bold=True))
    draw.text((215, 325), "• Genuine SKU, price, stock availability extraction\n• Real customer review aggregation\n• Suppress basic WooCommerce schema to prevent duplicates", fill="#64748b", font=get_font(12))
    draw.rounded_rectangle([215, 420, 480, 455], radius=3, fill="#eff6ff", outline="#3b82f6", width=1)
    draw.text((230, 430), "[✓] Suppress core WooCommerce schema", fill="#1e40af", font=get_font(11, bold=True))

    # Elementor Card
    draw.rounded_rectangle([735, 250, 1255, 480], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((755, 270), "🧩 Page Builder Adapters (Elementor)", fill="#1e293b", font=get_font(15, bold=True))
    draw.text((755, 295), "Status: Active (Elementor 3.25+ detected)", fill="#059669", font=get_font(12, bold=True))
    draw.text((755, 325), "• Deep widget tree inspection (_elementor_data)\n• Heading and text node parsing without executing shortcodes\n• Safe fallback when editor mode is idle", fill="#64748b", font=get_font(12))
    draw.rounded_rectangle([755, 420, 1020, 455], radius=3, fill="#eff6ff", outline="#3b82f6", width=1)
    draw.text((770, 430), "[✓] Enable Elementor tree scanner", fill="#1e40af", font=get_font(11, bold=True))

    # Local AI Card
    draw.rounded_rectangle([195, 500, 1255, 740], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((215, 520), "🤖 Optional Local AI Inference (Ollama)", fill="#1e293b", font=get_font(15, bold=True))
    draw.text((215, 545), "Connect to a self-hosted Ollama server on localhost (127.0.0.1:11434).", fill="#64748b", font=get_font(12))
    draw.text((215, 575), "Endpoint: http://localhost:11434  |  Model: llama3:latest  |  Zero Cloud Transmission", fill="#334155", font=get_font(12, bold=True))
    draw.rounded_rectangle([215, 615, 360, 650], radius=3, fill="#2563eb")
    draw.text((235, 625), "Test AI Connection", fill="#ffffff", font=get_font(12, bold=True))

    return img

# -------------------------------------------------------------
# 8. SCREENSHOT 8: DATA MANAGEMENT & BACKUP PORTABILITY
# -------------------------------------------------------------
def make_screenshot_8():
    w, h = 1280, 800
    img = Image.new("RGB", (w, h), "#f0f0f1")
    draw = ImageDraw.Draw(img)
    draw_wp_frame(draw, w, h, active_menu="Settings")

    draw.text((195, 52), "DPLS Schema AI — Data Management & Backup Portability", fill="#1d2327", font=get_font(22, bold=True))
    draw.text((195, 82), "Export and import plugin settings and structured data records with pre-import validation and overwrite protection.", fill="#646970", font=get_font(13))

    # Export Panel
    draw.rounded_rectangle([195, 115, 700, 450], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((220, 135), "📤 Export Schema Configurations", fill="#1e293b", font=get_font(16, bold=True))
    draw.text((220, 165), "Download complete JSON backups of your settings or generated Schema records.", fill="#64748b", font=get_font(12))
    
    draw.rounded_rectangle([220, 210, 440, 250], radius=4, fill="#2271b1")
    draw.text((245, 222), "Export Settings (JSON)", fill="#ffffff", font=get_font(12, bold=True))

    draw.rounded_rectangle([455, 210, 680, 250], radius=4, fill="#2271b1")
    draw.text((475, 222), "Export Schemas (JSON)", fill="#ffffff", font=get_font(12, bold=True))

    # Import Panel
    draw.rounded_rectangle([720, 115, 1255, 450], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((745, 135), "📥 Import Configuration", fill="#1e293b", font=get_font(16, bold=True))
    draw.text((745, 165), "Select a valid DPLS Schema export file to inspect and restore.", fill="#64748b", font=get_font(12))

    draw.rounded_rectangle([745, 210, 1220, 250], radius=4, fill="#f8fafc", outline="#cbd5e1", width=1)
    draw.text((760, 222), "Choose file: dpls-schema-backup-2026.json", fill="#475569", font=get_font(12))

    draw.rounded_rectangle([745, 270, 950, 305], radius=4, fill="#059669")
    draw.text((770, 280), "Preview & Validate Import", fill="#ffffff", font=get_font(12, bold=True))

    # Privacy Guarantee Card
    draw.rounded_rectangle([195, 475, 1255, 740], radius=6, fill="#ffffff", outline="#c3c4c7", width=1)
    draw.text((220, 495), "🛡️ Privacy & Non-Destructive Storage Policy", fill="#1e293b", font=get_font(16, bold=True))
    draw.text((220, 525), "• 100% Local Storage: All settings and schema versions are stored strictly in standard WordPress wp_options and wp_postmeta tables.\n• Non-Destructive Uninstall: By default, deactivating or deleting DPLS Schema AI preserves all your metadata and custom schema edits.\n• Zero External Telemetry: No tracking scripts, no remote CDN assets, and zero analytics beacons are loaded.", fill="#475569", font=get_font(13))

    return img

print("Generating all 8 screenshots...")
save_both(make_screenshot_1(), "screenshot-1.png")
save_both(make_screenshot_2(), "screenshot-2.png")
save_both(make_screenshot_3(), "screenshot-3.png")
save_both(make_screenshot_4(), "screenshot-4.png")
save_both(make_screenshot_5(), "screenshot-5.png")
save_both(make_screenshot_6(), "screenshot-6.png")
save_both(make_screenshot_7(), "screenshot-7.png")
save_both(make_screenshot_8(), "screenshot-8.png")

print("All 8 screenshots successfully created!")
