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
   * List of packages that need to be sync'd.
   *
   * We only sync a package if there's been a concrete installation/update event,
   * but we need to defer until late in the installation process to ensure
   * that the assets are fully materialized (e.g. after patches and compilation tasks).
   *
   * @var string[]
   *   Ex: ['vendor1/package1', 'vendor2/package2']
   */
  protected $queue;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->io = $io;
    $this->publisher = new Publisher($composer, $io);
    $this->queue = [];
  }

  public function deactivate(Composer $composer, IOInterface $io) {
    // NOTE: This method is only valid on composer v2.
    unset($this->io);
    unset($this->publisher);
    unset($this->queue);
  }

  public function uninstall(Composer $composer, IOInterface $io) {
    // NOTE: This method is only valid on composer v2.
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
      ScriptEvents::POST_INSTALL_CMD => ['runQueue', -100],
      ScriptEvents::POST_UPDATE_CMD => ['runQueue', -100],
    ];
  }

  /**
   * @param \Composer\Installer\PackageEvent $event
   *   The event.
   */
  public function onPackageInstall(PackageEvent $event) {
    $package = $event->getOperation()->getPackage();
    $this->queue[] = $package->getName();
  }

  /**
   * @param \Composer\Installer\PackageEvent $event
   *   The event.
   */
  public function onPackageUpdate(PackageEvent $event) {
    $package = $event->getOperation()->getTargetPackage();
    $this->queue[] = $package->getName();
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

  public function runQueue(Event $event) {
    if (empty($this->queue)) {
      return;
    }

    $this->io->write("<info>Sync CiviCRM assets</info>");
    $queue = $this->queue;
    $this->queue = [];

    $localRepo = $event->getComposer()->getRepositoryManager()->getLocalRepository();

    foreach ($queue as $packageName) {
      $this->publisher->publishAssets($localRepo->findPackage($packageName, '*'));
    }
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
