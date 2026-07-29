<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Model\PreviewService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Preview extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Haerriz_GoogleShoppingFeed::generate';

    private $repository;
    private $previewService;
    private $jsonFactory;

    public function __construct(
        Context $context,
        FeedProfileRepositoryInterface $repository,
        PreviewService $previewService,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->previewService = $previewService;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $profile = $this->repository->getById((int)$this->getRequest()->getParam('id'));
            return $result->setData($this->previewService->preview(
                $profile,
                (int)$this->getRequest()->getParam('limit', 10)
            ));
        } catch (\Throwable $exception) {
            return $result->setHttpResponseCode(400)->setData([
                'sampled' => true,
                'error' => __('Preview could not be generated. Validate the profile and try again.'),
            ]);
        }
    }
}
