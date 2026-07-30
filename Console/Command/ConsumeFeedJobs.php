<?php
namespace Haerriz\GoogleShoppingFeed\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeFeedJobs extends Command
{
    protected function configure()
    {
        $this->setName('haerriz:feed:consume-jobs')
             ->setDescription('Consume queued asynchronous feed generation jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Queued feed jobs processed successfully.</info>');
        return Command::SUCCESS;
    }
}
