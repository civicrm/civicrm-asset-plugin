<?php

namespace Civi\AssetPlugin;

use Civi\AssetPlugin\Exception\XmlException;
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

  /**
   * Find all Civi-related packages; extract and publish all relevant
   * assets.
   */
  public function publishAllAssets() {
    $this->io->write("\n<info>Publishing CiviCRM assets (<comment>{$this->getLocalPath()}</comment>)</info>");
    foreach ($this->createAllAssetRules() as $assetRule) {
      $assetRule->publish($this, $this->io);
    }
  }

  /**
   * Create a file with path/url mappings for Civi-related assets.
   *
   * @return string
   *   Full path to the asset map.
   */
  public function createAssetMap() {
    $vendorPath = $this->composer->getConfig()->get('vendor-dir');
    $file = $vendorPath . "/composer/autoload_civicrm_asset.php";

    $this->io->write("<info>Generating CiviCRM asset map</info>");
    $snippets = ["<?php\n"];
    $snippets[] = "global \$civicrm_paths;\n";
    $snippets[] = "\$vendorDir = dirname(dirname(__FILE__));\n";
    $snippets[] = "\$baseDir = dirname(\$vendorDir);\n";
    $snippets[] = "\$civicrm_paths['civicrm.vendor']['path'] = \$vendorDir;\n";
    foreach ($this->createAllAssetRules() as $assetRule) {
      $snippets[] = $assetRule->createAssetMap($this, $this->io);
    }

    file_put_contents($file, implode("", $snippets));
    return $file;
  }

  /**
   * Get the local file-path to which we should write assets.
   *
   * @return string
   */
  public function getLocalPath() {
    return rtrim($this->config['path'], DIRECTORY_SEPARATOR);
  }

  /**
   * Get the public URL path at which assets may be read.
   *
   * @return string
   */
  public function getWebPath() {
    return rtrim($this->config['url'], '/');
  }

  /**
   * @return array
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Get a list of rules for publishing assets from a given package.
   *
   * @param \Composer\Package\PackageInterface $package
   * @return AssetRuleInterface|NULL
   *   NULL if the package is not related to CiviCRM.
   */
  protected function createAssetRule(PackageInterface $package) {
    $installPath = $this->composer->getInstallationManager()->getInstallPath($package);

    switch ($package->getName()) {
      case 'civicrm/civicrm-core':
        return new BasicAssetRule($package, $installPath, 'core', 'civicrm.root');

      case 'civicrm/civicrm-packages':
        return new BasicAssetRule($package, $installPath, 'packages', 'civicrm.packages');
    }

    if ($installPath && file_exists("$installPath/info.xml")) {
      try {
        return new ExtensionAssetRule($package, $installPath);
      }
      catch (XmlException $e) {
        $this->io->writeError("Skipping invalid extension: $installPath.");
      }
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
      'path' => 'web/libraries/civicrm',
      'url' => '/libraries/civicrm',
      'symlink' => FALSE,
      'files' => [
        'DEFAULT' => [
          '**.html',
          '**.js',
          '**.css',
          '**.svg',
          '**.png',
          '**.jpg',
          '**.jpeg',
          '**.ico',
          '**.gif',
          '**.woff',
          '**.woff2',
          '**.ttf',
          '**.eot',
          '**.swf',
        ],
      ],
      'exclude-dir' => [
        // Common VCS folders
        '.git',
        '.svn',
        '.bzr',
        // Common top-level PHP folders
        '/CRM',
        '/Civi',
        '/tests',
      ],
    ];

    $extra = $this->composer->getPackage()->getExtra();
    $config = isset($extra['civicrm-asset']) ? $extra['civicrm-asset'] : [];
    $config = array_merge($defaults, $config);
    $config['files'] = array_merge($defaults['files'], $config['files']);
    return $config;
  }

}
