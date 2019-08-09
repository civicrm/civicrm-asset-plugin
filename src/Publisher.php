<?php

namespace Civi\AssetPlugin;

use Composer\Package\PackageInterface;

/**
 * Class Publisher
 * @package Civi\AssetPlugin
 *
 * Locate assets (*.js, *.css) from CiviCRM-related packages and sync them a public folder.
 */
class Publisher {

  public function __construct(\Composer\Composer $composer, \Composer\IO\IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  public function publishAssets(PackageInterface $package) {
    $assetRule = $this->createAssetRule($package);
    if ($assetRule) {
      $assetRule->publish($this, $this->io);
    }
  }

  public function publishAllAssets() {
    $this->io->write("TODO: syncAllAssets");
    foreach ($this->createAllAssetRules() as $assetRule) {
      $assetRule->publish($this, $this->io);
    }
  }

  /**
   * @param \Composer\Package\PackageInterface $package
   * @return string
   */
  public function createLocalPath(PackageInterface $package) {
    return $this->getLocalPath() . DIRECTORY_SEPARATOR . $package->getName();
  }

  /**
   * Get the publicly-accessible path to which we should write assets.
   *
   * @return string
   */
  public function getLocalPath() {
    // FIXME, use 'composer.json' extras and rtrim()
    return './web/libraries/civicrm';
  }

  public function getWebPath() {
    // FIXME, use 'composer.json' extras and rtrim()
    return '/libraries/civicrm';
  }

  /**
   * Get a list of rules for publishing assets from a given package.
   *
   * @param \Composer\Package\PackageInterface $package
   * @return AssetRuleInterface|NULL
   *   NULL if the package is not related to CiviCRM.
   */
  protected function createAssetRule(PackageInterface $package) {
    switch ($package->getName()) {
      case 'civicrm/civicrm-core':
      case 'civicrm/civicrm-packages':
        return new BasicAssetRule($package);
    }

    $targetDir = $package->getTargetDir();
    if ($targetDir && file_exists("$targetDir/info.xml")) {
      return new ExtensionAssetRule($package, "$targetDir/info.xml");
    }

    return NULL;
  }

  /**
   * @return AssetRuleInterface[]
   */
  protected function createAllAssetRules() {
    $this->io->write("TODO: findAllAssetRules");
    //    $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
    //    foreach ($localRepo->getCanonicalPackages() as $package) {
    //      /** @var PackageInterface $package */
    //    }
    return [];
  }

}
