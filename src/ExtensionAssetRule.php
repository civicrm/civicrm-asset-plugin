<?php

namespace Civi\AssetPlugin;

use Composer\IO\IOInterface;

class ExtensionAssetRule implements AssetRuleInterface {

  /**
   * @var \Composer\Package\PackageInterface
   */
  protected $package;

  /**
   * @var string
   */
  protected $infoXmlPath;

  /**
   * ExtensionAssetRule constructor.
   *
   * @param \Composer\Package\PackageInterface $package
   * @param string $infoXmlPath
   */
  public function __construct(\Composer\Package\PackageInterface $package, $infoXmlPath) {
    $this->package = $package;
    $this->infoXmlPath = $infoXmlPath;
  }

  public function createAutoloadSnippet(Publisher $publisher, IOInterface $io) {
    return "/* FIXME ExtensionAssetRule::createAutoloadSnippet */\n";
  }

  public function publish(Publisher $publisher, IOInterface $io) {
    $io->write("TODO: ExtensionAssetRule::publish for " . $this->getPackage()->getName());
  }

  /**
   * @return \Composer\Package\PackageInterface
   */
  public function getPackage() {
    return $this->package;
  }

  //return [
  //'pkg' => $package->getName(),
  //'extKey' => "FIXME parse $infoXmlPath",
  //'src' => $package->getTargetDir(),
  //'dest' => $this->createLocalPath($package),
  //'ext' => Defaults::$defaultAssetExtensions,
  //];

}
