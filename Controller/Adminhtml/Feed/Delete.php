<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Delete extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Haerriz_GoogleShoppingFeed::feed_profiles';

    private $repository;

    public function __construct(
        Action\Context $context,
        FeedProfileRepositoryInterface $repository
    ) {
        parent::__construct($context);
        $this->repository = $repository;
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        try {
            $this->repository->deleteById((int)$this->getRequest()->getParam('id'));
            $this->messageManager->addSuccessMessage(
                __('The profile was deleted. Historical jobs were retained.')
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('The profile could not be deleted.'));
        }

        return $redirect->setPath('*/*/');
    }
}
