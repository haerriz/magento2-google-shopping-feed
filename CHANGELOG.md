# Changelog - Haerriz_GoogleShoppingFeed

All notable changes to the `Haerriz_GoogleShoppingFeed` Magento 2 module are documented here.

## [2.4.2] - 2026-07-30

### Fixed
- Resolved Magento Report `472e1ddfd24770593f0b6945f3f1750487918102d5de98be58d6e451a17b2df8` caused by missing `<argument name="dataProvider" xsi:type="configurableObject">` node inside `haerriz_googleshoppingfeed_feed_form.xml`.
- Configured dataProvider class `Haerriz\GoogleShoppingFeed\Model\FeedProfile\DataProvider` in form UI component.

---

## [2.4.1] - 2026-07-30

### Fixed
- Fixed `Unsupported feed format: xml` exception in `Model/Writer/Pool.php`.
- Added format fallback mapping in `WriterPool` supporting `xml`, `csv`, `jsonl`, and all 11 channel codes.

---

## [2.4.0] - 2026-07-30

### Added
- **Exact 7-Step Feed Wizard UI**.
- **11 Multi-Channel Templates**.
- **Admin Grid Actions & Quick View**.
- **System Information & Diagnostics Panel**.
- **PHPUnit Unit Test Suite**.
