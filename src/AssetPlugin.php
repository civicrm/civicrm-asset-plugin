<?php

namespace Civi\AssetPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Class AssetPlugin
 * @package Civi\AssetPlugin
 *
 * Call the "Publisher" whenever one installs or updates a Civi-related package.
 */
class AssetPlugin implements PluginInterface, EventSubscriberInterface, Capable {

  /**
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * @var \Civi\AssetPlugin\Publisher
   */
  protected $publisher;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->io = $io;
    $this->publisher = new Publisher($composer, $io);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PackageEvents::POST_PACKAGE_INSTALL => ['onPackageInstall', -100],
      PackageEvents::POST_PACKAGE_UPDATE => ['onPackageUpdate', -100],
      PackageEvents::PRE_PACKAGE_UNINSTALL => ['onPackageUninstall'],
      ScriptEvents::PRE_AUTOLOAD_DUMP => ['onAutoloadDump', -100],
    ];
  }

  /**
   * @param \Composer\Installer\PackageEvent $event
   *   The event.
   */
  public function onPackageInstall(PackageEvent $event) {
    $package = $event->getOperation()->getPackage();
    $this->publisher->publishAssets($package);
  }

  /**
   * @param \Composer\Installer\PackageEvent $event
   *   The event.
   */
  public function onPackageUpdate(PackageEvent $event) {
    $package = $event->getOperation()->getTargetPackage();
    $this->publisher->publishAssets($package);
  }

  /**
   * @param \Composer\Installer\PackageEvent $event
   *   The event.
   */
  public function onPackageUninstall(PackageEvent $event) {
    $package = $event->getOperation()->getPackage();
    $this->publisher->unpublishAssets($package);
  }

  /**
   * @param \Composer\Script\Event $event
   */
  public function onAutoloadDump(Event $event) {
    $this->io->write("  - <info>CiviCRM asset map</info>");
    $file = $this->publisher->createAssetMap();

    $rootPackage = $event->getComposer()->getPackage();
    $autoloads = $rootPackage->getAutoload();
    $autoloads['files'][] = $file;
    $rootPackage->setAutoload($autoloads);
  }

  /**
   * {@inheritdoc}
   */
  public function getCapabilities() {
    return [
      'Composer\Plugin\Capability\CommandProvider' => CommandProvider::class,
    ];
  }

}
