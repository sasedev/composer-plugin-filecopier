<?php

namespace Sasedev\Composer\Plugin\Filescopier;

use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Script\Event;

/**
 *
 * @author sasedev <seif.salah@gmail.com>
 */
class Processor
{

	/**
	 *
	 * @var IOInterface
	 */
	private $io;

	/**
	 *
	 * @var Composer $composer
	 */
	private $composer;

	public function __construct(Event $ev)
	{

		$this->io = $ev->getIO();

		$this->composer = $ev->getComposer();
		/*

		if (\defined('FNM_PATHNAME')) {
			define('FNM_PATHNAME', 1);
		}

		if (\defined('FNM_NOESCAPE')) {
			define('FNM_NOESCAPE', 2);
		}

		if (\defined('FNM_PERIOD')) {
			define('FNM_PERIOD', 4);
		}

		if (\defined('FNM_CASEFOLD')) {
			define('FNM_CASEFOLD', 16);
		}

		*/

	}

	public function processCopy(array $config)
	{

		$config = $this->processConfig($config);

		//$src = $config['src'];

		//$dest = $config['dest'];


		$home = $this->composer->getConfig()->get('home');
		$this->io->write('current home : '.$home);
		$current_dir = __DIR__;
		$this->io->write('current dir : '.$current_dir);
		$vendor_dir = $this->composer->getConfig()->get('vendor-dir');
		$this->io->write('current vendor dir : '.$vendor_dir);
		$bin_dir = $this->composer->getConfig()->get('bin-dir');
		$this->io->write('current bin dir : '.$bin_dir);

		$config_all = $this->composer->getConfig()->all();
		foreach ($config_all['config'] as $key => $value) {
			$this->io->write('key : '.$key.'   |   value : '.$value);
		}

		/*

		if (!is_dir($dest)) {
			mkdir($dest);
		}

		$is_regex = false;
		if( preg_match("/^\/.+\/[a-z]*$/i",$src)) {
			$is_regex = true;
		}

		if (!$is_regex) {
			$this->recurse_copy($src, $dest);
		}

		$srcTree = $this->folder_tree($src, 0, '/path_here/', -1);
		*/

	}

	private function processConfig(array $config)
	{

		if (empty($config['src'])) {
			throw new \InvalidArgumentException('The extra.filescopier.src setting is required to use this script handler.');
		}

		if (empty($config['dest'])) {
			throw new \InvalidArgumentException('The extra.filescopier.dest setting is required to use this script handler.');
		}

		if (empty($config['level']) || !\is_int($config['level'])) {
			$config['level'] = -1;
		}

		return $config;

	}

	function folder_tree($pattern = '*', $flags = 0, $path = false, $depth = 0, $level = 0) {
		$tree = array();

		$files = glob($path.$pattern, $flags);
		$paths = glob($path.'*', GLOB_ONLYDIR|GLOB_NOSORT);

		if (!empty($paths) && ($level < $depth || $depth == -1)) {
			$level++;
			foreach ($paths as $sub_path) {
				$tree[$sub_path] = folder_tree($pattern, $flags, $sub_path.DIRECTORY_SEPARATOR, $depth, $level);
			}
		}

		$tree = array_merge($tree, $files);

		return $tree;
	}

	private function recurse_copy($src, $dest) {
		$dir = opendir($src);
		@mkdir($dest);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					recurse_copy($src . '/' . $file,$dest . '/' . $file);
				}
				else {
					copy($src . '/' . $file,$dest . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

}