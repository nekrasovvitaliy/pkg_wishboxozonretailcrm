# WishBox Ozon RetailCRM

Joomla package for synchronising RetailCRM with Ozon Seller. The project follows the structure of `pkg_wishboxvkretailcrm`, but uses the Ozon Seller API and the separate `pkg_wishboxozon` library.

## Features

- exports RetailCRM offer prices and available quantities to Ozon;
- imports new Ozon FBS postings into RetailCRM;
- prevents duplicate imports by the Ozon posting number;
- supports scheduled Joomla tasks;
- exposes an optional Ozon push-notification endpoint;
- registers and monitors the Ozon delivery module in RetailCRM;
- supports a RetailCRM API proxy token.

## Requirements

- Joomla 4.4.3 or later;
- PHP 8.5 or later;
- installed and enabled `pkg_wishboxozon` package;
- Ozon Seller API credentials (`Client-Id` and `Api-Key`);
- RetailCRM API URL, API key, and site code.

## Installation

1. Install `pkg_wishboxozon.zip`.
2. Open **Components → WishBox Ozon** and configure the Ozon connection, warehouse, and currency.
3. Install `pkg_wishboxozonretailcrm.zip` through the Joomla extension installer.
4. Enable the **Task - WishBox Ozon RetailCRM** plugin and configure its RetailCRM settings.
5. Create scheduler tasks for product synchronisation and FBS order import.
6. If push notifications are required, enable the **Web Services - WishBox Ozon RetailCRM** plugin and configure its token.
7. Open **Components → WishBox Ozon RetailCRM** and install the RetailCRM integration module.

## RetailCRM integration module

The administrator component reads the current module configuration from RetailCRM and displays one of these states: not installed, installed, update required, or API error. The install/update button sends a protected POST request in Joomla, updates the module through the RetailCRM API, and redirects back to a fresh status check.

RetailCRM credentials are shared with the task plugin. The API key must allow reading and writing integration modules. The registered delivery module follows the legacy `wishboxozonshipping` contract and advertises the `package-label` print form at `/print` relative to the public integration base URL.

The webservices plugin maps this action to:

```text
POST https://example.com/api/index.php/v1/wishboxozonretailcrm/print
```

The API controller validates the module `clientId`, reads the legacy `print.deliveryIds` payload, requests a combined PDF from Ozon, and returns it to RetailCRM.

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
