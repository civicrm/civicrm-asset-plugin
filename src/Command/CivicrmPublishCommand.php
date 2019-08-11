<?php

namespace Civi\AssetPlugin\Command;

use Civi\AssetPlugin\Publisher;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CivicrmPublishCommand extends \Composer\Command\BaseCommand {

  protected function configure() {
    parent::configure();

    $this
      ->setName('civicrm:publish')
      ->setAliases(['cvpub'])
      ->setDescription('Publish web assets from CiviCRM-related projects');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $p = new Publisher($this->getComposer(), $this->getIO());
    $p->publishAllAssets();
    $p->createAssetMap();
  }

}
