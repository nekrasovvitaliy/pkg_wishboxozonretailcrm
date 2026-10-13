<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Stubs\WishboxRetailCrmLibrary\Service;

/**
 * Supplies normalized RetailCRM products without making API requests.
 *
 * @since 1.0.0
 */
final readonly class RetailCrmProductService
{
	/**
	 * Initialise the RetailCRM product stub.
	 *
	 * @param   list<array{offer_id: string, product_id: int, price: float|null, stock: int}>  $products  Products to return.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct(private array $products)
	{
	}

	/**
	 * Return the configured products.
	 *
	 * @param   int     $maxProducts       Maximum products to return.
	 * @param   string  $productIdProperty  External product ID property code.
	 * @param   string  $priceType          RetailCRM price type code.
	 *
	 * @return list<array{offer_id: string, product_id: int, price: float|null, stock: int}>
	 *
	 * @since 1.0.0
	 */
	public function getProducts(
		int $maxProducts = 1000,
		string $productIdProperty = '',
		string $priceType = 'base'
	): array {
		return array_slice($this->products, 0, $maxProducts);
	}
}
