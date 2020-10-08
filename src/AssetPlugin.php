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
   * @var \Composer\Composer
   */
  protected $composer;

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
   * @var bool|null
   */
  protected $conflicted = NULL;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
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
    if ($this->checkConflicted() || $this->checkRemoved()) {
      return;
    }
    $this->io->write("  - <info>CiviCRM asset map</info>");
    $file = $this->publisher->createAssetMap();

    $rootPackage = $event->getComposer()->getPackage();
    $autoloads = $rootPackage->getAutoload();
    $autoloads['files'][] = $file;
    $rootPackage->setAutoload($autoloads);
  }

  public function runQueue(Event $event) {
    if ($this->checkConflicted() || $this->checkRemoved()) {
      return;
    }

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

  /**
   * Determine if there is a conflict that prevents us from syncing assets.
   *
   * On the first invocation, this will assess the conflict and print an appropriate warning.
   *
   * Note that it is preferable to run this late (eg `POST_INSTALL_CMD`) rather
   * than early (eg `PRE_PACKAGE_INSTALL`) so that we have a clear view is
   * (or is not) installed.
   *
   * @return bool
   *   TRUE if there is a conflict
   */
  protected function checkConflicted() {
    if ($this->conflicted === NULL) {
      $this->conflicted = NULL !== $this->composer->getRepositoryManager()->getLocalRepository()->findPackage('roundearth/civicrm-composer-plugin', '*');
      if ($this->conflicted) {
        // We are likely to get in this situation if (1) a site originally installed with an early
        // Civi-D8 tutorial/template using RE/CCP and (2) later upgraded to Civi 5.31+ which bundles C/CAP.
        $url = 'https://civicrm.stackexchange.com/q/35921';
        $this->io->write("");
        $this->io->write("<warning>WARNING</warning>: <comment>civicrm/civicrm-asset-plugin</comment> skipped due to conflict with <comment>roundearth/civicrm-composer-plugin</comment>.");
        $this->io->write("");
        $this->io->write("Both plugins are installed. They overlap insofar as both publish assets " .
          "for CiviCRM-D8, but they are not interoperable. To ensure consistency, " .
          "<comment>civicrm/civicrm-asset-plugin</comment> will defer to " .
          "<comment>roundearth/civicrm-composer-plugin</comment>. If you prefer to migrate, see: " .
          "<comment>$url</comment>\n"
        );
      }
    }

    return $this->conflicted;
  }

  /**
   * In composer v1, the plugin class is loaded and activated before removal.
   * This leaves us in a dangling state where uninstallation may raise errors.
   *
   * @return bool
   *   TRUE if it appears that this has been removed.
   */
  protected function checkRemoved() {
    try {
      $pkg = $this->composer->getRepositoryManager()->getLocalRepository()->findPackage('civicrm/civicrm-asset-plugin', '*');
    }
    catch (\Exception $e) {
      return TRUE;
    }

    if ($pkg === NULL) {
      return TRUE;
    }

    try {
      $path = $this->composer->getInstallationManager()->getInstallPath($pkg);
    }
    catch (\Exception $e) {
      return TRUE;
    }

    return !is_readable($path);
  }

}
