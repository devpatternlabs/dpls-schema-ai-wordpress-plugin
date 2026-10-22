=== DPLS Schema AI ===
Contributors: devpatternlabs
Tags: schema, structured data, json-ld, seo, rich snippets
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Analyze page and post content, recommend structured data, generate Schema.org JSON-LD, and maintain fresh schema when WordPress content changes.

== Description ==

DPLS Schema AI is a production-grade WordPress SEO and structured-data management plugin developed by Dev Pattern Labs. It scans WordPress content, identifies qualifying Schema.org types, generates standards-compliant JSON-LD markup, provides an administrator review and application workflow, injects clean structured data into frontend headers, and maintains schema freshness as content changes over time.

= Free, Transparent, and Self-Contained =
DPLS Schema AI operates completely autonomously inside your local WordPress installation. It does not require paid third-party AI subscriptions, cloud credits, or external backend services. For developers desiring AI-assisted entity recognition, an optional local connection to a private Ollama instance is supported with explicit administrator consent.

= Key Features =
* **Intelligent Heuristic Classification**: Evaluates multi-factor content structure, headings, excerpts, metadata, and post types rather than guessing from titles alone.
* **14+ Supported Schema Types**: Native support for Article, BlogPosting, NewsArticle, WebPage, AboutPage, ContactPage, FAQPage, Service, Product, Recipe, HowTo, LocalBusiness, Organization, and BreadcrumbList.
* **Evidence-Based Generation**: Never invents missing data. Prices, ratings, reviews, authors, and addresses are only included when verifiably present in source content.
* **Administrator Review Workflow**: Review proposals, customize schema properties, inspect formatted JSON-LD, run validation checks, and apply schema with explicit confirmation.
* **Native Post Editor Integration**: Integrated control panel inside both the WordPress Block Editor and Classic Editor.
* **Automatic Schema Maintenance**: Listens for post modifications via `save_post` with intelligent content hashing. Supports SAFE AUTO-UPDATE (updates timestamps and headlines while preserving approved types and custom edits) and REVIEW REQUIRED policies.
* **Version History & One-Click Rollback**: Archives historical schema iterations, allowing instant rollback to previous versions.
* **WooCommerce E-Commerce Integration**: Reads genuine product attributes (SKU, verified price, stock status, and actual customer reviews) while preventing duplicate schema conflicts with WooCommerce core.
* **Page Builder Support**: Specialized adapters (including Elementor) parse custom widget trees when standard `post_content` is empty.
* **Data Management & Portability**: Export and import settings or schema records via standard JSON with pre-import validation and overwrite protection.
* **Privacy & Non-Destructive Storage**: Operates locally without external data transmission. Preserves your settings and schemas on deactivation and uninstall by default unless explicitly configured otherwise.

= Supported Schema.org Types =
1. **Article**: Standard articles and long-form written pieces.
2. **BlogPosting**: Chronological blog posts and journal updates.
3. **NewsArticle**: Timely journalistic reporting categorized under news feeds.
4. **WebPage**: Standard generic web pages and foundational landing pages.
5. **AboutPage**: Organizational narratives detailing corporate missions and team background.
6. **ContactPage**: Verified contact endpoints, public emails, telephone numbers, and maps.
7. **FAQPage**: Frequently Asked Questions requiring at least two qualifying structured Q&A pairs.
8. **Service**: Professional, technical, or commercial service representations.
9. **Product**: Physical and digital merchandise with verified pricing, SKU, and availability.
10. **Recipe**: Culinary recipes detailing ingredients, cooking times, and nutrition.
11. **HowTo**: Step-by-step instructional guides and procedural tutorials.
12. **LocalBusiness**: Physical storefronts, offices, and geographic business profiles.
13. **Organization**: Verified corporate, NGO, or educational entity identity.
14. **BreadcrumbList**: Hierarchical website navigation trail from homepage to current post.

= Search Engine Disclaimer =
Structured data markup provides standardized machine-readable context for search engine crawlers. Search engines (including Google, Bing, and Yahoo) evaluate structured data independently; the implementation of valid Schema.org JSON-LD does not guarantee rich result appearance or specific search engine ranking improvements.

== Installation ==

= From WordPress Admin =
1. Navigate to **Plugins &rarr; Add New &rarr; Upload Plugin**.
2. Select the `dpls-schema-ai.zip` file and click **Install Now**.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Go to **DPL Schema AI &rarr; Settings** to configure your organization identity and verified details.

= Manual FTP / File Manager =
1. Download and extract the `dpls-schema-ai.zip` archive.
2. Upload the `dpls-schema-ai` directory to your WordPress site's `/wp-content/plugins/` directory.
3. Activate the plugin in WordPress under **Plugins &rarr; Installed Plugins**.

