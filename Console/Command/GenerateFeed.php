<?php
namespace Haerriz\GoogleShoppingFeed\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFeed extends Command
{
    private \Haerriz\GoogleShoppingFeed\Model\FeedProfileRepository $profileRepository;
    private \Haerriz\GoogleShoppingFeed\Model\FeedGenerator $generator;

    public function __construct(
        \Haerriz\GoogleShoppingFeed\Model\FeedProfileRepository $profileRepository,
        \Haerriz\GoogleShoppingFeed\Model\FeedGenerator $generator
    ) {
        $this->profileRepository = $profileRepository;
        $this->generator = $generator;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('haerriz:feed:generate')
             ->setDescription('Generate product feed for profile')
             ->addOption('profile_id', 'p', InputOption::VALUE_REQUIRED, 'Profile ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profileId = (int)$input->getOption('profile_id');
        $profile = $this->profileRepository->getById($profileId);
        $output->writeln("Generating feed for profile: {$profile->getName()}");
        $this->generator->generate($profile, 'cli');
        $output->writeln("Feed generated successfully!");
        return Command::SUCCESS;
    }
}
