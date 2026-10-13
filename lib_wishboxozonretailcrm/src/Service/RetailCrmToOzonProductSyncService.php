<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license         GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use Joomla\Registry\Registry;
use WishboxOzonLibrary\Service\OzonProductService;
use WishboxOzonRetailCrmLibrary\Dto\ProductSyncResult;
use WishboxRetailCrmLibrary\Service\RetailCrmProductService;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Synchronises RetailCRM offer prices and quantities with Ozon.
 *
 * @since 1.0.0
 */
final readonly class RetailCrmToOzonProductSyncService
{
	/**
	 * Initialize the product synchronization service.
	 *
	 * @param   RetailCrmProductService  $retailCrmProductService  RetailCRM product source.
	 * @param   OzonProductService       $ozonProductService       Ozon product destination.
	 * @param   Registry                 $params                   Integration parameters.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		private RetailCrmProductService $retailCrmProductService,
		private OzonProductService      $ozonProductService,
		private Registry                $params
	)
	{
	}

	/**
	 * Update Ozon prices and stocks for mapped RetailCRM offers.
	 *
	 * @param   int  $maxOffers  Maximum RetailCRM offers to process.
	 *
	 * @return ProductSyncResult Synchronization result.
	 *
	 * @since 1.0.0
	 */
	public function updateProducts(int $maxOffers = 1000): ProductSyncResult
	{
		$productIdProperty = trim(
			(string) $this->params->get('ozon_product_id_property', 'ozon_product_id')
		);
		$priceType = trim((string) $this->params->get('retailcrm_price_type', 'base'));
		$warehouseId = (int) $this->params->get('ozon_warehouse_id');
		$currencyCode = trim((string) $this->params->get('ozon_currency_code', 'RUB'));

		$products = $this->retailCrmProductService->getProducts(
			$maxOffers,
			$productIdProperty,
			$priceType
		);

		$result = $this->ozonProductService->updateProducts(
			$products,
			$warehouseId,
			$currencyCode
		);

		return new ProductSyncResult(...$result);
	}
}
