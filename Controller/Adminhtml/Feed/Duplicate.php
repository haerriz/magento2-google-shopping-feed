<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Model\FeedProfileCloner;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Duplicate extends Action implements HttpPostActionInterface
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
            $source = $this->repository->getById((int)$this->getRequest()->getParam('id'));
            $copy = $this->cloner->duplicate($source);
            $this->messageManager->addSuccessMessage(__('The profile was duplicated without credentials.'));
            return $redirect->setPath('*/*/edit', ['id' => $copy->getId()]);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('The profile could not be duplicated.'));
            return $redirect->setPath('*/*/');
        }
    }
}
