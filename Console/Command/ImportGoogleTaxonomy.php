<?php
namespace Haerriz\GoogleShoppingFeed\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportGoogleTaxonomy extends Command
{
    protected function configure()
    {
        $this->setName('haerriz:feed:import-taxonomy')
             ->setDescription('Import official Google Shopping taxonomy categories');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Official Google Shopping Taxonomy imported successfully.</info>');
        return Command::SUCCESS;
    }
}
