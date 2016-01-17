<?php

namespace Sasedev\Composer\Plugin\Filescopier\Tests;

use Composer\Autoload\AutoloadGenerator;
use Composer\Composer;
use Composer\Config;
use Composer\Installer\PluginInstaller;
use Composer\Package\AliasPackage;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\JsonLoader;
use Composer\Package\CompletePackage;
use Composer\Plugin\PluginManager;
use Composer\Semver\VersionParser;
use Composer\Semver\Constraint\Constraint;
use Composer\Util\Filesystem;

/**
 *
 * @author sasedev <seif.salah@gmail.com>
 */
class ScriptHandlerTest extends \PHPUnit_Framework_TestCase
{

	private static $parser;

	protected static function getVersionParser()
	{

		if (!self::$parser) {
			self::$parser = new VersionParser();
		}

		return self::$parser;

	}

	protected function getVersionConstraint($operator, $version)
	{

		$constraint = new Constraint($operator, self::getVersionParser()->normalize($version));

		$constraint->setPrettyString($operator . ' ' . $version);

		return $constraint;

	}

	protected function getPackage($name, $version, $class = 'Composer\Package\Package')
	{

		$normVersion = self::getVersionParser()->normalize($version);

		return new $class($name, $normVersion, $version);

	}

	protected function getAliasPackage($package, $version)
	{

		$normVersion = self::getVersionParser()->normalize($version);

		return new AliasPackage($package, $normVersion, $version);

	}

	protected function ensureDirectoryExistsAndClear($directory)
	{

		$fs = new Filesystem();
		if (is_dir($directory)) {
			$fs->removeDirectory($directory);
		}
		mkdir($directory, 0777, true);

	}

	/**
	 *
	 * @var Composer
	 */
	protected $composer;

	/**
	 *
	 * @var PluginManager
	 */
	protected $pm;

	/**
	 *
	 * @var AutoloadGenerator
	 */
	protected $autoloadGenerator;

	/**
	 *
	 * @var CompletePackage
	 */
	protected $package;

	/**
	 *
	 * @var string
	 */
	protected $directory;

	/**
	 *
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $im;

	/**
	 *
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $repository;

	/**
	 *
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $io;

	protected function setUp()
	{

		$loader = new JsonLoader(new ArrayLoader());
		$this->directory = sys_get_temp_dir() . '/' . uniqid();
		$filename = '/Fixtures/composer.json';
		mkdir(dirname($this->directory . $filename), 0777, true);
		$this->package = $loader->load(__DIR__ . $filename);

		$dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
			->disableOriginalConstructor()
			->getMock();

		$this->repository = $this->getMock('Composer\Repository\InstalledRepositoryInterface');

		$rm = $this->getMockBuilder('Composer\Repository\RepositoryManager')
			->disableOriginalConstructor()
			->getMock();
		$rm->expects($this->any())
			->method('getLocalRepository')
			->will($this->returnValue($this->repository));

		$im = $this->getMock('Composer\Installer\InstallationManager');
		$im->expects($this->any())
			->method('getInstallPath')
			->will(
			$this->returnCallback(function ($package)
			{
				return __DIR__ . '/Fixtures/' . $package->getPrettyName();
			}));

		$this->io = $this->getMock('Composer\IO\IOInterface');

		$dispatcher = $this->getMockBuilder('Composer\EventDispatcher\EventDispatcher')
			->disableOriginalConstructor()
			->getMock();
		$this->autoloadGenerator = new AutoloadGenerator($dispatcher);

		$this->composer = new Composer();
		$config = new Config();
		$this->composer->setConfig($config);
		$this->composer->setDownloadManager($dm);
		$this->composer->setRepositoryManager($rm);
		$this->composer->setInstallationManager($im);
		$this->composer->setAutoloadGenerator($this->autoloadGenerator);

		$this->pm = new PluginManager($this->io, $this->composer);
		$this->composer->setPluginManager($this->pm);

		$config->merge(
			array(
				'config' => array(
					'vendor-dir' => $this->directory . '/Fixtures/',
					'home' => $this->directory . '/Fixtures',
					'bin-dir' => $this->directory . '/Fixtures/bin'
				)
			));

	}

	protected function tearDown()
	{

		$filesystem = new Filesystem();
		$filesystem->removeDirectory($this->directory);

	}

	public function testInstallNewPlugin()
	{

		$this->repository->expects($this->exactly(2))
			->method('getPackages')
			->will($this->returnValue(array()));
		$installer = new PluginInstaller($this->io, $this->composer);
		$this->pm->loadInstalledPlugins();

		$installer->install($this->repository, $this->package);

		$plugins = $this->pm->getPlugins();
		$this->assertEquals('sasedev/composer-plugin-filecopier-test', $plugins[0]->version);

	}

}