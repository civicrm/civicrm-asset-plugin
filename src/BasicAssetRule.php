<?php

namespace Civi\AssetPlugin;

use Composer\IO\IOInterface;

class BasicAssetRule implements AssetRuleInterface {

  /**
   * @var \Composer\Package\PackageInterface
   */
  protected $package;

  /**
   * BasicPublisher constructor.
   * @param \Composer\Package\PackageInterface $package
   */
  public function __construct(\Composer\Package\PackageInterface $package) {
    $this->package = $package;
  }

  public function createAutoloadSnippet(Publisher $publisher, IOInterface $io) {
    return '/* FIXME BasicAssetRule::createAutoloadSnippet */';
  }

  public function publish(Publisher $publisher, IOInterface $io) {
    $io->write("TODO: BasicAssetRule::publish for " . $this->getPackage()->getName());
  }

  /**
   * @return \Composer\Package\PackageInterface
   */
  public function getPackage() {
    return $this->package;
  }

}
