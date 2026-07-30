<?php
namespace Haerriz\GoogleShoppingFeed\Model\Cron;

use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Model\FeedExporter;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Dispatcher
{
    private $repository;
    private $searchCriteriaBuilder;
    private $exporter;

    public function __construct(
        FeedProfileRepositoryInterface $repository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FeedExporter $exporter
    ) {
        $this->repository = $repository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->exporter = $exporter;
    }

    public function dispatch()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', 1)
            ->create();

        $profiles = $this->repository->getList($searchCriteria)->getItems();
        foreach ($profiles as $profile) {
            if ($profile->getCronExpr()) {
                $this->exporter->export($profile, 'pub/media/' . $profile->getFilename());
            }
        }
    }
}
