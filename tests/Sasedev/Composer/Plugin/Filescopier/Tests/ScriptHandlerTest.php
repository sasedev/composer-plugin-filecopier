<?php

namespace Sasedev\Composer\Plugin\Filescopier\Tests;

use Composer\Autoload\AutoloadGenerator;
use Composer\Composer;
use Composer\Config;
use Composer\Installer\PluginInstaller;
use Composer\Package\CompletePackage;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\JsonLoader;
use Composer\Plugin\PluginManager;
use Composer\TestCase;
use Composer\Util\Filesystem;
use Sasedev\Composer\Plugin\Filescopier\ScriptHandler;

/**
 *
 * @author sasedev <seif.salah@gmail.com>
 */
class ScriptHandlerTest extends TestCase
{

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
			$this->returnCallback(
				function ($package)
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
		$this->assertEquals('installer-v1', $plugins[0]->version);

	}

}