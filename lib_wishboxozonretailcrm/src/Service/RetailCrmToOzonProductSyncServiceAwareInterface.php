<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Defines the contract for classes that depend on RetailCRM to Ozon product synchronization.
 *
 * @since 1.0.0
 */
interface RetailCrmToOzonProductSyncServiceAwareInterface
{
	/**
	 * Set the RetailCRM to Ozon product synchronization service.
	 *
	 * @param   RetailCrmToOzonProductSyncService  $retailCrmToOzonProductSyncService  Product synchronization service.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function setRetailCrmToOzonProductSyncService(
		RetailCrmToOzonProductSyncService $retailCrmToOzonProductSyncService
	): void;
}
