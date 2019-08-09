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

  /**
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * @var array
   */
  protected $config;

  public function __construct(\Composer\Composer $composer, \Composer\IO\IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
    $this->config = $this->createConfig();
  }

  public function publishAssets(PackageInterface $package) {
    $assetRule = $this->createAssetRule($package);
    if ($assetRule) {
      $assetRule->publish($this, $this->io);
    }
  }

  public function publishAllAssets() {
    $this->io->write("\n<info>Publishing CiviCRM assets (<comment>{$this->getLocalPath()}</comment>)</info>");
    foreach ($this->createAllAssetRules() as $assetRule) {
      $assetRule->publish($this, $this->io);
    }
  }

  /**
   * @return string
   *   Full path to the asset map.
   */
  public function generateAutoload() {
    $vendorPath = $this->composer->getConfig()->get('vendor-dir');
    $file = $vendorPath . "/composer/autoload_civicrm_asset.php";

    $this->io->write("<info>Generating CiviCRM asset paths</info>");
    $snippets = ["<?php\n"];
    $snippets[] = "global \$civicrm_paths;\n";
    $snippets[] = "\$vendorDir = dirname(dirname(__FILE__));\n";
    $snippets[] = "\$baseDir = dirname(\$vendorDir);\n";
    $snippets[] = "\$civicrm_paths['civicrm.vendor']['path'] = \$vendorDir;\n";
    foreach ($this->createAllAssetRules() as $assetRule) {
      $snippets[] = $assetRule->createAutoloadSnippet($this, $this->io);
    }

    file_put_contents($file, implode("", $snippets));
    return $file;
  }

  /**
   * @param \Composer\Package\PackageInterface $package
   * @return string
   */
  public function createLocalPath(PackageInterface $package) {
    return $this->getLocalPath() . DIRECTORY_SEPARATOR . $package->getName();
  }

  /**
   * @param \Composer\Package\PackageInterface $package
   * @return string
   */
  public function createWebPath(PackageInterface $package) {
    return $this->getWebPath() . '/' . $package->getName();
  }

  /**
   * Get the publicly-accessible path to which we should write assets.
   *
   * @return string
   */
  public function getLocalPath() {
    return rtrim($this->config['path'], DIRECTORY_SEPARATOR);
  }

  public function getWebPath() {
    return rtrim($this->config['url'], '/');
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
        return new BasicAssetRule($package, 'civicrm.root');

      case 'civicrm/civicrm-packages':
        return new BasicAssetRule($package, 'civicrm.packages');
    }

    $installPath = $this->composer->getInstallationManager()->getInstallPath($package);
    if ($installPath && file_exists("$installPath/info.xml")) {
      return new ExtensionAssetRule($package, "$installPath/info.xml");
    }

    return NULL;
  }

  /**
   * @return AssetRuleInterface[]
   */
  protected function createAllAssetRules() {
    $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
    $rules = [];
    foreach ($localRepo->getCanonicalPackages() as $package) {
      /** @var \Composer\Package\PackageInterface $package */
      if ($rule = $this->createAssetRule($package)) {
        $rules[] = $rule;
      }
    }
    return $rules;
  }

  protected function createConfig() {
    $defaults = [
      'path' => 'web/libraries',
      'url' => '/libraries',
    ];
    $extra = $this->composer->getPackage()->getExtra();
    $config = isset($extra['civicrm-asset']) ? $extra['civicrm-asset'] : [];
    $config = array_merge($defaults, $config);
    return $config;
  }

}
