<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use InvalidArgumentException;
use RetailCrm\Api\Exception\Api\AccountDoesNotExistException;
use RetailCrm\Api\Exception\Api\ApiErrorException;
use RetailCrm\Api\Exception\Api\MissingCredentialsException;
use RetailCrm\Api\Exception\Api\MissingParameterException;
use RetailCrm\Api\Exception\Api\ValidationException;
use RetailCrm\Api\Exception\Client\HandlerException;
use RetailCrm\Api\Exception\Client\HttpClientException;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use RuntimeException;
use WishboxOzonLibrary\Service\OzonFbsService;
use WishboxRetailCrmLibrary\Service\RetailCrmOrderService;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Ships an Ozon posting associated with a RetailCRM order.
 *
 * @since 1.0.0
 */
final readonly class OzonOrderShipmentService
{
	/**
	 * Initialize the Ozon order shipment service.
	 *
	 * @param   RetailCrmOrderService  $retailCrmOrderService  RetailCRM order service.
	 * @param   OzonFbsService         $ozonFbsService         Ozon FBS service.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		private RetailCrmOrderService $retailCrmOrderService,
		private OzonFbsService $ozonFbsService
	) {
	}

	/**
	 * Package and ship the Ozon posting associated with a RetailCRM order.
	 *
	 * @param   int  $retailCrmOrderId  RetailCRM order identifier.
	 *
	 * @return void
	 *
	 * @throws AccountDoesNotExistException
	 * @throws ApiErrorException
	 * @throws MissingCredentialsException
	 * @throws MissingParameterException
	 * @throws ValidationException
	 * @throws HandlerException
	 * @throws HttpClientException
	 * @throws ApiExceptionInterface
	 * @throws ClientExceptionInterface
	 *
	 * @since 1.0.0
	 */
	public function ship(int $retailCrmOrderId): void
	{
		if ($retailCrmOrderId < 1)
		{
			throw new InvalidArgumentException('RetailCRM order ID must be more than zero.');
		}

		$order = $this->retailCrmOrderService->getOrder($retailCrmOrderId);

		// RetailCRM stores the Ozon posting number in lastName for this integration.
		$postingNumber = trim($order->lastName);

		if ($postingNumber === '')
		{
			throw new RuntimeException('The RetailCRM order does not contain an Ozon posting number.');
		}

		$posting = $this->ozonFbsService->getPosting($postingNumber);
		$packages = [['products' => []]];

		foreach ($posting->products ?? [] as $product)
		{
			$packages[0]['products'][] = [
				'product_id' => $product->sku,
				'quantity'   => $product->quantity,
			];
		}

		$this->ozonFbsService->ship($postingNumber, $packages);
	}
}
