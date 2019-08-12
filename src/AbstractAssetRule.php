<?php

namespace Civi\AssetPlugin;

use Civi\AssetPlugin\Util\GlobPlus;
use Composer\IO\IOInterface;

abstract class AbstractAssetRule implements AssetRuleInterface {

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
  public function __construct(\Composer\Package\PackageInterface $package, $srcPath, $publicName) {
    $this->package = $package;
    $this->srcPath = $srcPath;
    $this->publicName = (string) $publicName;
  }

  public function publish(Publisher $publisher, IOInterface $io) {
    $localPath = $this->getLocalPath($publisher);
    // $webPath = $this->getWebPath($publisher);
    $globPatterns = $this->getGlobPatterns($publisher);

    $io->write("DRY RUN: Map from {$this->srcPath} to {$localPath}");
    $io->write("         With: " . implode(', ', $globPatterns));

    $files = GlobPlus::find($this->srcPath, $globPatterns, $publisher->getConfig()['exclude-dir']);
    foreach ($files as $file) {
      $io->write(" - $file");
    }
  }

  /**
   * @return \Composer\Package\PackageInterface
   */
  public function getPackage() {
    return $this->package;
  }

  public function getGlobPatterns(Publisher $publisher) {
    $config = $publisher->getConfig();
    return $config['files'][$this->publicName] ?? $config['files']['DEFAULT'];
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

}