= Initial Setup =
1. Visit **DPL Schema AI &rarr; Settings** and enter your Organization Legal Name, Website URL, and Logo.
2. Review the **Enabled Post Types** (default: Posts and Pages).
3. Navigate to **Pages & Posts** to run an initial content scan or generate proposals for existing articles.

== Frequently Asked Questions ==

= Does DPLS Schema AI guarantee rich snippets in Google search results? =
No. DPLS Schema AI generates valid Schema.org JSON-LD formatted to Google's official structured data guidelines. However, rich result displays in search engine result pages (SERPs) remain solely at the discretion of search engine algorithms, website authority, and search context.

= Does this plugin require paid third-party AI subscriptions or cloud credits? =
No. The core classification and generation engine is 100% rule-based and deterministic. It runs entirely inside your local PHP and WordPress environment without requiring paid API keys, monthly subscriptions, or external network calls.

= Can I use local AI models with this plugin? =
Yes. If you operate an Ollama instance on your local machine or server, you can optionally connect it via the Integrations screen with explicit administrator consent. No data is sent to external cloud vendors.

= What happens when an author updates an existing post? =
DPLS Schema AI computes a cryptographic content hash over titles, excerpts, plain text, and modified dates. If content has not changed, processing is bypassed immediately. If content is modified, the plugin either updates approved fields under SAFE AUTO-UPDATE policy or flags the item for administrator review under REVIEW REQUIRED policy.

= How does the WooCommerce integration prevent conflicts? =
By default, WooCommerce outputs basic product schema. DPLS Schema AI provides a one-click toggle to suppress WooCommerce's default schema, allowing DPLS Schema AI to output a single, enriched Schema.org Product block that avoids duplicate entity warnings in Search Console.

= Does the plugin work with page builders like Elementor? =
Yes. When a page is designed using Elementor, WordPress `post_content` is often blank. DPLS Schema AI includes a dedicated Elementor adapter that parses the widget JSON tree (`_elementor_data`) to extract text and headings without executing arbitrary code.

= Will uninstalling the plugin delete my existing schema data? =
By default, NO. DPLS Schema AI adheres to a strict non-destructive uninstall policy (`preserve_data = true`). If you deactivate or delete the plugin, your schema metadata and history logs remain preserved. To permanently purge all plugin data upon uninstallation, administrators can enable the explicit deletion option under Settings.

== Screenshots ==

1. **Overview Dashboard**: Real-time structured data coverage, awaiting review queue, validation warnings, and content breakdown.
2. **Pages & Posts Inventory**: Filterable content inventory with publication statuses, suggested schema types, and interactive management actions.
3. **Interactive Post Editor Panel**: Block Editor and Classic Editor meta box for instant content analysis, JSON-LD preview, and one-click application.
4. **Schema Generator & JSON-LD Viewer**: Structured proposal generator detailing source fields, missing recommendations, and formatted markup.
5. **System-wide Validation Hub**: Automatic validation checking for required Schema.org properties and syntax errors.
6. **Verified Organization Settings**: Legal entity configuration, logo URL, official contact endpoints, and social profile links.
7. **WooCommerce & Elementor Integrations**: E-commerce mapping settings and page builder adapters with version detection.
8. **Data Management & Backup Portability**: Settings and schema records export/import with pre-import validation preview and overwrite protection.

== Changelog ==

= 2.0.0 =
* Major release introducing modular AI provider architecture with local Ollama support.
* Self-contained: 100% functional without external paid APIs or cloud dependencies.
* Server-side SSRF security defenses blocking cloud metadata endpoints and unauthorized protocols.
* Prompt injection isolation using strict delimiters, HTML stripping, and bounded content limits.
* Strict PHP-side response validation, confidence score bounds, and evidence-based anti-hallucination checks.
* Interactive AI analysis and comparison panel within the Schema Generator dashboard.
* Live connection testing endpoint with latency reporting and model tag discovery.
* Asynchronous background schema updates via WP-Cron (`dpls_schema_ai_async_ai_update`).
* 100% compliant with WordPress Plugin Check (PCP) repository guidelines.

= 1.0.1 =
* Preparation for review update on WordPress.org.
* Initial integration hooks for local AI providers.
* Minor admin styling refinements and compatibility updates.

= 1.0.0 =
* Initial production release.
* Modular PSR-4 architecture with strict WordPress coding standards.
* Complete administrative dashboard featuring Overview, Pages & Posts, Generator, Validation, Automation, History, Integrations, and Settings.
* Heuristic rule-based Schema.org generator supporting 14+ standard entity types.
* Frontend JSON-LD injection engine hooked into `wp_head` with request-level deduplication.
* Automated maintenance engine with content hashing, field locks, and safe update policies.
* Comprehensive WooCommerce e-commerce integration with verified price and review mapping.
* Native Elementor page builder adapter parsing widget data structures safely.
* Portable JSON configuration and schema records Import/Export module with validation preview.
* Core WordPress Privacy Policy Guide integration and non-destructive uninstaller.
