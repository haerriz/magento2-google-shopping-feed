<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Model\FeedProfileFactory;

class Save extends Action
{
    const ADMIN_RESOURCE = 'Haerriz_GoogleShoppingFeed::feed_profiles';

    protected $repository;
    protected $factory;

    public function __construct(
        Context $context,
        FeedProfileRepositoryInterface $repository,
        FeedProfileFactory $factory
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->factory = $factory;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            try {
                $id = $data['profile_id'] ?? null;
                $model = $id ? $this->repository->getById($id) : $this->factory->create();
                
                // Validate filename for path traversal and extension
                if (isset($data['filename'])) {
                    $filename = basename($data['filename']);
                    if (!preg_match('/\.(xml|csv|txt|tsv|jsonl)$/i', $filename)) {
                        throw new \Exception('Invalid filename extension.');
                    }
                    $data['filename'] = $filename;
                }

                // Serialize Dynamic Rows for attribute mapping
                if (isset($data['attributes_mapping'])) {
                    $data['attributes_mapping_serialized'] = json_encode($data['attributes_mapping']);
                } else {
                    $data['attributes_mapping_serialized'] = null;
                }
                
                if (isset($data['name'])) $model->setName($data['name']);
                if (isset($data['status'])) $model->setStatus($data['status']);
                if (isset($data['store_id'])) $model->setStoreId((int)$data['store_id']);
                if (isset($data['currency'])) $model->setCurrency($data['currency']);
                if (isset($data['filename'])) $model->setFilename($data['filename']);
                if (isset($data['feed_type'])) $model->setFeedType($data['feed_type']);
                
                $model->setAttributesMappingSerialized($data['attributes_mapping_serialized'] ?? null);
                $model->setConditionsSerialized($data['conditions_serialized'] ?? null);
                $this->repository->save($model);
                
                $this->messageManager->addSuccessMessage(__('You saved the feed profile.'));
                
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        
        return $resultRedirect->setPath('*/*/');
    }
}
