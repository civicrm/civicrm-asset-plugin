<?php

namespace Civi\AssetPlugin;

use Civi\AssetPlugin\Exception\XmlException;
use Civi\AssetPlugin\Util\Xml;
use Composer\IO\IOInterface;

class ExtensionAssetRule extends AbstractAssetRule {

  const RESERVED_NAMES = ['core', 'packages', 'drupal', 'drupal-8', 'joomla', 'wordpress'];

  protected $extKey;

  public function __construct(\Composer\Package\PackageInterface $package, $srcPath) {
    parent::__construct($package, $srcPath, $package->getName());
  }

  public function createAssetMap(Publisher $publisher, IOInterface $io) {
    $localPath = $this->getLocalPath($publisher);
    $webPath = $this->getWebPath($publisher);
    return "/* FIXME ExtensionAssetRule::createAssetMap ([cms.root]$webPath <=> $localPath) */\n";
  }

  public function publish(Publisher $publisher, IOInterface $io) {
    $localPath = $this->getLocalPath($publisher);
    $webPath = $this->getWebPath($publisher);
    $io->write("TODO: ExtensionAssetRule::publish ($webPath <=> $localPath)");
  }

  //return [
  //'pkg' => $package->getName(),
  //'extKey' => "FIXME parse $infoXmlPath",
  //'src' => $package->getTargetDir(),
  //'dest' => $this->createLocalPath($package),
  //'ext' => Defaults::$defaultAssetExtensions,
  //];

}
