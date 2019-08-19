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

  /**
   * Publisher constructor.
   * @param \Composer\Composer $composer
   * @param \Composer\IO\IOInterface $io
   * @param null $extra
   */
  public function __construct($composer, $io, $extra = NULL) {
    $this->composer = $composer;
    $this->io = $io;

    $this->config = $this->mergeConfigExtra(
      $extra ?? $this->composer->getPackage()->getExtra(),
      PublisherDefaults::create($composer, $io));
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
   * Determine the file-writing mode.
   *
   * @return string
   *   One of the following:
   *    - 'copy': Do not use symlinks
   *    - 'symlink': Symlink on a file-by-file basis
   *    - 'symdir': Symlink top-level directories, even if that exposes unrelated files
   */
  public function getFileMode() {
    $mode = $this->getConfig()['file-mode'];

    if (empty($mode) || $mode === 'auto') {
      // FIXME
      return 'copy';
    }

    return $mode;
  }

  /**
   * @return array
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Get the part of the configuration which is specifically targeted at
   * a specific package.
   *
   * @param string $publicName
   *   Ex: 'core' or 'org.civicrm.api4'
   * @param string $field
   *   Ex: 'include' or 'exclude-dir'
   * @return array
   *   Ex: ['**.css']
   */
  public function getAssetConfig($publicName, $field) {
    $config = $this->getConfig();
    $tgt = $config["assets:{$publicName}"] ?? [];
    $base = $config['assets:*'];
    $eff = array_merge($base, $tgt);
    if (isset($eff['+' . $field])) {
      $eff[$field] = array_unique(array_merge($eff[$field], $eff['+' . $field]));
    }
    return $eff[$field];
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

  /**
   * @param $extra
   * @param $defaults
   * @return array
   */
  protected function mergeConfigExtra($extra, $defaults) {
    $config = isset($extra['civicrm-asset']) ? $extra['civicrm-asset'] : [];
    foreach (array_merge(array_keys($config), array_keys($defaults)) as $k) {
      // If only one party sets the key, use that.
      if (isset($config[$k]) && !isset($defaults[$k])) {
        continue;
      }
      if (!isset($config[$k]) && isset($defaults[$k])) {
        $config[$k] = $defaults[$k];
        continue;
      }

      // else: Both are set. What kind of merge?

      $isMergeKey = substr($k, 0, 7) === 'assets:';
      if ($isMergeKey) {
        $config[$k] = array_merge($defaults[$k], $config[$k]);
        continue;
      }
      else {
        // Both are set, current $config[$k] takes precedence.
        continue;
      }
    }
    return $config;
  }

}
