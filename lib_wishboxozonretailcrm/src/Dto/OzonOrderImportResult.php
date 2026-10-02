<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Dto;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Summarizes one Ozon-to-RetailCRM order import run.
 *
 * @since 1.0.0
 */
final class OzonOrderImportResult
{
	/**
	 * Number of RetailCRM orders created.
	 *
	 * @since 1.0.0
	 */
	public int $created = 0;

	/**
	 * Number of previously imported Ozon postings skipped.
	 *
	 * @since 1.0.0
	 */
	public int $skipped = 0;

	/**
	 * Number of Ozon postings that could not be imported.
	 *
	 * @since 1.0.0
	 */
	public int $failed = 0;

	/**
	 * @var array<string, int> RetailCRM order IDs indexed by Ozon posting number.
	 *
	 * @since 1.0.0
	 */
	public array $orderIds = [];

	/**
	 * @var array<string, string> Error messages indexed by Ozon posting number.
	 *
	 * @since 1.0.0
	 */
	public array $errors = [];
}
