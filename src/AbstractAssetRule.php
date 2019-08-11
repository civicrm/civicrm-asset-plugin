<?php

namespace Civi\AssetPlugin;

abstract class AbstractAssetRule implements AssetRuleInterface {

  /**
   * @var \Composer\Package\PackageInterface
   */
  protected $package;

  /**
   * @var string
   */
  protected $srcPath;

  /**
   * ExtensionAssetRule constructor.
   *
   * @param \Composer\Package\PackageInterface $package
   * @param string $srcPath
   */
  public function __construct(\Composer\Package\PackageInterface $package, $srcPath) {
    $this->package = $package;
    $this->srcPath = $srcPath;
  }

  /**
   * @return \Composer\Package\PackageInterface
   */
  public function getPackage() {
    return $this->package;
  }

}
