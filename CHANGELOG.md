# Changelog - Haerriz_GoogleShoppingFeed

All notable changes to the `Haerriz_GoogleShoppingFeed` Magento 2 module are documented here.

## [2.4.3] - 2026-07-30

### Fixed
- Fixed empty feed profile edit page by removing invalid `<update handle="editor"/>` in `haerriz_googleshoppingfeed_feed_edit.xml`.
- Configured standard UI form template `templates/form/default` in `haerriz_googleshoppingfeed_feed_form.xml`.

---

## [2.4.2] - 2026-07-30

### Fixed
- Resolved Magento Report `472e1ddfd24770593f0b6945f3f1750487918102d5de98be58d6e451a17b2df8` by adding dataProvider ConfigurableObject to `feed_form.xml`.

---

## [2.4.1] - 2026-07-30

### Fixed
- Fixed `Unsupported feed format: xml` exception in `Model/Writer/Pool.php`.
