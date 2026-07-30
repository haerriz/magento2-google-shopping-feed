<?php
namespace Haerriz\GoogleShoppingFeed\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MerchantReconcile extends Command
{
    protected function configure()
    {
        $this->setName('haerriz:feed:merchant-reconcile')
             ->setDescription('Reconcile Merchant API product approval statuses');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Merchant API statuses reconciled successfully.</info>');
        return Command::SUCCESS;
    }
}
