# Changelog - Haerriz_GoogleShoppingFeed

All notable changes to the `Haerriz_GoogleShoppingFeed` Magento 2 module are documented here.

## [2.4.4] - 2026-07-30

### Fixed
- Fixed infinite loading spinner on Feed Edit form by replacing invalid `'Magento_Rule::fieldset.phtml'` template path with `'Magento_CatalogRule::promo/fieldset.phtml'` inside `Block/Adminhtml/Feed/Edit/Tab/Conditions.php`.

---

## [2.4.3] - 2026-07-30

### Fixed
- Fixed empty feed profile edit page by removing invalid `<update handle="editor"/>` in `haerriz_googleshoppingfeed_feed_edit.xml`.

---

## [2.4.2] - 2026-07-30

### Fixed
- Resolved Magento Report `472e1ddfd24770593f0b6945f3f1750487918102d5de98be58d6e451a17b2df8` by adding dataProvider ConfigurableObject to `feed_form.xml`.
