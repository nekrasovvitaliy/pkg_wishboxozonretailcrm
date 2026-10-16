<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

declare(strict_types=1);

namespace Tests\Unit\Plugin;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the webservices plugin route-only structure.
 *
 * @since 1.0.0
 */
final class WebservicesPluginStructureTest extends TestCase
{
	private string $projectRoot;

	/**
	 * Resolve the package project root.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	protected function setUp(): void
	{
		$this->projectRoot = dirname(__DIR__, 3);
	}

	/**
	 * Verify that only component-backed routes are registered.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testRegistersOnlyComponentBackedRoutes(): void
	{
		$extension = $this->read(
			'plg_webservices_wishboxozonretailcrm/src/Extension/WishboxOzonRetailCrm.php'
		);

		self::assertStringContainsString("'wishboxozon.print'", $extension);
		self::assertStringContainsString("'orders.ship'", $extension);
		self::assertSame(2, substr_count($extension, "'component' => 'com_wishboxozonretailcrm'"));
		self::assertStringNotContainsString('callback.handle', $extension);
		self::assertStringNotContainsString('com_jshopping', $extension);
	}

	/**
	 * Verify that the route-only plugin does not build application services.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testProviderContainsOnlyPluginConstruction(): void
	{
		$provider = $this->read('plg_webservices_wishboxozonretailcrm/services/provider.php');

		self::assertStringContainsString('new WishboxOzonRetailCrm(', $provider);
		self::assertStringNotContainsString('OzonOrderImportService', $provider);
		self::assertStringNotContainsString('registerServiceProvider', $provider);
	}

	/**
	 * Read a project file.
	 *
	 * @param   string  $relativePath  Project-relative file path.
	 *
	 * @return string File contents.
	 *
	 * @since 1.0.0
	 */
	private function read(string $relativePath): string
	{
		$contents = file_get_contents($this->projectRoot . '/' . $relativePath);

		self::assertNotFalse($contents);

		return $contents;
	}
}
