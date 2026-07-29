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

### Changed
- `composer.json` dependencies locked to specific Magento `~103.0`/`~104.0` framework versions.
- `etc/adminhtml/system.xml`: Swapped Service Account JSON textarea for `obscure` encrypted backend model.
- Admin Feed Form UI component now requires `store_id` and `currency`.

### Fixed
- Directory path traversal vulnerability in `Controller/Adminhtml/Feed/Save.php` blocked via `basename` parsing.
- Cron `GenerateFeeds.php` false-positive `dummy_sync` removed; replaced with informational logging.
- API Client stub now actively throws `LocalizedException` instead of falsely simulating successful API push.
