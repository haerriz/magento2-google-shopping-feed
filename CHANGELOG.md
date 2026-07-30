# Changelog - Haerriz_GoogleShoppingFeed

All notable changes to the `Haerriz_GoogleShoppingFeed` Magento 2 module are documented here.

## [2.4.1] - 2026-07-30

### Fixed
- Fixed `Unsupported feed format: xml` exception in `Model/Writer/Pool.php`.
- Added format fallback mapping in `WriterPool` supporting `xml`, `csv`, `jsonl`, and all 11 channel codes (`google_shopping_v1`, `meta_catalog_v1`, `instagram_catalog_v1`, `snapchat_catalog_v1`, `tiktok_catalog_v1`, `pinterest_catalog_v1`, `microsoft_merchant_v1`, `amazon_catalog_v1`, `ebay_inventory_v1`, `rakuten_catalog_v1`, `openai_commerce_v1`).
- Wired `WriterPool` format map arguments in `etc/di.xml`.

---

## [2.4.0] - 2026-07-30

### Added
- **Exact 7-Step Feed Wizard UI**:
  - Step 1: General Settings
  - Step 2: Exclude Categories
  - Step 3: Rename Categories
  - Step 4: Basic Product Information
  - Step 5: Optional Product Information
  - Step 6: Schedule Settings
  - Step 7: Destination
- **11 Multi-Channel Templates** (Google, Meta, Instagram, Snapchat, TikTok, Pinterest, Microsoft/Bing, Amazon, eBay, Rakuten, OpenAI).
- **Admin Grid Actions & Quick View**.
- **System Information & Diagnostics Panel**.
- **PHPUnit Unit Test Suite**.
