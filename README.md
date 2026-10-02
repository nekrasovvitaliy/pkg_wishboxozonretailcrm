# WishBox Ozon RetailCRM

Joomla package for synchronising RetailCRM with Ozon Seller. The project follows the structure of `pkg_wishboxvkretailcrm`, but uses the Ozon Seller API and the separate `pkg_wishboxozon` library.

## Features

- exports RetailCRM offer prices and available quantities to Ozon;
- imports new Ozon FBS postings into RetailCRM;
- prevents duplicate imports by the Ozon posting number;
- supports scheduled Joomla tasks;
- exposes an optional Ozon push-notification endpoint;
- supports a RetailCRM API proxy token.

## Requirements

- Joomla 4.4.3 or later;
- PHP 8.5 or later;
- installed and enabled `pkg_wishboxozon` package;
- Ozon Seller API credentials (`Client-Id` and `Api-Key`);
- RetailCRM API URL, API key, and site code.

## Installation

1. Install `pkg_wishboxozon.zip`.
2. Install `pkg_wishboxozonretailcrm.zip` through the Joomla extension installer.
3. Enable the **Task - WishBox Ozon RetailCRM** plugin.
4. Configure Ozon and RetailCRM credentials in the plugin settings.
5. Create scheduler tasks for product synchronisation and FBS order import.
6. If push notifications are required, enable the **Web Services - WishBox Ozon RetailCRM** plugin and configure its token.

## RetailCRM offer mapping

The integration reads these custom offer properties by default:

- `ozon_product_id` — Ozon product ID used for price and stock updates;
- `ozon_sku` — Ozon marketplace SKU used to match posting products during order import.

Both property codes can be changed in the plugin settings. The RetailCRM offer `externalId` is sent to Ozon as `offer_id`.

## Push notifications

Configure the Ozon notification URL as:

```text
https://example.com/api/index.php/v1/wishboxozonretailcrm/callback?token=YOUR_RANDOM_TOKEN
```

Use the same random token in the webservices plugin settings. `TYPE_NEW_POSTING` triggers an import pass; ping and other notification types receive a valid acknowledgement without importing orders.

## Development

```bash
composer install
composer test
php phing-latest.phar
```

The last command creates `pkg_wishboxozonretailcrm.zip`.
