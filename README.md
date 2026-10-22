# DPLS Schema AI — WordPress Structured Data & Schema.org JSON-LD

[![Plugin Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](dpls-schema-ai.php)
[![WordPress Version](https://img.shields.io/badge/WordPress-6.0%2B-21759b.svg)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777bb4.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)](LICENSE)
[![Unit Tests](https://img.shields.io/badge/tests-110%2F110%20passing-brightgreen.svg)](tests/test_plugin.php)
[![PCP Compliance](https://img.shields.io/badge/Plugin%20Check-100%25%20Clean-success.svg)](tests/test_pcp_package.php)

**DPLS Schema AI** is a production-grade, privacy-first WordPress plugin developed by **Dev Pattern Labs**. It automatically analyzes page and post content, classifies content into appropriate Schema.org structured data, generates standards-compliant JSON-LD, and automatically maintains schema consistency as your site evolves.

---

## 🌟 Key Highlights

- **🔒 Privacy First & Zero External Dependencies**: 100% operational offline without any external cloud API, paid service, or remote JavaScript CDN. Your content never leaves your server without explicit consent.
- **⚡ Dual-Engine Architecture**:
  - **Deterministic Rule Engine (Default)**: Fast, reliable, heuristic classification and structured data generation across 14+ Schema.org types.
  - **Optional Local AI (Ollama)**: Local self-hosted LLM analysis with strict schema grounding, anti-hallucination verification, and multi-layer SSRF defenses.
- **🛍️ Deep WooCommerce & Page Builder Support**: Native adapters for WooCommerce (SKU, real pricing, stock status, reviews) and Elementor Page Builder.
- **🛡️ Governance & Versioned Rollback**: Schema proposals, side-by-side preview, manual property locking, and revision history with one-click restore.
- **✅ WordPress.org Standards Compliant**: Validated against the official WordPress Plugin Check (PCP) tool with zero errors and zero warnings.

---

## 📋 Supported Schema.org Types

| Type | Description & Auto-Detection Triggers |
| :--- | :--- |
| `Article` / `BlogPosting` | Standard posts, editorial articles, and news publications. Includes author, publisher, and timestamps. |
| `NewsArticle` | Time-sensitive news coverage with dateline and publishing entity metadata. |
| `WebPage` | General site pages, landing pages, and safe fallback schema. |
| `AboutPage` | Company story, team information, mission statements, and executive profiles. |
| `ContactPage` | Contact details, office addresses, inquiry forms, and phone numbers. |
| `FAQPage` | Frequently asked questions detected from headings, definition lists, or accordion structures. |
| `HowTo` | Step-by-step guides, tutorials, and instructions with structured steps and tools. |
| `Recipe` | Culinary recipes with ingredients, preparation/cook time, and instructions. |
| `Product` | E-commerce items with real offers, price, currency, SKU, availability, and verified ratings. |
| `Event` | Scheduled events with date/time, location, and attendance modes. |
| `LocalBusiness` | Physical stores, service providers, operating hours, and geo-coordinates. |
| `Organization` | Corporate identity, verified logo, social profiles, and founding info. |
| `BreadcrumbList` | Hierarchical navigational trails mapped automatically from page hierarchy or taxonomies. |

---

## 🚀 Installation

### From ZIP Release
1. Download the latest release from the [Releases](https://github.com/devpatternlabs/dpls-schema-ai-wordpress-plugin/releases) page or build via `tools/package-release.php`.
2. In your WordPress admin dashboard, navigate to **Plugins → Add New → Upload Plugin**.
3. Choose `dpls-schema-ai.zip` and click **Install Now**.
4. Activate the plugin.

### Manual Installation
1. Clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/devpatternlabs/dpls-schema-ai-wordpress-plugin.git dpls-schema-ai
   ```
2. Activate **DPLS Schema AI** in your WordPress admin panel under **Plugins**.

---

## ⚙️ Configuration & Features

### 1. General Settings
- **Post Types**: Enable or disable scanning and schema generation per public post type (Posts, Pages, Products, Custom Post Types).
- **Default Behavior**: Choose between auto-applying generated schemas or queueing them for manual editorial review.
- **Update Policy**:
  - `Manual Only`: Schemas are only updated when manually triggered.
  - `Safe (Default)`: Updates existing schema on post save only if the detected schema type remains consistent.
  - `Automatic`: Always recalculates and updates schema on content change.

### 2. Optional Local AI (Ollama)
DPLS Schema AI supports connecting to a self-hosted [Ollama](https://ollama.ai) instance running on your server or local network:
- **Zero Cloud Leakage**: Prompts and content are processed entirely on your local machine (`http://127.0.0.1:11434`).
- **SSRF Defense Suite**: Prohibits requests to AWS/GCP cloud metadata endpoints (`169.254.169.254`), non-HTTP protocols, and user-specified restricted hosts.
- **Anti-Hallucination Guard**: Cross-verifies all AI-suggested properties against the actual raw post text. Unverified entities are stripped automatically.
- **Graceful Deterministic Fallback**: If the Ollama server is offline, times out, or returns invalid JSON, the plugin seamlessly falls back to the deterministic rule-based engine.

### 3. Editor Meta Box & Workflow
Every supported post includes a dedicated **DPLS Schema AI** panel in the WordPress block and classic editors:
- **Scan & Propose**: Re-scan content and generate an updated schema recommendation.
- **Interactive JSON-LD Editor**: Edit properties before approval.
- **Property Locking**: Lock specific fields (e.g., custom headline or target audience) so automated updates will never overwrite them.
- **History & Rollback**: View timestamped revisions and restore earlier schema versions with a single click.

---

## 🧪 Testing & Verification

DPLS Schema AI includes a self-contained test suite that executes in isolated CLI environments:

### Comprehensive Unit & Integration Tests (110 Tests)
```bash
php tests/test_plugin.php
```
*Tests content scanning, heuristic classification, proposal reviews, JSON-LD output, WooCommerce integration, Elementor compatibility, SSRF defenses, and Ollama AI fallbacks.*

### WordPress Plugin Check (PCP) Simulation (29 Checks)
```bash
php tests/test_pcp_package.php
```
*Validates compliance against WordPress.org guidelines including file naming, security headers, nonce usage, capability checks, text domain consistency, and database sanitization.*

### Build Production Release Package
```bash
php tools/package-release.php
```
*Generates a clean, distribution-ready ZIP archive in `dist/dpls-schema-ai.zip` formatted with standard Unix forward slashes.*

---

## 📂 Project Structure

```
dpls-schema-ai/
├── assets/                    # Admin styles and vanilla JavaScript
│   ├── css/admin.css
│   └── js/admin.js
├── includes/                  # Core architectural modules
│   ├── Admin/                 # Admin menu, settings UI, and meta boxes
│   ├── AI/                    # AI manager, Ollama provider, and SSRF validator
│   ├── Generator/             # Rule engine, SchemaRegistry, and entity builders
│   ├── Integrations/          # WooCommerce and Elementor adapters
│   ├── Output/                # Frontend JSON-LD injection and deduplication
│   ├── Scanner/               # Content scanner, text extractor, and batch worker
│   ├── Storage/               # Post metadata and options repository
│   ├── Updater/               # Automated schema maintenance on post save
│   └── Validator/             # Schema.org and JSON validation engine
├── languages/                 # Internationalization POT templates
├── tests/                     # Test suites and WordPress mock harness
├── tools/                     # Production packaging automation scripts
├── dpls-schema-ai.php         # Main plugin bootstrap file
├── readme.txt                 # Official WordPress.org repository readme
├── LICENSE                    # GNU General Public License v2.0
└── uninstall.php              # Non-destructive uninstallation handler
```

---

## 🔒 Security & Privacy

- **No Remote Tracking or Telemetry**: No external calls to third-party tracking or analytics services.
- **Local Storage**: All data, settings, and schema histories are stored locally in standard WordPress tables (`wp_options` and `wp_postmeta`).
- **Strict Escaping**: All frontend JSON-LD scripts are encoded using `wp_json_encode()` with `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE` and wrapped in validated `<script type="application/ld+json">` tags.
- **Permission Checks**: All AJAX handlers and administrative actions enforce strict capability checks (`manage_options` or `edit_post`) and cryptographically validated nonces.
- **Safe Uninstallation**: By default, user schema data and configurations are preserved if the plugin is uninstalled, unless the administrator explicitly enables "Complete Data Removal" in Settings.

---

## 📄 License

This project is licensed under the **GNU General Public License v2.0 or later** ([GPL-2.0-or-later](LICENSE)).

Copyright (C) 2026 Dev Pattern Labs.
All rights reserved.
