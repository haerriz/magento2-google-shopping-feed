<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Model\FeedProfileCloner;
use Magento\Backend\App\Action;

class Duplicate extends Action
{
    const ADMIN_RESOURCE = 'Haerriz_GoogleShoppingFeed::feed_profiles';

    private $repository;
    private $cloner;

    public function __construct(
        Action\Context $context,
        FeedProfileRepositoryInterface $repository,
        FeedProfileCloner $cloner
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->cloner = $cloner;
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $source = $this->repository->getById($id);
            $copy = $this->cloner->duplicate($source);
            $this->messageManager->addSuccessMessage(__('The profile was duplicated without credentials.'));
            return $redirect->setPath('*/*/edit', ['id' => $copy->getId() ?? $copy->getProfileId()]);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('The profile could not be duplicated: %1', $exception->getMessage()));
            return $redirect->setPath('*/*/');
        }
    }
}
