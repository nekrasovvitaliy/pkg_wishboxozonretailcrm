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
 * Defines the contract for classes that depend on Ozon product synchronization.
 *
 * @since 1.0.0
 */
interface OzonProductSyncServiceAwareInterface
{
	public function setOzonProductSyncService(OzonProductSyncService $ozonProductSyncService): void;
}
