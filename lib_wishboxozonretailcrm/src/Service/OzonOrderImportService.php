<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license         GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Joomla\Registry\Registry;
use RetailCrm\Api\Exception\Api\AccountDoesNotExistException;
use RetailCrm\Api\Exception\Api\ApiErrorException;
use RetailCrm\Api\Exception\Api\MissingCredentialsException;
use RetailCrm\Api\Exception\Api\MissingParameterException;
use RetailCrm\Api\Exception\Api\ValidationException;
use RetailCrm\Api\Exception\Client\HandlerException;
use RetailCrm\Api\Exception\Client\HttpClientException;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use RetailCrm\Api\Model\Entity\Orders\Delivery\OrderDeliveryAddress;
use RetailCrm\Api\Model\Entity\Orders\Delivery\SerializedOrderDelivery;
use RetailCrm\Api\Model\Entity\Orders\Items\Offer;
use RetailCrm\Api\Model\Entity\Orders\Items\OrderProduct;
use RetailCrm\Api\Model\Entity\Orders\MarketplaceData;
use RetailCrm\Api\Model\Entity\Orders\Order;
use RetailCrm\Api\Model\Entity\Store\StoreOffer;
use stdClass;
use Throwable;
use WishboxOzonLibrary\Service\OzonFbsService;
use WishboxOzonRetailCrmLibrary\Dto\OzonOrderImportResult;
use WishboxOzonRetailCrmLibrary\Exception\OzonOrderImportException;
use WishboxOzonRetailCrmLibrary\Repositories\RetailCrmOfferRepository;
use WishboxOzonRetailCrmLibrary\Repositories\RetailCrmOrderRepository;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Imports new Ozon FBS postings into RetailCRM.
 *
 * @since 1.0.0
 */
