<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Stubs\WishboxOzonLibrary\Service;

use stdClass;
use Wishbox\Dto\OrderImport\MarketplaceOrder;
use Wishbox\Dto\OrderImport\MarketplaceOrderDelivery;
use Wishbox\Dto\OrderImport\MarketplaceOrderItem;

/**
 * Supplies deterministic Ozon FBS postings without making network requests.
 *
 * @since 1.0.0
 */
final class OzonFbsService
{
	/** @var array<string, mixed> */
	public array $lastFilter = [];

	public int $requestCount = 0;
	public ?string $shippedPostingNumber = null;

	/** @var list<array<string, mixed>> */
	public array $shippedPackages = [];

	/**
	 * @param   stdClass  $response  Simulated Ozon response.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct(private readonly stdClass $response)
	{
	}

	/**
	 * @param   array<string, mixed>  $filter  Ozon posting filters.
	 * @param   array<string, bool>   $with    Optional response sections.
	 *
	 * @since 1.0.0
	 */
	public function getPostings(
		array $filter,
		int $limit = 1000,
		string $cursor = '',
		string $direction = 'ASC',
		array $with = []
	): stdClass {
		$this->lastFilter = $filter;
		$this->requestCount++;

		return $this->response;
	}

	/**
	 * Return normalized Ozon orders.
	 *
	 * @param   int           $limit         Maximum orders.
	 * @param   int           $lookbackDays  Lookback period.
	 * @param   list<string>  $statuses      Posting statuses.
	 * @param   string        $cursor        Pagination cursor.
	 *
	 * @return list<MarketplaceOrder> Normalized orders.
	 *
	 * @since 1.0.0
	 */
	public function getOrders(
		int $limit = 10,
		int $lookbackDays = 5,
		array $statuses = ['awaiting_packaging'],
		string $cursor = ''
	): array {
		$this->lastFilter = ['status' => $statuses];
		$this->requestCount++;
		$orders = [];

		foreach (array_slice($this->response->postings ?? [], 0, $limit) as $posting)
		{
			$customer = $posting->customer ?? new stdClass();
			$address = $customer->address ?? new stdClass();
			$addressee = $posting->addressee ?? new stdClass();
			$nameParts = preg_split('/\s+/u', trim((string) ($addressee->name ?? '')), 3) ?: [];
			$items = [];

			foreach ($posting->products ?? [] as $product)
			{
				$items[] = new MarketplaceOrderItem(
					productId: (int) ($product->sku ?? 0),
					quantity: (float) ($product->quantity ?? 1),
					price: (float) ($product->price ?? 0),
					name: (string) ($product->name ?? '')
				);
			}

			$orders[] = new MarketplaceOrder(
				sourceId: (string) ($posting->order_id ?? $posting->posting_number),
				externalId: (string) $posting->posting_number,
				items: $items,
				firstName: (string) ($nameParts[0] ?? 'Ozon'),
				lastName: (string) ($nameParts[1] ?? 'Customer'),
				phone: (string) ($addressee->phone ?? ''),
				email: (string) ($customer->customer_email ?? ''),
				delivery: new MarketplaceOrderDelivery(
					country: (string) ($address->country ?? ''),
					region: (string) ($address->region ?? ''),
					city: (string) ($address->city ?? ''),
					zip: (string) ($address->zip_code ?? ''),
					text: (string) ($address->address_tail ?? '')
				)
			);
		}

		return $orders;
	}

	/**
	 * Return a simulated Ozon posting.
	 *
	 * @param   string  $postingNumber  Ozon posting number.
	 * @param   array<string, bool>  $with  Optional response sections.
	 *
	 * @return stdClass Simulated posting.
	 *
	 * @since 1.0.0
	 */
	public function getPosting(string $postingNumber, array $with = []): stdClass
	{
		foreach ($this->response->postings ?? [] as $posting)
		{
			if ((string) ($posting->posting_number ?? '') === $postingNumber)
			{
				return $posting;
			}
		}

		return new stdClass();
	}

	/**
	 * Record a simulated Ozon shipment.
	 *
	 * @param   string                      $postingNumber  Ozon posting number.
	 * @param   list<array<string, mixed>>  $packages       Ozon packages.
	 * @param   array<string, bool>         $with           Optional response sections.
	 *
	 * @return stdClass Simulated response.
	 *
	 * @since 1.0.0
	 */
	public function ship(string $postingNumber, array $packages, array $with = []): stdClass
	{
		$this->shippedPostingNumber = $postingNumber;
		$this->shippedPackages = $packages;

		return new stdClass();
	}
}
