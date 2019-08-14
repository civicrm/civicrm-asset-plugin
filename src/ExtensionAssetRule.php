<?php

namespace Civi\AssetPlugin;

use Civi\AssetPlugin\Exception\XmlException;
use Civi\AssetPlugin\Util\Xml;
use Composer\IO\IOInterface;

class ExtensionAssetRule extends AbstractAssetRule {

  const RESERVED_NAMES = ['core', 'packages', 'drupal', 'drupal-8', 'joomla', 'wordpress', 'setup'];

  protected $extKey;

  public function __construct(\Composer\Package\PackageInterface $package, $srcPath) {
    $infoXml = Xml::parseFile("$srcPath/info.xml");
    $extKey = $infoXml->attributes()->key;
    if (empty($extKey)) {
      throw new XmlException("Failed to parse info.xml");
    }
    if (in_array($extKey, self::RESERVED_NAMES)) {
      throw new XmlException("Cannot use info.xml - extension key is a reserved name");
    }
    parent::__construct($package, $srcPath, $extKey);
  }

  public function createAssetMap(Publisher $publisher, IOInterface $io) {
    $localPath = $this->getLocalPath($publisher);
    $webPath = $this->getWebPath($publisher);
    return parent::createAssetMap($publisher, $io)
      . "/* FIXME ExtensionAssetRule::createAssetMap ([cms.root]$webPath <=> $localPath) */\n";
  }

  public function getIncludes(Publisher $publisher) {
    // FIXME: Check info.xml
    return parent::getIncludes($publisher);
  }

  //return [
  //'pkg' => $package->getName(),
  //'extKey' => "FIXME parse $infoXmlPath",
  //'src' => $package->getTargetDir(),
  //'dest' => $this->createLocalPath($package),
  //'ext' => Defaults::$defaultAssetExtensions,
  //];

}
