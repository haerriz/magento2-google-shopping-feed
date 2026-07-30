<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterfaceFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Save extends Action
{
    const ADMIN_RESOURCE = 'Haerriz_GoogleShoppingFeed::feed_save';

    private $repository;
    private $profileFactory;

    public function __construct(
        Context $context,
        FeedProfileRepositoryInterface $repository,
        FeedProfileInterfaceFactory $profileFactory
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->profileFactory = $profileFactory;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $redirect = $this->resultRedirectFactory->create();

        if (!$data) {
            return $redirect->setPath('*/*/');
        }

        try {
            $id = (int)($data['profile_id'] ?? $this->getRequest()->getParam('id'));
            if ($id) {
                $profile = $this->repository->getById($id);
            } else {
                $profile = $this->profileFactory->create();
            }

            // Map FeedType to allowed extensions
            $feedType = (string)($data['feed_type'] ?? $profile->getFeedType() ?? 'google_shopping_v1');
            $filename = trim((string)($data['filename'] ?? ''));

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $validExtensions = ['xml', 'csv', 'tsv', 'jsonl', 'json', 'txt'];
            if (!$ext || !in_array($ext, $validExtensions, true)) {
                throw new \InvalidArgumentException(__('Output filename must end with a valid extension (.xml, .csv, .jsonl, .tsv, .txt).'));
            }

            $profile->setName($data['name'] ?? $profile->getName());
            $profile->setStatus((int)($data['status'] ?? 1));
            $profile->setFeedType($feedType);
            $profile->setStoreId((int)($data['store_id'] ?? 1));
            $profile->setFilename($filename);
            $profile->setCronExpr($data['cron_expr'] ?? $profile->getCronExpr());

            if (!empty($data['excluded_category_ids'])) {
                $ids = is_array($data['excluded_category_ids'])
                    ? implode(',', $data['excluded_category_ids'])
                    : $data['excluded_category_ids'];
                $profile->setExcludeCategoryIds($ids);
            }

            if (!empty($data['delivery_type'])) {
                $profile->setDeliveryType($data['delivery_type']);
                $profile->setDeliveryHost($data['delivery_host'] ?? '');
                $profile->setDeliveryUsername($data['delivery_username'] ?? '');
                if (!empty($data['delivery_password'])) {
                    $profile->setDeliveryPassword($data['delivery_password']);
                }
            }

            $saved = $this->repository->save($profile);
            $this->messageManager->addSuccessMessage(__('The feed profile has been saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['id' => $saved->getEntityId() ?? $saved->getProfileId()]);
            }
            return $redirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error saving feed profile: %1', $e->getMessage()));
            return $redirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
    }
}
