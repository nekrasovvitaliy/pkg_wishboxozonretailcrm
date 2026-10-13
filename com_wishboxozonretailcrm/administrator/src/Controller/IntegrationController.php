<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxOzonRetailCrm\Administrator\Controller;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Component\WishboxOzonRetailCrm\Administrator\Model\IntegrationModel;
use RuntimeException;
use Throwable;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Handles integration module update requests.
 *
 * @since 1.0.0
 *
 * @noinspection PhpUnused
 */
final class IntegrationController extends BaseController
{
	/**
	 * Create or update the RetailCRM integration module.
	 *
	 * @return void
	 *
	 * @noinspection PhpUnused
	 *
	 * @throws Exception
	 *
	 * @since        1.0.0
	 */
	public function update(): void
	{
		$this->checkToken();

		if (!$this->app->getIdentity()->authorise('core.manage', 'com_wishboxozonretailcrm'))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		try
		{
			/** @var IntegrationModel $model */
			$model = $this->getModel('Integration');

			$model->updateIntegration();
			$this->app->enqueueMessage(Text::_('COM_WISHBOXOZONRETAILCRM_UPDATE_SUCCESS'));
		}
		catch (Throwable $throwable)
		{
			$this->app->enqueueMessage(
				Text::sprintf('COM_WISHBOXOZONRETAILCRM_UPDATE_ERROR', $throwable->getMessage()),
				'error'
			);
		}

		$this->setRedirect(
			Route::_('index.php?option=com_wishboxozonretailcrm&view=integration', false)
		);
	}
}
