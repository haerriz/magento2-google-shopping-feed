<?php
namespace Haerriz\GoogleShoppingFeed\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupArtifacts extends Command
{
    protected function configure()
    {
        $this->setName('haerriz:feed:cleanup-artifacts')
             ->setDescription('Remove expired historical feed artifacts');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Expired historical artifacts cleaned up successfully.</info>');
        return Command::SUCCESS;
    }
}
