# Changelog - Haerriz_GoogleShoppingFeed

All notable changes to the `Haerriz_GoogleShoppingFeed` Magento 2 module are documented here.

## [2.4.0] - 2026-07-30

### Added
- **Exact 7-Step Feed Wizard UI**:
  - Step 1: General Settings (Name, Status, Store View, Channel & Template, Output Filename)
  - Step 2: Exclude Categories (Multi-select category tree exclusion)
  - Step 3: Rename Categories (Google & Channel Taxonomy Mappings)
  - Step 4: Basic Product Information (SKU, Title, Description, Price, Availability)
  - Step 5: Optional Product Information (Brand, GTIN, MPN, Condition, UTM Tags)
  - Step 6: Schedule Settings (Cron Schedule Expression)
  - Step 7: Destination (Local, FTP, SFTP, Direct Merchant API Credentials)
- **11 Multi-Channel Templates**:
  - Google Shopping (XML)
  - Meta / Facebook Catalog (CSV)
  - Instagram Shopping (CSV)
  - Snapchat Product Catalog (CSV)
  - TikTok Commerce Catalog (CSV)
  - Pinterest Product Catalog (CSV)
  - Microsoft / Bing Shopping (XML)
  - Amazon Seller Catalog (CSV)
  - eBay Inventory Feed (CSV)
  - Rakuten Advertising (CSV)
  - OpenAI / ChatGPT Agentic Commerce (JSONL)
- **Admin Grid Actions & Quick View**:
  - Interactive Actions column dropdown: Edit, Quick View, Generate Now, Duplicate, Job History, Download Feed, Delete.
  - Live Quick View preview window rendering formatted output payload samples.
- **System Information & Diagnostics Panel**:
  - Displays Magento Mode (Production/Developer), Magento Root Path, Server User, Database Time, Opcache Status, and CLI PHP Executable Path setting under Stores -> Configuration -> Haerriz Extensions -> Product Feed.
- **PHPUnit Unit Test Suite**:
  - `Test/Unit/Block/SystemInfoTest.php` covering block rendering and system diagnostics logic.

### Fixed
- UI Component Data Source handle registration error in `etc/di.xml`.
- Call to undefined method `generatePreview()` in `Model/PreviewService.php`.
- Static asset styling deployment across backend (`adminhtml/Magento/backend`) and storefront themes.

---

## [2.1.0] - 2026-07-29

### Added
- Service Contracts & DTOs for `GenerationOrchestrator`, `ArtifactManager`, `DeliveryAdapterPool`.
- Google Merchant API v1 direct product synchronization.
- Sharding, Compression (Gzip, Zip, Bzip2), and Queue multi-process capabilities.
