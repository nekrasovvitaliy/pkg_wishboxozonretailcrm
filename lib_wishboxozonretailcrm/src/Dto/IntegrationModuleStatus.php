<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Dto;

use RetailCrm\Api\Model\Entity\Integration\IntegrationModule;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Describes the current RetailCRM integration module state.
 *
 * @since 1.0.0
 */
final readonly class IntegrationModuleStatus
{
	/**
	 * Create an integration module status.
	 *
	 * @param   bool                    $installed       Module exists in RetailCRM.
	 * @param   bool                    $updateRequired  Managed values differ.
	 * @param   array<int, string>      $differences     Different property paths.
	 * @param   IntegrationModule|null  $current         Current RetailCRM module.
	 * @param   IntegrationModule       $expected        Expected module definition.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		public bool $installed,
		public bool $updateRequired,
		public array $differences,
		public ?IntegrationModule $current,
		public IntegrationModule $expected
	) {
	}
}
