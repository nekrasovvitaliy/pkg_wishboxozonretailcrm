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
 * Provides Ozon product synchronization service injection.
 *
 * @since 1.0.0
 */
trait OzonProductSyncServiceAwareTrait
{
	private ?OzonProductSyncService $ozonProductSyncService = null;

	protected function getOzonProductSyncService(): OzonProductSyncService
	{
		if ($this->ozonProductSyncService instanceof OzonProductSyncService)
		{
			return $this->ozonProductSyncService;
		}

		throw new UnexpectedValueException('OzonProductSyncService not set in ' . static::class . '.');
	}

	public function setOzonProductSyncService(OzonProductSyncService $ozonProductSyncService): void
	{
		$this->ozonProductSyncService = $ozonProductSyncService;
	}
}
