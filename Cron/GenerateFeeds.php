<?php
namespace Haerriz\GoogleShoppingFeed\Cron;

use Haerriz\GoogleShoppingFeed\Model\ResourceModel\FeedProfile\CollectionFactory;
use Haerriz\GoogleShoppingFeed\Model\FeedGenerator;
use Haerriz\GoogleShoppingFeed\Model\Api\MerchantClient;
use Psr\Log\LoggerInterface;

class GenerateFeeds
{
    protected $collectionFactory;
    protected $feedGenerator;
    protected $merchantClient;
    protected $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        FeedGenerator $feedGenerator,
        MerchantClient $merchantClient,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->feedGenerator = $feedGenerator;
        $this->merchantClient = $merchantClient;
        $this->logger = $logger;
    }

    public function execute()
    {
        $this->logger->info("Starting Google Shopping Feed Generation via Cron");

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', 1); // Only active profiles

        foreach ($collection as $profile) {
            $this->logger->info("Generating feed for profile ID: " . $profile->getId());
            
            try {
                // Generate the XML or CSV locally
                $this->feedGenerator->generate($profile);
                $this->logger->info("Feed file generated: " . $profile->getFilename());
                
                // Note: Merchant API integration is incomplete in Phase 0.
                // The actual payload delivery will be implemented in Phase 9.
                $this->logger->info("Feed file generated successfully. Merchant API delivery is not yet implemented.");
            } catch (\Exception $e) {
                $this->logger->error("Error generating feed profile {$profile->getId()}: " . $e->getMessage());
            }
        }

        $this->logger->info("Completed Google Shopping Feed Generation via Cron");
        return $this;
    }
}
