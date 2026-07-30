<?php
namespace Haerriz\GoogleShoppingFeed\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Model\FeedExporter;

class GenerateFeed extends Command
{
    private $profileRepository;
    private $exporter;

    public function __construct(
        FeedProfileRepositoryInterface $profileRepository,
        FeedExporter $exporter
    ) {
        $this->profileRepository = $profileRepository;
        $this->exporter = $exporter;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('haerriz:feed:generate')
             ->setDescription('Generate product feed for profile')
             ->addOption('profile', 'p', InputOption::VALUE_REQUIRED, 'Profile ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profileId = (int)$input->getOption('profile');
        if ($profileId <= 0) {
            $output->writeln('<error>Please specify a valid --profile ID.</error>');
            return Command::FAILURE;
        }

        try {
            $profile = $this->profileRepository->getById($profileId);
            $output->writeln("Generating feed for profile: <info>{$profile->getName()}</info> ({$profile->getFilename()})");
            
            $result = $this->exporter->export($profile, 'pub/media/' . $profile->getFilename());
            $output->writeln("<info>Feed generated successfully!</info> Exported: " . ($result['exported'] ?? 0) . " products.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>Feed generation failed: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
