<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use UnexpectedValueException;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Provides RetailCRM to Ozon product synchronization service injection.
 *
 * @since 1.0.0
 */
trait RetailCrmToOzonProductSyncServiceAwareTrait
{
	private ?RetailCrmToOzonProductSyncService $retailCrmToOzonProductSyncService = null;

	/**
	 * Get the RetailCRM to Ozon product synchronization service.
	 *
	 * @return RetailCrmToOzonProductSyncService Product synchronization service.
	 *
	 * @since 1.0.0
	 */
	protected function getRetailCrmToOzonProductSyncService(): RetailCrmToOzonProductSyncService
	{
		if ($this->retailCrmToOzonProductSyncService instanceof RetailCrmToOzonProductSyncService)
		{
			return $this->retailCrmToOzonProductSyncService;
		}

		throw new UnexpectedValueException(
			'RetailCrmToOzonProductSyncService not set in ' . static::class . '.'
		);
	}

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
	): void
	{
		$this->retailCrmToOzonProductSyncService = $retailCrmToOzonProductSyncService;
	}
}
