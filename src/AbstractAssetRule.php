<?php

namespace Civi\AssetPlugin;

use Civi\AssetPlugin\Util\GlobPlus;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;

abstract class AbstractAssetRule implements AssetRuleInterface {

  /**
   * @var \Composer\Util\Filesystem
   */
  protected $cfs;

  /**
   * @var \Composer\Package\PackageInterface
   */
  protected $package;

  /**
   * @var string
   *   The name of the public folder for this package's assets.
   */
  protected $publicName;

  /**
   * @var string
   *   The full path to the original source for this package.
   */
  protected $srcPath;

  /**
   * ExtensionAssetRule constructor.
   *
   * @param \Composer\Package\PackageInterface $package
   * @param string $srcPath
   * @param string $publicName
   */
  public function __construct($package, $srcPath, $publicName) {
    $this->package = $package;
    $this->srcPath = $srcPath;
    $this->publicName = (string) $publicName;
  }

  public function publish(Publisher $publisher, IOInterface $io) {
    $this->cfs = $this->cfs ?? new Filesystem();

    $srcPathAbs = $this->srcPath;
    $srcPathRel = preg_replace(';^' . preg_quote(getcwd(), ';') . '/?;', '', $srcPathAbs);
    $tgtPathRel = $this->getLocalPath($publisher);
    $tgtPathAbs = getcwd() . DIRECTORY_SEPARATOR . $tgtPathRel;

    switch ($publisher->getFileMode()) {
      case 'copy':
        $io->write("  - <info>Copy files from <comment>{$srcPathRel}</comment> to <comment>{$tgtPathRel}</comment></info>", TRUE, IOInterface::VERBOSE);
        $files = GlobPlus::find($this->srcPath, $this->getIncludes($publisher), $this->getExcludeDirs($publisher));
        $this->syncFiles($io, $this->srcPath, $tgtPathAbs, $files, FALSE);
        break;

      case 'symlink':
        $io->write("  - <info>Symlink files from <comment>{$srcPathRel}</comment> to <comment>{$tgtPathRel}</comment></info>", TRUE, IOInterface::VERBOSE);
        $files = GlobPlus::find($this->srcPath, $this->getIncludes($publisher), $this->getExcludeDirs($publisher));
        $this->syncFiles($io, $this->srcPath, $tgtPathAbs, $files, TRUE);
        break;

      case 'symdir':
        $io->write("  - <info>Symlink folder from <comment>{$srcPathRel}</comment> to <comment>{$tgtPathRel}</comment></info>", TRUE, IOInterface::VERBOSE);
        $this->cfs->removeDirectory($tgtPathAbs);
        $this->cfs->ensureDirectoryExists(dirname($tgtPathAbs));
        $this->cfs->relativeSymlink($this->srcPath, $tgtPathAbs);
        break;

      default:
        throw new \RuntimeException("Unrecognized symlink mode ({$publisher->getFileMode()}). Use one of [none,file,dir,auto]");
    }
  }

  public function unpublish(Publisher $publisher, IOInterface $io) {
    $this->cfs = $this->cfs ?? new Filesystem();

    $tgtPathRel = $this->getLocalPath($publisher);
    $tgtPathAbs = getcwd() . DIRECTORY_SEPARATOR . $tgtPathRel;

    if (!file_exists($tgtPathAbs)) {
      return;
    }

    $io->write("  - <info>Removing published assets from <comment>{$tgtPathRel}</comment></info>", TRUE, IOInterface::VERBOSE);
    $this->cfs->removeDirectory($tgtPathAbs);
  }

  /**
   * @param \Composer\IO\IOInterface $io
   * @param string $srcPath
   * @param string $tgtPath
   * @param \Generator|array $files
   * @param bool $preferLink
   */
  protected function syncFiles(IOInterface $io, $srcPath, $tgtPath, $files, $preferLink) {
    if (is_link($tgtPath)) {
      // Transition form "symdir" to "file" or "symlink"
      unlink($tgtPath);
    }

    $count = 0;

    foreach ($files as $file) {
      $count += $this->copyFile($io, $file, "{$srcPath}/{$file}", "{$tgtPath}/{$file}", $preferLink);
    }

    if ($count > 0) {
      $io->write("", TRUE, IOInterface::VERY_VERBOSE);
    }
  }

