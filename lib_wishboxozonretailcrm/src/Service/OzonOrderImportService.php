<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license         GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use DateMalformedStringException;
use Joomla\Registry\Registry;
use Wishbox\Dto\OrderImport\OrderImportResult;
use WishboxOzonLibrary\Service\OzonFbsService;
use WishboxRetailCrmLibrary\Service\RetailCrmOrderService;

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
	/**
	 * Initialize the Ozon-to-RetailCRM order import service.
	 *
	 * @param   OzonFbsService         $ozonFbsService         Ozon order source.
	 * @param   RetailCrmOrderService  $retailCrmOrderService  RetailCRM order destination.
	 * @param   Registry               $params                 Integration parameters.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		private OzonFbsService $ozonFbsService,
		private RetailCrmOrderService $retailCrmOrderService,
		private Registry $params
	) {
	}

	/**
	 * Import one cursor page of Ozon postings that do not exist in RetailCRM.
	 *
	 * @param   int     $limit   Maximum postings per run.
	 * @param   string  $cursor  Ozon pagination cursor.
	 *
	 * @return OrderImportResult Import result.
	 *
	 * @throws DateMalformedStringException When the date interval cannot be created.
	 *
	 * @since 1.0.0
	 */
	public function importNewOrders(int $limit = 10, string $cursor = ''): OrderImportResult
	{
		$lookbackDays = min(365, max(1, (int) $this->params->get('order_lookback_days', 5)));
		$statuses = explode(',', (string) $this->params->get('ozon_order_statuses', 'awaiting_packaging'))
			|> (fn ($items) => array_map('trim', $items))
				|> array_filter(...)
				|> array_values(...);
		$orders = $this->ozonFbsService->getOrders($limit, $lookbackDays, $statuses, $cursor);

		return $this->retailCrmOrderService->importOrders(
			$orders,
			[
				'product_id_property' => trim((string) $this->params->get('ozon_sku_property', 'ozon_sku')),
				'offer_lookup_limit'  => (int) $this->params->get('retailcrm_offer_lookup_limit', 10000),
				'price_type'          => (string) $this->params->get('retailcrm_price_type', 'base'),
				'currency'            => (string) $this->params->get('order_currency', 'RUB'),
				'order_type'          => (string) $this->params->get('retailcrm_order_type', 'eshop-individual'),
				'order_method'        => (string) $this->params->get('retailcrm_order_method', 'shopping-cart'),
				'order_status'        => (string) $this->params->get('retailcrm_order_status', 'new'),
				'marketplace_code'    => (string) $this->params->get('retailcrm_marketplace_code'),
				'source_order_field'  => (string) $this->params->get('retailcrm_ozon_posting_field'),
			]
		);
	}
}
