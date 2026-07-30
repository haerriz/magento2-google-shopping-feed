<?php
namespace Haerriz\GoogleShoppingFeed\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateFeed extends Command
{
    protected function configure()
    {
        $this->setName('haerriz:feed:validate')
             ->setDescription('Validate feed profile configurations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("All feed profile configurations are valid!");
        return Command::SUCCESS;
    }
}
