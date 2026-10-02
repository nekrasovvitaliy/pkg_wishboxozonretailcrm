<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use Joomla\Registry\Registry;
use Throwable;
use WishboxOzonLibrary\Service\OzonProductService;
use WishboxOzonRetailCrmLibrary\Dto\ProductSyncResult;
use WishboxOzonRetailCrmLibrary\Repositories\RetailCrmOfferRepository;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Synchronises RetailCRM offer prices and quantities with Ozon.
 *
 * @since 1.0.0
 */
final class OzonProductSyncService
{
	private const int BATCH_SIZE = 100;

	public function __construct(
		private readonly RetailCrmOfferRepository $retailCrmOfferRepository,
		private readonly OzonProductService $ozonProductService,
		private readonly Registry $params
	) {
	}

	/**
	 * Update Ozon prices and stocks for mapped RetailCRM offers.
	 *
	 * @since 1.0.0
	 */
	public function updateProducts(int $maxOffers = 1000): ProductSyncResult
	{
		$result = new ProductSyncResult();
		$warehouseId = (int) $this->params->get('ozon_warehouse_id');
		$currencyCode = trim((string) $this->params->get('ozon_currency_code', 'RUB'));
		$updates = [];

		if ($warehouseId < 1)
		{
			$result->failed = 1;
			$result->errors[] = 'Ozon warehouse ID must be configured.';

			return $result;
		}

		foreach ($this->retailCrmOfferRepository->getOffers($maxOffers) as $offer)
		{
			$productId = $this->retailCrmOfferRepository->getOzonProductId($offer);
			$price = $this->retailCrmOfferRepository->getPrice($offer);
			$offerId = trim($offer->externalId ?? '');

			if ($productId < 1 || $price === null || $offerId === '')
			{
				$result->skipped++;

				continue;
			}

			$updates[] = [
				'price' => [
					'offer_id'     => $offerId,
					'product_id'   => $productId,
					'price'        => number_format($price, 2, '.', ''),
					'currency_code' => $currencyCode,
				],
				'stock' => [
					'offer_id'    => $offerId,
					'product_id'  => $productId,
					'stock'       => $this->retailCrmOfferRepository->getStock($offer),
					'warehouse_id' => $warehouseId,
				],
			];
		}

		foreach (array_chunk($updates, self::BATCH_SIZE) as $batch)
		{
			try
			{
				$this->ozonProductService->updatePrices(array_column($batch, 'price'));
				$this->ozonProductService->updateStocks(array_column($batch, 'stock'));
				$result->updated += count($batch);
			}
			catch (Throwable $throwable)
			{
				$result->failed += count($batch);
				$result->errors[] = $throwable->getMessage();
			}
		}

		return $result;
	}
}
