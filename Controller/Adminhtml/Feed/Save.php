<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Model\ProfileValidator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Haerriz_GoogleShoppingFeed::manage';

    private $repository;
    private $validator;

    public function __construct(
        Context $context,
        FeedProfileRepositoryInterface $repository,
        ProfileValidator $validator
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->validator  = $validator;
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $data     = $this->getRequest()->getPostValue();

        if (!$data) {
            return $redirect->setPath('*/*/');
        }

        try {
            $id = isset($data['profile_id']) ? (int)$data['profile_id'] : 0;

            if ($id > 0) {
                $profile = $this->repository->getById($id);
            } else {
                $profile = $this->repository->create();
            }

            // Map form fields to profile
            foreach (['name', 'feed_type', 'store_id', 'filename', 'status', 'cron_expr',
                      'delivery_type', 'ftp_host', 'ftp_port', 'ftp_user', 'ftp_password',
                      'remote_path', 'attributes_mapping_serialized', 'conditions_serialized'] as $field) {
                if (array_key_exists($field, $data)) {
                    $profile->setData($field, $data[$field]);
                }
            }

            // Validate BEFORE saving
            $errors = $this->validator->validate($profile);
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->messageManager->addErrorMessage($error);
                }
                $backUrl = $id > 0
                    ? $this->getUrl('*/*/edit', ['id' => $id])
                    : $this->getUrl('*/*/new');
                return $redirect->setUrl($backUrl);
            }

            $this->repository->save($profile);
            $this->messageManager->addSuccessMessage(__('Feed profile "%1" saved successfully.', $profile->getName()));

            if ($this->getRequest()->getParam('back') === 'edit') {
                return $redirect->setPath('*/*/edit', ['id' => $profile->getId()]);
            }
            return $redirect->setPath('*/*/');

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while saving: %1', $e->getMessage()));
        }

        return $redirect->setPath('*/*/');
    }
}
