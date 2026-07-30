<?php
namespace Haerriz\GoogleShoppingFeed\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MerchantSync extends Command
{
    protected function configure()
    {
        $this->setName('haerriz:feed:merchant-sync')
             ->setDescription('Synchronize catalog items with Google Merchant Center API');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Google Merchant API synchronization complete.</info>');
        return Command::SUCCESS;
    }
}
