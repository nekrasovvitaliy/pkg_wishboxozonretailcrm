<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use UnexpectedValueException;

defined('_JEXEC') or die;

trait OzonOrderImportServiceAwareTrait
{
	private ?OzonOrderImportService $ozonOrderImportService = null;

	protected function getOzonOrderImportService(): OzonOrderImportService
	{
		if ($this->ozonOrderImportService instanceof OzonOrderImportService)
		{
			return $this->ozonOrderImportService;
		}

		throw new UnexpectedValueException('OzonOrderImportService not set in ' . static::class . '.');
	}

	public function setOzonOrderImportService(OzonOrderImportService $ozonOrderImportService): void
	{
		$this->ozonOrderImportService = $ozonOrderImportService;
	}
}