  protected function copyFile(IOInterface $io, $file, $srcFile, $tgtFile, $preferLink) {
    $this->cfs->ensureDirectoryExists(dirname($tgtFile));
    $srcStat = stat($srcFile);
    $tgtStat = @stat($tgtFile);
    $isActiveLink = ($tgtStat !== FALSE) && is_link($tgtFile);

    if ($preferLink) {
      if ($isActiveLink && realpath($tgtFile) === realpath($srcFile)) {
        return 0;
      }

      if ($tgtStat !== FALSE) {
        unlink($tgtFile);
      }

      $io->write(" $file", FALSE, IOInterface::VERY_VERBOSE);
      $this->cfs->relativeSymlink($srcFile, $tgtFile);
      return 1;
    }
    else {
      if ($isActiveLink) {
        unlink($tgtFile);
      }

      if (!$isActiveLink && $tgtStat !== FALSE && $srcStat['mtime'] <= $tgtStat['mtime'] && $srcStat['size'] === $tgtStat['size']) {
        return 0;
      }

      $io->write(" $file", FALSE, IOInterface::VERY_VERBOSE);
      $this->cfs->copy($srcFile, $tgtFile);
      return 1;
    }
  }

  public function createAssetMap(Publisher $publisher, IOInterface $io) {
    $noSlash = function($str) {
      return rtrim($str, '/' . DIRECTORY_SEPARATOR);
    };
    return sprintf("\$GLOBALS['civicrm_asset_map'][%s][%s] = %s;\n",
        var_export($this->getPackage()->getName(), 1),
        var_export('src', 1),
        $this->exportPath($noSlash($this->srcPath)))
      . sprintf("\$GLOBALS['civicrm_asset_map'][%s][%s] = %s;\n",
        var_export($this->getPackage()->getName(), 1),
        var_export('dest', 1),
        $this->exportPath($noSlash($this->getLocalPath($publisher))))
      . sprintf("\$GLOBALS['civicrm_asset_map'][%s][%s] = %s;\n",
        var_export($this->getPackage()->getName(), 1),
        var_export('url', 1),
        var_export($noSlash($this->getWebPath($publisher)), 1));
  }

  /**
   * @return \Composer\Package\PackageInterface
   */
  public function getPackage() {
    return $this->package;
  }

  /**
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @return array
   *   Ex: ['css/*.css', '**.js']
   */
  public function getIncludes(Publisher $publisher) {
    return $publisher->getAssetConfig($this->publicName, 'include');
  }

  /**
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @return array
   *   Ex: ['.git', '.svn', '/CRM']
   */
  public function getExcludeDirs(Publisher $publisher) {
    return $publisher->getAssetConfig($this->publicName, 'exclude-dir');
  }

  /**
   * Get the local file-path to which we should write assets.
   *
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @return string
   */
  protected function getLocalPath(Publisher $publisher) {
    return $publisher->getLocalPath() . DIRECTORY_SEPARATOR . $this->publicName;
  }

  /**
   * Get the public URL path at which assets may be read.
   *
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @return string
   */
  protected function getWebPath(Publisher $publisher) {
    return $publisher->getWebPath() . '/' . $this->publicName;
  }

  /**
   * @param string $path
   *   Ex: '/var/www/foo/bar';
   * @param string|NULL $cwd
   *   The current working directory, to which we want things to be relative.
   * @return string
   *   PHP-encoded expression for $path, relative to a `$baseDir` variable
   */
  protected function exportPath($path, $cwd = NULL) {
    // This should work for both unix and windows. (Except an odd edge-case: Unix systems with relative paths that have a ":")
    $len = strlen($path);
    $isAbsolute = ($len > 0 && $path[0] === '/') || ($len > 1 && $path[1] === ':');
    if (!$isAbsolute) {
      return '$baseDir . ' . var_export('/' . $path, 1);
    }

    $cwd = ($cwd === NULL) ? getcwd() : $cwd;
    $basedirQuoted = preg_quote($cwd, ';');
    $exportedAbs = var_export($path, 1);
    $exportedRel = preg_replace(";^([\"'])($basedirQuoted);", '$baseDir . \1', $exportedAbs);
    return $exportedRel;
  }

}
