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
	}

	public function processCopy(array $config)
	{

		$config = $this->processConfig($config);

		$project_path = \realpath($this->composer->getConfig()->get('vendor-dir').'/../').'/';

		$this->io->write('[sasedev/composer-plugin-filecopier] basepath : '.$project_path);

		$destination = $config['destination'];

		if (\strlen($destination) == 0 || (\strlen($destination) != 0 && !$this->startsWith($destination, '/'))) {
			$destination = $project_path.$destination;
		}

		if (false === \realpath($destination)) {
			mkdir($destination, 0755, true);
		}
		$destination = \realpath($destination);

		$source = $config['source'];

		$this->io->write('[sasedev/composer-plugin-filecopier] init source : '.$source);

		$this->io->write('[sasedev/composer-plugin-filecopier] init destination : '.$destination);

		$sources = glob($source, GLOB_MARK);
		if (!empty($sources)) {
			foreach ($sources as $newsource) {
				$this->copyr($newsource, $destination, $project_path);
			}
		}

	}

	private function copyr($source, $destination, $project_path)
	{

		if (\strlen($source) == 0 || (\strlen($source) != 0 && !$this->startsWith($source, '/'))) {
			$source = $project_path.$source;
		}

		if (false === \realpath($source)) {
			$this->io->write('[sasedev/composer-plugin-filecopier] No copy : source ('.$source.') does not exist');
		}

		$source = \realpath($source);

		if ($source === $destination && \is_dir($source)) {
			$this->io->write('[sasedev/composer-plugin-filecopier] No copy : source ('.$source.') and destination ('.$destination.') are identicals');
			return true;
		}


		// Check for symlinks
		if (\is_link($source)) {
			$this->io->write('[sasedev/composer-plugin-filecopier] Copying Symlink '.source.' to '.$destination);
			$source_entry = \basename($source);
			return \symlink(\readlink($source), $destination.'/'.$source_entry);
		}

		if (\is_dir($source)) {
			// Loop through the folder
			$source_entry = \basename($source);
			if ($project_path.$source_entry == $source) {
				$destination = $destination.'/'.$source_entry;
			}
			// Make destination directory
			if (!\is_dir($destination)) {
				$this->io->write('[sasedev/composer-plugin-filecopier] New Folder '.$destination);
				\mkdir($destination);
			}

			$this->io->write('[sasedev/composer-plugin-filecopier] Scanning Folder '.$source);

			$dir = \dir($source);
			while (false !== $entry = $dir->read()) {
				// Skip pointers
				if ($entry == '.' || $entry == '..') {
					continue;
				}

				// Deep copy directories
				$this->copyr($source.'/'.$entry, $destination.'/'.$entry, $project_path);
			}

			// Clean up
			$dir->close();
			return true;
		}

		// Simple copy for a file
		if (\is_file($source)) {
			$source_entry = \basename($source);
			if ($project_path.$source_entry == $source || \is_dir($destination)) {
				$destination = $destination.'/'.$source_entry;
			}
			$this->io->write('[sasedev/composer-plugin-filecopier] Copying File '.$source.' to '.$destination);

			return \copy($source, $destination);
		}


		return true;

	}

	private function processConfig(array $config)
	{

		if (empty($config['source'])) {
			throw new \InvalidArgumentException('The extra.filescopier.source setting is required to use this script handler.');
		}

		if (empty($config['destination'])) {
			throw new \InvalidArgumentException('The extra.filescopier.destination setting is required to use this script handler.');
		}

		return $config;

	}

	private function startsWith($haystack, $needle) {
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	/**
	 * Check if a string ends with a suffix
	 *
	 * @param string $string
	 * @param string $suffix
	 *
	 * @return boolean
	 */
	public function endswith($string, $suffix)
	{
		$strlen = strlen($string);
		$testlen = strlen($suffix);
		if ($testlen > $strlen) {
			return false;
		}

		return substr_compare($string, $suffix, -$testlen) === 0;
	}

}