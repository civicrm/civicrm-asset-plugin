<?php

namespace Civi\AssetPlugin\Util;

class GlobPlus {

  // phpcs:disable
  // civicrm/coder cannot handle empty generator syntax.
  /**
   * An extended variant of glob() which supports subdirectory matching
   * with '**' as well as matching multiple patterns.
   *
   * @param string $baseDir
   *   The folder to search
   * @param string[] $pats
   *   Each is a glob, where '*' matches a file, and '**' matching across subdirectories. Ex:
   *   - '*.js' (match .js files in the base directory)
   *   - '**.js' (match .js files in any descendent of the base directory)
   *   - 'css/*.css' (match .css files directly in the 'css' subdir)
   *   - 'css/**.css' (match .css files in any descendent of the 'css' subdir)   * @return \Generator
   * @param string[] $excludeDirs
   *   List of subdirs to exclude systematically (e.g. `.git`, `.svn`).
   * @return \Generator
   *   A list of matching file names, expressed relative to $baseDir.
   *   Order is not guaranteed.
   *
   * The current implementation does a single, full, recursive pass over the $baseDir, which
   * is useful when there are many suffix patterns ("**.css"). However, you should still
   * use tighter patterns ("css/*.css") if you can - because they're amenable to
   * future optimization.
   */
  public static function find($baseDir, $pats, $excludeDirs = []) {
    // FIXME: If $pats doesn't have any '**.foo' items, then we don't need a full scan.
    // FIXME: If a folder is a VCS folder, then we don't need to scan below it.

    if (empty($pats)) {
      // Effectively, return an empty generator.
      return;
    }

    // Array(string $escapedGlob => string $regex).
    $globRegexMap = ['\*\*' => ".+", '\*' => '[^/]+'];

    // Deterministic ordering; multiple similar calls should yield same regex.
    sort($pats);

    // This maybe a premature optimization... split regex's into 2 groups, based on which anchors are needed.
    $regexes = [];
    foreach ($pats as $pat) {
      $regexes[] = strtr(preg_quote($pat, ';'), $globRegexMap);
    }

    $regex = ';^(' . implode('|', $regexes) . ')$;';
    $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
    $baseLen = strlen($baseDir) + 1;
    $files = new \RecursiveIteratorIterator(
      new \RecursiveCallbackFilterIterator(
        new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS|\RecursiveDirectoryIterator::UNIX_PATHS),
        function ($current, $key, $iterator) use ($excludeDirs, $baseLen) {
          /** @var \SplFileInfo $current */
          if (in_array($current->getBasename(), $excludeDirs)) {
            return FALSE;
          }
          $relPath = substr((string) $current, $baseLen);
          if (in_array("/$relPath", $excludeDirs)) {
            return FALSE;
          }
          return TRUE;
        }
      )
    );

    foreach ($files as $fileinfo) {
      /** @var \SplFileInfo $fileinfo */
      $relPath = substr((string) $fileinfo, $baseLen);
      if (preg_match($regex, $relPath)) {
        yield $relPath;
      }
    }
  }
  // phpcs:enable

}
