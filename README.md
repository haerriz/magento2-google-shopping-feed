# Haerriz Google Shopping Feed for Magento 2 (v2.0.0)

A robust, enterprise-grade Magento 2 module for generating Google Shopping Feeds and synchronizing products directly with Google Merchant Center via the Merchant API.

## Features
- **Dynamic Attribute Mapping**: Infinite dynamic rows to map Magento attributes to Google attributes.
- **Google Taxonomy Mapping**: Effortlessly categorize your products according to Google's strict taxonomy requirements.
- **Enterprise Modifiers**: Modify feed output with an extensible Modifier Pool (e.g. Prepends, Appends, Strip HTML, Round Prices).
- **Merchant API Integration**: Communicates directly with Google Merchant Center using secure service-account authentication.
- **Automated Timezone-Aware Cron Jobs**: Automatically generates and syncs feeds silently on customizable hourly/daily/weekly schedules.
- **Attribution & UTM Builder**: Resolves placeholder query values (`{sku}`, `{platform}`) and merges parameters safely without breaking fragment URLs.
- **OpenAI Agentic Commerce presets**: Generates JSONL configurations and compresses output files (`jsonl.gz`, `csv.gz`) with minor currency units automatically.

## Storefront Compatibility (Hyva & Luma)
- **Theme-Agnostic Generation**: All product processing and feeds generation run fully server-side. No storefront blocks, stylesheets, or layouts are loaded, guaranteeing 100% out-of-the-box compatibility with Hyva Themes, Hyva Checkout, Luma, or custom headless PWA installations.
- **No Storefront JS**: Does not inject RequireJS or any third-party storefront Javascript, ensuring perfect Google PageSpeed scores.

## Security Policies
- **Path Traversal Protection**: Save endpoints enforce basename checks blocking directory traversal vectors.
- **Credential Encryption**: Remote FTP/SFTP upload passwords are encrypted in the DB using Magento's core `EncryptorInterface`.
- **Log Sanitization**: Integrates Monolog channels redacting service account credentials or passwords from trace logs.

## Installation
```bash
composer require haerriz/module-google-shopping-feed
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

## CLI Operations
Run profile generations manually from the CLI:
```bash
php bin/magento haerriz:feed:generate --profile_id=[ID]
```

## License
MIT License. Free and Open Source.
