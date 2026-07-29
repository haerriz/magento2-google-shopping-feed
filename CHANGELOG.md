# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
### Added
- `docs/CURRENT_STATE.md` with full architecture audit.
- `docs/PHASE_STATUS.md` to track project evolution.
- `etc/acl.xml` for strict access control across admin resources.
- PHPUnit tests for `MerchantClient` and `Feed/Save` controller to catch security vulnerabilities.
- **Phase 1**: Database declarative schema fully normalized. `store_id` cast to `smallint` with core FK constraint.
- **Phase 1**: Added base queue and log tables (`haerriz_google_shopping_feed_job`, `haerriz_google_shopping_feed_log`).
- **Phase 1**: Introduced full suite of Magento Service Contracts (`Api/Data/` and `Api/`) for strict data type enforcement.
- **Phase 1**: Created proper `Repository` and SearchCriteria implementations.
- **Phase 2**: Secure config reader (`Model/Config`) implemented with memory caching.
- **Phase 2**: Dedicated system logger channel (`var/log/haerriz_googleshoppingfeed.log`) configured via custom Monolog integration.
- **Phase 2**: Enforced strict input validation on Google Merchant Center ID in configuration backend.
- **Phase 3**: Refactored `FeedGenerator` with collection pagination to process catalogs in batch sizes of 500.
- **Phase 3**: Replaced full-string in-memory XML accumulation with structured chunk streaming directly to target files.
- **Phase 3**: Implemented PHPUnit coverage for streaming validation.
- **Phase 4**: Added `delivery_*` columns in `db_schema.xml` to support local, FTP, and SFTP endpoints.
- **Phase 4**: Expanded `FeedProfile` model and interface to support delivery credentials.
- **Phase 4**: Encrypted remote delivery passwords using Magento's core `EncryptorInterface` at the Controller layer.
- **Phase 4**: Implemented dynamic delivery field toggling in Admin Feed Edit UI.
- **Phase 4**: Created modular delivery storage system with `Local`, `Ftp`, and `Sftp` storage adapters and `AdapterPool`.
- **Phase 4**: Leveraged Magento's native `Ftp`/`Sftp` filesystem utilities for reliable file uploading.
- **Phase 5**: Integrated standard Magento Rule models (`Magento\Rule\Model\AbstractModel`) to support recursive catalog rules.
- **Phase 5**: Created custom admin UI conditions block `Tab\Conditions` to load standard Magento rule selection UI templates.
- **Phase 5**: Added AJAX conditions controller (`Controller/Adminhtml/Feed/Conditions`) to dynamically fetch condition lists.
- **Phase 5**: Wired conditions saving/serialization inside Save controller.
- **Phase 5**: Hooked rule validations directly into paginated product collections during CSV/XML feed generations.

### Changed
- `composer.json` dependencies locked to specific Magento `~103.0`/`~104.0` framework versions.
- `etc/adminhtml/system.xml`: Swapped Service Account JSON textarea for `obscure` encrypted backend model.
- Admin Feed Form UI component now requires `store_id` and `currency`.

### Fixed
- Directory path traversal vulnerability in `Controller/Adminhtml/Feed/Save.php` blocked via `basename` parsing.
- Cron `GenerateFeeds.php` false-positive `dummy_sync` removed; replaced with informational logging.
- API Client stub now actively throws `LocalizedException` instead of falsely simulating successful API push.
