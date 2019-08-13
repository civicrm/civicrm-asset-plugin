<?php

class PublisherTest extends \PHPUnit\Framework\TestCase {

  public function testEmptyCfg() {
    $p = new \Civi\AssetPlugin\Publisher(NULL, NULL, []);
    $defaultConfig = \Civi\AssetPlugin\PublisherDefaults::create(NULL, NULL);
    $actualConfig = $p->getConfig();
    $this->assertEquals($defaultConfig, $actualConfig);
  }

  public function testPathUrlCfg() {
    $p = new \Civi\AssetPlugin\Publisher(NULL, NULL, [
      'civicrm-asset' => [
        'path' => 'foo',
        'url' => 'bar',
      ],
    ]);
    $defaultConfig = \Civi\AssetPlugin\PublisherDefaults::create(NULL, NULL);
    $actualConfig = $p->getConfig();
    $this->assertEquals($defaultConfig['assets:*'], $actualConfig['assets:*']);
    $this->assertEquals('foo', $actualConfig['path']);
    $this->assertEquals('bar', $actualConfig['url']);
  }

  public function getPackageOverrideExamples() {
    $defaultConfig = \Civi\AssetPlugin\PublisherDefaults::create(NULL, NULL);

    $cases = [];

    $cases[] = [
      [],
      $defaultConfig['assets:*']['include'],
      $defaultConfig['assets:*']['exclude-dir'],
    ];

    $cases[] = [
      [
        'civicrm-asset' => [
          'assets:foobar' => [],
        ],
      ],
      $defaultConfig['assets:*']['include'],
      $defaultConfig['assets:*']['exclude-dir'],
    ];

    $cases[] = [
      [
        'civicrm-asset' => [
          'assets:foobar' => ['exclude-dir' => ['CVSROOT', '.loco']],
        ],
      ],
      $defaultConfig['assets:*']['include'],
      ['CVSROOT', '.loco'],
    ];

    $cases[] = [
      [
        'civicrm-asset' => [
          'assets:*' => ['exclude-dir' => ['.git']],
          'assets:foobar' => ['+exclude-dir' => ['CVSROOT']],
        ],
      ],
      $defaultConfig['assets:*']['include'],
      ['.git', 'CVSROOT'],
    ];

    $cases[] = [
      [
        'civicrm-asset' => [
          'assets:*' => ['include' => ['*.foo', '*.bar']],
        ],
      ],
      ['*.foo', '*.bar'],
      $defaultConfig['assets:*']['exclude-dir'],
    ];

    $cases[] = [
      [
        'civicrm-asset' => [
          'assets:*' => ['exclude-dir' => ['CVSROOT', '.loco']],
          'assets:foobar' => ['include' => ['*.foo', '*.bar']],
        ],
      ],
      ['*.foo', '*.bar'],
      ['CVSROOT', '.loco'],
    ];

    return $cases;
  }

  /**
   * @param $inputOverride
   * @param $expectInclude
   * @param $expectExclude
   * @dataProvider getPackageOverrideExamples
   */
  public function testPackageOverride($inputOverride, $expectInclude, $expectExclude) {
    $p = new \Civi\AssetPlugin\Publisher(NULL, NULL, $inputOverride);
    $rule = new \Civi\AssetPlugin\BasicAssetRule(NULL, '/tmp/foobar', 'foobar', 'civicrm.foobar');
    $this->assertEquals($expectExclude, $rule->getExcludeDirs($p));
    $this->assertEquals($expectInclude, $rule->getIncludes($p));
  }

}
