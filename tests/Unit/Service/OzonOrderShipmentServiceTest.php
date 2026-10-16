<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use RetailCrm\Api\Client;
use RetailCrm\Api\Model\Entity\Orders\Order;
use RetailCrm\Api\Model\Response\Orders\OrdersGetResponse;
use RetailCrm\Api\ResourceGroup\Orders;
use stdClass;
use Tests\Stubs\WishboxOzonLibrary\Service\OzonFbsService;
use RuntimeException;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderShipmentService;
use WishboxRetailCrmLibrary\Configuration\RetailCrmConfiguration;
use WishboxRetailCrmLibrary\Service\RetailCrmOrderService;

/**
 * Tests Ozon order shipment orchestration.
 *
 * @since 1.0.0
 */
final class OzonOrderShipmentServiceTest extends TestCase
{
	/**
	 * Verify that products from the Ozon posting are shipped as one package.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testShipsPostingForRetailCrmOrder(): void
	{
		$order = new Order();
		$order->lastName = '12345-0001-1';
		$ordersGetResponse = new OrdersGetResponse();
		$ordersGetResponse->order = $order;
		$ordersApi = $this->createStub(Orders::class);
		$ordersApi->method('get')->willReturn($ordersGetResponse);
		$retailCrmClient = $this->createStub(Client::class);
		$retailCrmClient->orders = $ordersApi;
		$retailCrmOrderService = new RetailCrmOrderService(
			$retailCrmClient,
			new RetailCrmConfiguration('', '', '', 'shop')
		);
		$product = new stdClass();
		$product->sku = 1001;
		$product->quantity = 2;
		$posting = new stdClass();
		$posting->posting_number = '12345-0001-1';
		$posting->products = [$product];
		$ozonResponse = new stdClass();
		$ozonResponse->postings = [$posting];
		$ozonFbsService = new OzonFbsService($ozonResponse);
		$service = new OzonOrderShipmentService($retailCrmOrderService, $ozonFbsService);

		$service->ship(77);

		self::assertSame('12345-0001-1', $ozonFbsService->shippedPostingNumber);
		self::assertSame(
			[[
				'products' => [[
					'product_id' => 1001,
					'quantity'   => 2,
				]],
			]],
			$ozonFbsService->shippedPackages
		);
	}

	/**
	 * Verify that an order without an Ozon posting number is rejected.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testRejectsOrderWithoutPostingNumber(): void
	{
		$order = new Order();
		$order->lastName = '';
		$ordersGetResponse = new OrdersGetResponse();
		$ordersGetResponse->order = $order;
		$ordersApi = $this->createStub(Orders::class);
		$ordersApi->method('get')->willReturn($ordersGetResponse);
		$retailCrmClient = $this->createStub(Client::class);
		$retailCrmClient->orders = $ordersApi;
		$retailCrmOrderService = new RetailCrmOrderService(
			$retailCrmClient,
			new RetailCrmConfiguration('', '', '', 'shop')
		);
		$ozonResponse = new stdClass();
		$ozonResponse->postings = [];
		$service = new OzonOrderShipmentService(
			$retailCrmOrderService,
			new OzonFbsService($ozonResponse)
		);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The RetailCRM order does not contain an Ozon posting number.');

		$service->ship(77);
	}
}
