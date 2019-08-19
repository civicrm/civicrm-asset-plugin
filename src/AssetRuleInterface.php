<?php
namespace Civi\AssetPlugin;

use Composer\IO\IOInterface;

interface AssetRuleInterface {

  /**
   * @return \Composer\Package\PackageInterface
   */
  public function getPackage();

  /**
   * Create a code-snippet which registers this asset at runtime.
   *
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @param \Composer\IO\IOInterface $io
   * @return string
   */
  public function createAssetMap(Publisher $publisher, IOInterface $io);

  /**
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @param \Composer\IO\IOInterface $io
   */
  public function publish(Publisher $publisher, IOInterface $io);

  /**
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @param \Composer\IO\IOInterface $io
   */
  public function unpublish(Publisher $publisher, IOInterface $io);

}
