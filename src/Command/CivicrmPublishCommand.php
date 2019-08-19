<?php

namespace Civi\AssetPlugin\Command;

use Civi\AssetPlugin\Publisher;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CivicrmPublishCommand extends \Composer\Command\BaseCommand {

  protected function configure() {
    parent::configure();

    $this
      ->setName('civicrm:publish')
      ->setAliases(['cvpub'])
      ->setDescription('Publish web assets from CiviCRM-related projects')
      ->addOption('file-mode', 'F', InputOption::VALUE_OPTIONAL, 'How to create new files (auto,copy,symlink,symdir)', '')
      ->addOption('delete', 'D', InputOption::VALUE_NONE, 'Enable broad deletion. This ensures that orphaned files are removed, but it requires more I/O.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $fileMode = $input->getOption('file-mode');
    if ($fileMode && in_array($fileMode, ['auto', 'copy', 'symlink', 'symdir'])) {
      putenv('CIVICRM_COMPOSER_ASSET=' . $fileMode);
    }

    $p = new Publisher($this->getComposer(), $this->getIO());

    $tgtPath = $p->getLocalPath();
    if ($input->getOption('delete') && file_exists($tgtPath)) {
      $output->writeln("<info>Deleting CiviCRM assets from <comment>{$tgtPath}</comment></info>");
      $cfs = new Filesystem();
      $cfs->removeDirectory($tgtPath);
    }

    $output->writeln("<info>Publishing CiviCRM assets to <comment>{$tgtPath}</comment></info>");
    $p->publishAllAssets();

    $output->writeln("<info>Generating CiviCRM asset map</info>");
    $p->createAssetMap();
  }

}
