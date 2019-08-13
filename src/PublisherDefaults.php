<?php

namespace Civi\AssetPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;

class PublisherDefaults {

  /**
   * @param \Composer\Composer $composer
   * @param \Composer\IO\IOInterface $io
   * @return array
   */
  public static function create($composer, $io) {
    $instance = new PublisherDefaults();
    $instance->init();
    if ($composer) {
      $instance->detect($composer, $io);
    }
    return $instance->get();
  }

  /**
   * @var array
   */
  protected $defaults;

  /**
   * PublisherDefaults constructor.
   */
  public function init() {
    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'default-config.json';
    $this->defaults = json_decode(file_get_contents($file), 1);
  }

  public function detect(Composer $composer, IOInterface $io) {
    $extra = $composer->getPackage()->getExtra();
    $installerPaths = $extra['installer-paths'] ?? [];

    $libPath = $this->findPathByType('drupal-library', $installerPaths);
    $corePath = $this->findPathByType('drupal-core', $installerPaths);
    if ($libPath || $corePath) {
      if (preg_match(';web/;', $libPath) || preg_match(';web/;', $corePath)) {
        $io->write("<info>[<comment>civicrm-asset-plugin</comment>] Found <comment>drupal/core</comment>. Defaults will be based on <comment>drupal-composer/drupal-project</comment>.</info>", TRUE, IOInterface::VERBOSE);
        $this->defaults['path'] = 'web/libraries/civicrm';
        $this->defaults['url'] = '/libraries/civicrm';
        return;
      }
      else {
        $io->write("<info>[<comment>civicrm-asset-plugin</comment>] Found <comment>drupal/core</comment>. Defaults will be based on <comment>tarball</comment>.</info>", TRUE, IOInterface::VERBOSE);
        $this->defaults['path'] = 'libraries/civicrm';
        $this->defaults['url'] = '/libraries/civicrm';
        return;
      }
    }

    $io->write("<info>[<comment>civicrm-asset-plugin</comment>] CMS auto-detection failed. Consider setting <comment>extra.civicrm-asset.path</comment> and <comment>extra.civicrm-asset.url</comment>.", TRUE, IOInterface::VERBOSE);
  }

  //  /**
  //   * @param \Composer\Composer $composer
  //   * @return array
  //   *   List of package names.
  //   */
  //  protected function allDeps(Composer $composer) {
  //    $dep = [];
  //    $todo = [];
  //
  //    $schedule = function($pkg) use (&$todo) {
  //      if (!$pkg) {
  //        return;
  //      }
  //      foreach ($pkg->getRequires() as $req) {
  //        $todo[] = $req->getTarget();
  //      }
  //    };
  //
  //    $schedule($composer->getPackage());
  //    while (isset($todo[0])) {
  //      if (! isset($dep[$todo[0]])) {
  //        $dep[$todo[0]] = 1;
  //        $pkg = $composer->getRepositoryManager()->findPackage($todo[0], '*');
  //        $schedule($pkg);
  //      }
  //      array_shift($todo);
  //    }
  //
  //    return array_keys($dep);
  //  }

  /**
   * @param string $type
   *   An installer-type (e.g. 'drupal-module')
   * @param $paths
   * @return null|string
   */
  protected function findPathByType($type, $paths) {
    if (!$paths) {
      return NULL;
    }
    foreach ($paths as $path => $items) {
      if (in_array("type:{$type}", $items)) {
        return $path;
      }
    }
    return NULL;
  }

  /**
   * @return array
   */
  public function get() {
    return $this->defaults;
  }

}
