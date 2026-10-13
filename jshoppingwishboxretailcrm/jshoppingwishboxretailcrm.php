<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Webservices.Wishboxjshoppingretailcrm.php
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;
use Joomla\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

\JLoader::registerNameSpace('Joomla\Component\Jshopping\Api\Controller', JPATH_SITE.'/api/components/com_jshopping/src/Controller');

/**
 * Web Services adapter for com_jshopping.
 *
 * @since  4.0.0
 */
class PlgWebservicesJshoppingwishboxretailcrm extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Registers com_jshopping's API's routes in the application
	 *
	 * @param   ApiRouter  &$router  The API Routing object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onBeforeApiRoute(&$router)
	{
		/** @OA\Tag(
		 *     name="Orders",
		 *     description="User related operations"
		 * )
		 * @OA\Info(
		 *     title="WishBox API", version="1.0"
		 * )
		 * @OA\Server(
		 *     url="https://example.localhost",
		 *     description="API server"
		 * )
		 */
		$routes = [
					new Route(
								['PATCH'],
								'v1/jshopping/wishboxretailcrm/updateshippingnumber',
								'wishboxretailcrm.updateShippingNumber',
								[],
								['component' => 'com_jshopping']
							),
					new Route(
								['PATCH'],
								'v1/jshopping/wishboxretailcrm/updatetracknumber',
								'wishboxretailcrm.updateTrackNumber',
								[],
								['component' => 'com_jshopping']
							),
					/**
					 * @OA\Patch(
					 * path="/v1/jshopping/wishboxretailcrm/order/{jshoppingorder_id}/updatestatus",
					 * summary="Update order status.", tags={"Orders"},
					 * @OA\Parameter(
					 *    name="jshoppingorder_id",
					 *    in="path",
					 *    required=true,
					 *    description="The order id in JShopping.",
					 *    @OA\Schema(
					 *       type="string"
					 *    ),
					 * ),
					 * @OA\RequestBody(
					 *    @OA\MediaType(
					 *        mediaType="json",
					 *        @OA\Schema(
					 *            @OA\Property(
					 *                property="retailcrmorderstatuscode",
					 *                type="string",
					 *            ),
					 *        ),
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['PATCH'],
								'v1/jshopping/wishboxretailcrm/order/:jshoppingorder_id/updatestatus',
								'wishboxretailcrm.updateStatus',
								['jshoppingorder_id' => '(\d+)'],
								['component' => 'com_jshopping']
							),
					/**
					 * @OA\Patch(
					 * path="/v1/jshopping/wishboxwildberries/orders/{wildberriesorder_id}/ship",
					 * summary="Adds an order to the opened supply, creates a new supply in it exists.", tags={"Orders"},
					 * @OA\Parameter(
					 *    name="wildberriesorder_id",
					 *    in="path",
					 *    required=true,
					 *    description="The order id in Wildberries.",
					 *    @OA\Schema(
					 *       type="string"
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['PATCH'],
								'v1/jshopping/wishboxwildberries/orders/:wildberriesorder_id/ship',
								'wishboxwildberries.orderShip',
								['wildberriesorder_id' => '(\w+)'],
								['component' => 'com_jshopping']
							),
					/**
					 * @OA\Post(
					 * path="/v1/jshopping/wishboxretailcrm/products/updatequantityfromorder/{retailcrmorder_id}",
					 * summary="Updates quantity for products from order.", tags={"JShopping products"},
					 * @OA\Parameter(
					 *    name="retailcrmorder_id",
					 *    in="path",
					 *    required=true,
					 *    description="The order id in retailCRM.",
					 *    @OA\Schema(
					 *       type="int"
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['POST'],
								'v1/jshopping/wishboxretailcrm/products/updatequantityfromorder/:retailcrmorder_id',
								'wishboxretailcrm.productsUpdateQuantityFromOrder',
								['retailcrmorder_id' => '(\d+)'],
								['component' => 'com_jshopping']
							),
					/**
					 * @OA\Patch(
					 * path="/v1/jshopping/wishboxretailcrm/orders/{retailcrmorder_id}/updatesumtowords",
					 * summary="Converts sum to words in order.", tags={"retailCRM orders"},
					 * @OA\Parameter(
					 *    name="retailcrmorder_id",
					 *    in="path",
					 *    required=true,
					 *    description="The order id in retailCRM.",
					 *    @OA\Schema(
					 *       type="int"
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['PATCH'],
								'v1/jshopping/wishboxretailcrm/orders/:retailcrmorder_id/updatesumtowords',
								'wishboxretailcrm.ordersUpdatesumtowords',
								['retailcrmorder_id' => '(\d+)'],
								['component' => 'com_jshopping']
							),
					/**
					 * @OA\Patch(
					 * path="/v1/jshopping/wishboxretailcrm/orders/{retailcrmorder_id}/createpaymentlink",
					 * summary="Creates payment link in order.", tags={"retailCRM orders"},
					 * @OA\Parameter(
					 *    name="retailcrmorder_id",
					 *    in="path",
					 *    required=true,
					 *    description="The order id in retailCRM.",
					 *    @OA\Schema(
					 *       type="int"
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['PATCH'],
								'v1/jshopping/wishboxretailcrm/orders/:retailcrmorder_id/createpaymentlink',
								'wishboxretailcrm.ordersCreatepaymentlink',
								['retailcrmorder_id' => '(\d+)'],
								['component' => 'com_jshopping']
							),
					/**
					 * @OA\Patch(
					 * path="/v1/jshopping/wishboxretailcrm/orders/{retailcrmorder_id}/createrussianpostshippingorder",
					 * summary="Creates Russian post shipping order.", tags={"retailCRM orders"},
					 * @OA\Parameter(
					 *    name="retailcrmorder_id",
					 *    in="path",
					 *    required=true,
					 *    description="The order id in retailCRM.",
					 *    @OA\Schema(
					 *       type="int"
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['PATCH'],
								'v1/jshopping/wishboxretailcrm/orders/:retailcrmorder_id/createrussianpostshippingorder',
								'wishboxretailcrm.ordersCreaterussianpostshippingorder',
								['retailcrmorder_id' => '(\d+)'],
								['component' => 'com_jshopping']
							),
					/**
					 * @OA\Patch(
					 * path="/v1/jshopping/wishboxozon/orders/{ozonorder_id}/ship",
					 * summary="Order ship.", tags={"Ozon orders"},
					 * @OA\Parameter(
					 *    name="ozonorder_id",
					 *    in="path",
					 *    required=true,
					 *    description="The order id in Ozon.",
					 *    @OA\Schema(
					 *       type="string"
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['PATCH'],
								'v1/jshopping/wishboxozon/orders/:ozonorder_id/ship',
								'wishboxozon.orderShip',
								['ozonorder_id' => '(\w+)'],
								['component' => 'com_jshopping']
							),
					/**
					 * @OA\Post(
					 * path="/v1/jshopping/wishboxretailcrm/products/updatequantity",
					 * summary="Updates quantity for products by retailCRM offer external ids.", tags={"JShopping products"},
					 * @OA\RequestBody(
					 *    @OA\MediaType(
					 *        mediaType="json",
					 *        @OA\Schema(
					 *            @OA\Property(
					 *                property="retailcrmoffer_externalids",
					 *                type="array",
					 *				  example={"5632452", "2345262"},
					 *                @OA\Items(
					 *                      type="string"
					 *                  )
					 *            ),
					 *        ),
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['POST'],
								'v1/jshopping/wishboxretailcrm/products/updatequantity',
								'wishboxretailcrm.productsUpdateQuantity',
								[],
								['component' => 'com_jshopping']
							),
					/**
					 * @OA\Get(
					 * path="/v1/jshopping/wishboxozon/print",
					 * summary="Returns documents for print", tags={"Ozon"},
					 * @OA\Parameter(
					 *    name="print",
					 *    in="query",
					 *    required=true,
					 *    description="Print data.",
					 *    @OA\Schema(
					 *       type="string",
					 *					 
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['POST'],
								'v1/jshopping/wishboxozon/print',
								'wishboxozon.print',
								[],
								[
									'component'	=> 'com_jshopping',
									'public'	=> true
								]
							),
					/**
					 * @OA\Get(
					 * path="/v1/jshopping/wishboxwildberries/print",
					 * summary="Returns documents for print", tags={"Wildberries"},
					 * @OA\Parameter(
					 *    name="print",
					 *    in="query",
					 *    required=true,
					 *    description="Print data.",
					 *    @OA\Schema(
					 *       type="string",
					 *       
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['POST'],
								'v1/jshopping/wishboxwildberries/print',
								'wishboxwildberries.print',
								[],
								[
									'component'	=> 'com_jshopping',
									'public'	=> true
								]
							),
					/**
					 * @OA\Post(
					 * path="/v1/jshopping/wishboxvk/callback",
					 * summary="Receives callback requests", tags={"VK"},
					 * @OA\RequestBody(
					 *    @OA\MediaType(
					 *        mediaType="json",
					 *    ),
					 * ),
					 * @OA\Response(response="200", description="Success"),
					 * @OA\Response(response="404", description="Not found"),
					 * )
					 */
					new Route(
								['POST'],
								'v1/jshopping/wishboxvk/callback',
								'wishboxvk.callback',
								[],
								[
									'component'	=> 'com_jshopping',
									'public'	=> true
								]
							),
				];
		// Add routes
		$router->addRoutes($routes);
	}
}