<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Unit\Service;

use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;
use WishboxOzonLibrary\Service\OzonProductService;
use WishboxOzonRetailCrmLibrary\Service\RetailCrmToOzonProductSyncService;
use WishboxRetailCrmLibrary\Service\RetailCrmProductService;

final class RetailCrmToOzonProductSyncServiceTest extends TestCase
{
	public function testPassesRetailCrmProductsToOzonService(): void
	{
		$products = [
			[
				'offer_id'   => 'CRM-101',
				'product_id' => 101,
				'price'      => 1490.0,
				'stock'      => 3,
			],
		];
		$retailCrmProductService = new RetailCrmProductService($products);
		$ozonProductService = new OzonProductService();
		$ozonProductService->result = [
			'updated' => 1,
			'skipped' => 0,
			'failed'  => 0,
			'errors'  => [],
		];
		$service = new RetailCrmToOzonProductSyncService(
			$retailCrmProductService,
			$ozonProductService,
			new Registry([
				'ozon_product_id_property' => 'ozon_product_id',
				'retailcrm_price_type'     => 'base',
				'ozon_warehouse_id'        => 77,
				'ozon_currency_code'       => 'RUB',
			])
		);

		$result = $service->updateProducts();

		self::assertSame($products, $ozonProductService->products);
		self::assertSame(77, $ozonProductService->warehouseId);
		self::assertSame('RUB', $ozonProductService->currencyCode);
		self::assertSame(1, $result->updated);
		self::assertSame(0, $result->failed);
	}

	public function testReturnsOzonServiceResult(): void
	{
		$retailCrmProductService = new RetailCrmProductService([]);
		$ozonProductService = new OzonProductService();
		$ozonProductService->result = [
			'updated' => 0,
			'skipped' => 0,
			'failed'  => 1,
			'errors'  => ['Ozon request failed.'],
		];
		$service = new RetailCrmToOzonProductSyncService(
			$retailCrmProductService,
			$ozonProductService,
			new Registry(['ozon_warehouse_id' => 77])
		);

		$result = $service->updateProducts();

		self::assertSame(1, $result->failed);
		self::assertSame(['Ozon request failed.'], $result->errors);
	}
}
