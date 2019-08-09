<?php

namespace Civi\AssetPlugin;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Civi\AssetPlugin\Command\CivicrmPublishCommand;

class CommandProvider implements CommandProviderCapability {

  public function getCommands() {
    return [
      new CivicrmPublishCommand(),
    ];
  }

}
