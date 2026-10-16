<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

define('_JEXEC', 1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/pkg_wishbox_j4/lib_wishbox/src/Dto/OrderImport/MarketplaceOrderDelivery.php';
require dirname(__DIR__, 2) . '/pkg_wishbox_j4/lib_wishbox/src/Dto/OrderImport/MarketplaceOrderItem.php';
require dirname(__DIR__, 2) . '/pkg_wishbox_j4/lib_wishbox/src/Dto/OrderImport/MarketplaceOrder.php';
require dirname(__DIR__, 2) . '/pkg_wishbox_j4/lib_wishbox/src/Dto/OrderImport/OrderImportResult.php';
require dirname(__DIR__, 2) . '/pkg_wishboxretailcrm/lib_wishboxretailcrm/src/Configuration/RetailCrmConfiguration.php';
require dirname(__DIR__, 2) . '/pkg_wishboxretailcrm/lib_wishboxretailcrm/src/Service/RetailCrmOrderService.php';

$environmentFileDirectory = dirname(__DIR__);

if (is_file($environmentFileDirectory . '/.env'))
{
	Dotenv\Dotenv::createImmutable($environmentFileDirectory)->safeLoad();
}

class_alias(
	Tests\Stubs\WishboxOzonLibrary\Service\OzonProductService::class,
	WishboxOzonLibrary\Service\OzonProductService::class
);
class_alias(
	Tests\Stubs\WishboxOzonLibrary\Service\OzonFbsService::class,
	WishboxOzonLibrary\Service\OzonFbsService::class
);
class_alias(
	Tests\Stubs\WishboxRetailCrmLibrary\Service\RetailCrmProductService::class,
	WishboxRetailCrmLibrary\Service\RetailCrmProductService::class
);
