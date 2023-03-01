<?php

namespace Civi\AssetPlugin\Util;

class ComposerJsonMerge {

  /**
   * Merge two `composer.json` fragments.
   *
   * @param array $base
   *   Original/baseline configuration.
   * @param array $extras
   *   Additional data to add on top.
   * @return array
   *   Combined data.
   */
  public static function merge(array $base, array $extras): array {
    $result = $base;
    foreach ($extras as $key => $value) {
      switch ($key) {
        case 'config':
        case 'extra':
        case 'repositories':
          $result[$key] = array_merge($result[$key] ?? [], $value);
          break;

        default:
          $result[$key] = $value;
          break;
      }
    }
    return $result;
  }

}
