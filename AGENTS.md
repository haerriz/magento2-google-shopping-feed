# Agent Collaboration Rules - Haerriz_GoogleShoppingFeed

## Module Governance
- Module: `Haerriz_GoogleShoppingFeed`
- Reference Baseline: `ANTIGRAVITY_COMPLETE_FILE_STRUCTURE.md`
- Target Platform: Magento 2.4.x (PHP 8.1 / 8.2 / 8.3)
- Frontends: Luma, Hyvä Theme, Hyvä Checkout, Headless/PWA

## Development Standards
- All processing must remain server-side and theme-agnostic.
- Never introduce RequireJS dependencies for storefront components.
- Enforce strict credential encryption and log sanitization.
- Maintain complete PHPUnit and Integration test coverage for modified/new components.