final readonly class OzonOrderImportService
{
	public function __construct(
		private OzonFbsService           $ozonFbsService,
		private RetailCrmOfferRepository $retailCrmOfferRepository,
		private RetailCrmOrderRepository $retailCrmOrderRepository,
		private Registry                 $params
	)
	{
	}

	/**
	 * Import one cursor page of Ozon postings that do not exist in RetailCRM.
	 *
	 * @throws DateMalformedStringException
	 * @since 1.0.0
	 */
	public function importNewOrders(int $limit = 10, string $cursor = ''): OzonOrderImportResult
	{
		$limit        = min(1000, max(1, $limit));
		$lookbackDays = min(365, max(1, (int) $this->params->get('order_lookback_days', 5)));
		$now          = new DateTimeImmutable('now', new DateTimeZone('UTC'));
		$statuses     = explode(',', (string) $this->params->get('ozon_order_statuses', 'awaiting_packaging'))
				|> (fn($x) => array_map('trim', $x))
				|> array_filter(...)
				|> array_values(...);
		$response     = $this->ozonFbsService->getPostings(
			filter: [
				'since'  => $now->modify('-' . $lookbackDays . ' days')->format('Y-m-d\TH:i:s\Z'),
				'to'     => $now->format('Y-m-d\TH:i:s\Z'),
				'status' => $statuses,
			],
			limit: $limit,
			cursor: $cursor,
			direction: 'DESC',
			with: [
				'analytics_data' => true,
				'barcodes'       => true,
				'financial_data' => true,
				'legal_info'     => true,
			]
		);
		$result       = new OzonOrderImportResult();
		$postings     = is_array($response->postings ?? null) ? $response->postings : [];

		foreach ($postings as $posting)
		{
			if (!$posting instanceof stdClass)
			{
				$result->failed++;
				$result->errors['unknown'] = 'Ozon returned an invalid posting.';

				continue;
			}

			$postingNumber = trim((string) ($posting->posting_number ?? ''));

			if ($postingNumber === '')
			{
				$result->failed++;
				$result->errors['unknown'] = 'Ozon returned a posting without a posting number.';

				continue;
			}

			try
			{
				if ($this->retailCrmOrderRepository->orderExists($postingNumber))
				{
					$result->skipped++;

					continue;
				}

				$retailCrmOrder   = $this->buildRetailCrmOrder($posting);
				$retailCrmOrderId = $this->retailCrmOrderRepository->createOrder(
					$retailCrmOrder,
					$this->getRetailCrmSite()
				);

				$result->created++;
				$result->orderIds[$postingNumber] = $retailCrmOrderId;
			}
			catch (Throwable $throwable)
			{
				$result->failed++;
				$result->errors[$postingNumber] = $throwable->getMessage();
			}
		}

		return $result;
	}

	/**
	 * @throws ApiErrorException
	 * @throws ClientExceptionInterface
	 * @throws HandlerException
	 * @throws MissingCredentialsException
	 * @throws AccountDoesNotExistException
	 * @throws HttpClientException
	 * @throws ApiExceptionInterface
	 * @throws ValidationException
	 * @throws MissingParameterException
	 *
	 * @since 1.0.0
	 */
	private function buildRetailCrmOrder(stdClass $posting): Order
	{
		$products   = array_values(
			array_filter(
				is_array($posting->products ?? null) ? $posting->products : [],
				static fn(mixed $product): bool => $product instanceof stdClass
			)
		);
		$ozonSkus   = array_map(static fn(stdClass $product): int => (int) ($product->sku ?? 0), $products);
		$offers     = $this->retailCrmOfferRepository->getOffersByOzonSkus(
			$ozonSkus,
			(int) $this->params->get('retailcrm_offer_lookup_limit', 10000)
		);
		$orderItems = [];

		foreach ($products as $product)
		{
			$ozonSku = (int) ($product->sku ?? 0);

			if ($ozonSku < 1 || !isset($offers[$ozonSku]))
			{
				throw new OzonOrderImportException('RetailCRM offer was not found for Ozon SKU ' . $ozonSku . '.');
			}

			$orderItems[] = $this->buildRetailCrmOrderItem($product, $offers[$ozonSku]);
		}

		if ($orderItems === [])
		{
			throw new OzonOrderImportException('Ozon posting does not contain any products.');
		}

		$customer              = $posting->customer ?? new stdClass();
		$addressee             = $posting->addressee ?? new stdClass();
		$name                  = trim((string) ($addressee->name ?? $customer->name ?? ''));
		$nameParts             = preg_split('/\s+/u', $name, 3, PREG_SPLIT_NO_EMPTY) ?: [];
		$postingNumber         = (string) $posting->posting_number;
		$order                 = new Order();
		$order->externalId     = $postingNumber;
		$order->site           = $this->getRetailCrmSite();
		$order->currency       = (string) $this->params->get('order_currency', 'RUB');
		$order->orderType      = (string) $this->params->get('retailcrm_order_type', 'eshop-individual');
		$order->orderMethod    = (string) $this->params->get('retailcrm_order_method', 'shopping-cart');
		$order->status         = (string) $this->params->get('retailcrm_order_status', 'new');
		$order->firstName      = (string) ($nameParts[0] ?? 'Ozon');
		$order->lastName       = (string) ($nameParts[1] ?? 'Customer');
		$order->patronymic     = (string) ($nameParts[2] ?? '');
		$order->phone          = (string) ($addressee->phone ?? $customer->phone ?? '');
		$order->email          = (string) ($customer->customer_email ?? $customer->email ?? '');
		$order->managerComment = 'Imported from Ozon posting ' . $postingNumber
			. '; order #' . ($posting->order_number ?? $posting->order_id ?? '')
			. '; status ' . ($posting->status ?? '') . '.';
		$order->items          = $orderItems;
		$order->delivery       = $this->buildDelivery($posting);
		$marketplaceCode       = trim((string) $this->params->get('retailcrm_marketplace_code'));

		if ($marketplaceCode !== '')
		{
			$order->marketplace          = new MarketplaceData();
			$order->marketplace->code    = $marketplaceCode;
			$order->marketplace->orderId = $postingNumber;
		}

		$postingField = trim((string) $this->params->get('retailcrm_ozon_posting_field'));

		if ($postingField !== '')
		{
			$order->customFields = [$postingField => $postingNumber];
		}

		return $order;
	}

	private function buildRetailCrmOrderItem(stdClass $product, StoreOffer $retailCrmOffer): OrderProduct
	{
		$offer              = new Offer();
		$offer->id          = $retailCrmOffer->id;
		$offer->externalId  = $retailCrmOffer->externalId ?? '';
		$item               = new OrderProduct();
		$item->offer        = $offer;
		$item->quantity     = max(1.0, (float) ($product->quantity ?? 1));
		$item->initialPrice = is_numeric($product->price ?? null)
			? (float) $product->price
			: ($this->retailCrmOfferRepository->getPrice($retailCrmOffer) ?? 0.0);
		$item->productName  = (string) (
			$product->name
			?? $retailCrmOffer->name
			?? ('Ozon SKU #' . (int) ($product->sku ?? 0))
		);

		return $item;
	}

	private function buildDelivery(stdClass $posting): SerializedOrderDelivery
	{
		$customer            = $posting->customer ?? new stdClass();
		$ozonAddress         = $customer->address ?? new stdClass();
		$address             = new OrderDeliveryAddress();
		$address->countryIso = (string) ($ozonAddress->country ?? '');
		$address->region     = (string) ($ozonAddress->region ?? '');
		$address->city       = (string) ($ozonAddress->city ?? '');
		$address->text       = (string) ($ozonAddress->address_tail ?? $ozonAddress->address_line ?? '');
		$address->index      = (string) ($ozonAddress->zip_code ?? '');
		$address->notes      = (string) ($ozonAddress->comment ?? '');
		$delivery            = new SerializedOrderDelivery();
		$delivery->address   = $address;
		$delivery->cost      = 0.0;

		return $delivery;
	}

	private function getRetailCrmSite(): string
	{
		$site = trim((string) $this->params->get('retailcrm_site'));

		if ($site === '')
		{
			throw new OzonOrderImportException('RetailCRM site code must be configured.');
		}

		return $site;
	}
}
