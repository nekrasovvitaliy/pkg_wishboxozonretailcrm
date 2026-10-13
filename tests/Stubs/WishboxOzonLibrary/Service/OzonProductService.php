<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Stubs\WishboxOzonLibrary\Service;

use RuntimeException;
use stdClass;

/**
 * Records Ozon price and stock batches without making network requests.
 *
 * @since 1.0.0
 */
final class OzonProductService
{
	/** @var list<array{offer_id: string, product_id: int, price: float|null, stock: int}> */
	public array $products = [];

	public int $warehouseId = 0;

	public string $currencyCode = '';

	/** @var array{updated: int, skipped: int, failed: int, errors: list<string>} */
	public array $result = [
		'updated' => 0,
		'skipped' => 0,
		'failed'  => 0,
		'errors'  => [],
	];

	/** @var list<list<array<string, mixed>>> */
	public array $priceBatches = [];

	/** @var list<list<array<string, mixed>>> */
	public array $stockBatches = [];

	public bool $fail = false;

	/**
	 * Record normalized product synchronization input.
	 *
	 * @param   list<array{offer_id: string, product_id: int, price: float|null, stock: int}>  $products      Products to update.
	 * @param   int                                                                            $warehouseId  Ozon warehouse ID.
	 * @param   string                                                                         $currencyCode Ozon currency code.
	 *
	 * @return array{updated: int, skipped: int, failed: int, errors: list<string>}
	 *
	 * @since 1.0.0
	 */
	public function updateProducts(array $products, int $warehouseId, string $currencyCode = 'RUB'): array
	{
		$this->products = $products;
		$this->warehouseId = $warehouseId;
		$this->currencyCode = $currencyCode;

		return $this->result;
	}

	/**
	 * @param   list<array<string, mixed>>  $prices  Price update records.
	 *
	 * @since 1.0.0
	 */
	public function updatePrices(array $prices): stdClass
	{
		if ($this->fail)
		{
			throw new RuntimeException('Ozon request failed.');
		}

		$this->priceBatches[] = $prices;

		return (object) [];
	}

	/**
	 * @param   list<array<string, mixed>>  $stocks  Stock update records.
	 *
	 * @since 1.0.0
	 */
	public function updateStocks(array $stocks): stdClass
	{
		if ($this->fail)
		{
			throw new RuntimeException('Ozon request failed.');
		}

		$this->stockBatches[] = $stocks;

		return (object) [];
	}
}
