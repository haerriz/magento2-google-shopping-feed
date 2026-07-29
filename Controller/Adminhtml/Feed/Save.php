<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Api\CredentialProviderInterface;
use Haerriz\GoogleShoppingFeed\Model\FeedProfileFactory;

use Haerriz\GoogleShoppingFeed\Model\RuleFactory;
use Haerriz\GoogleShoppingFeed\Model\Logger\Sanitizer;
use Haerriz\GoogleShoppingFeed\Model\Cron\Scheduler;
use Haerriz\GoogleShoppingFeed\Model\ProfileValidator;

class Save extends Action
{
    const ADMIN_RESOURCE = 'Haerriz_GoogleShoppingFeed::feed_profiles';

    protected $repository;
    protected $factory;
    protected $credentialProvider;
    protected $ruleFactory;
    protected $sanitizer;
    private $scheduler;
    private $profileValidator;

    public function __construct(
        Context $context,
        FeedProfileRepositoryInterface $repository,
        FeedProfileFactory $factory,
        CredentialProviderInterface $credentialProvider,
        RuleFactory $ruleFactory,
        Sanitizer $sanitizer,
        Scheduler $scheduler,
        ProfileValidator $profileValidator
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->factory = $factory;
        $this->credentialProvider = $credentialProvider;
        $this->ruleFactory = $ruleFactory;
        $this->sanitizer = $sanitizer;
        $this->scheduler = $scheduler;
        $this->profileValidator = $profileValidator;
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
                    if ($filename !== $data['filename']
                        || !preg_match('/\.(xml|csv|txt|tsv|jsonl)$/i', $filename)
                    ) {
                        throw new \Exception('Invalid filename extension.');
                    }
                    if (isset($data['feed_type'])
                        && strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== strtolower($data['feed_type'])
                    ) {
                        throw new \Exception('Filename extension must match the selected feed format.');
                    }
                    $data['filename'] = $filename;
                }

                // Serialize Dynamic Rows for attribute mapping
                if (isset($data['attributes_mapping'])) {
                    $data['attributes_mapping_serialized'] = json_encode($data['attributes_mapping']);
                } else {
                    $data['attributes_mapping_serialized'] = null;
                }

                foreach (['include_category_ids', 'exclude_category_ids', 'visibility_values'] as $listField) {
                    if (isset($data[$listField]) && is_array($data[$listField])) {
                        $data[$listField] = implode(',', array_values(array_unique(array_map('intval', $data[$listField]))));
                    }
                }

                // Process standard Magento rules data
                if (isset($data['rule'])) {
                    $ruleModel = $this->ruleFactory->create();
                    $ruleModel->loadPost($data['rule']);
                    $data['conditions_serialized'] = json_encode($ruleModel->getConditions()->asArray());
                }
                
                if (isset($data['name'])) $model->setName($data['name']);
                if (isset($data['status'])) $model->setStatus($data['status']);
                if (isset($data['store_id'])) $model->setStoreId((int)$data['store_id']);
                if (isset($data['currency'])) $model->setCurrency($data['currency']);
                if (isset($data['filename'])) $model->setFilename($data['filename']);
                if (isset($data['feed_type'])) $model->setFeedType($data['feed_type']);

                $profileFields = [
                    'locale',
                    'target_country',
                    'content_language',
                    'channel',
                    'template_version',
                    'delimiter',
                    'enclosure',
                    'line_ending',
                    'encoding',
                    'compression',
                    'max_products_per_file',
                    'max_bytes_per_file',
                    'include_category_ids',
                    'exclude_category_ids',
                    'include_category_descendants',
                    'include_disabled',
                    'visibility_values',
                    'stock_policy',
                    'conditional_values_serialized',
                    'modifier_chains_serialized',
                    'configurable_strategy',
                    'bundle_strategy',
                    'grouped_strategy',
                    'include_virtual',
                    'include_downloadable',
                    'delivery_timeout',
                    'ftp_passive',
                    'remote_filename',
                    'sftp_fingerprint',
                    'frequency',
                    'cron_expression',
                    'timezone',
                    'concurrency_policy',
                    'missed_run_policy',
                    'max_retries',
                    'utm_enabled',
                    'utm_source',
                    'utm_medium',
                    'utm_campaign',
                    'utm_term',
                    'utm_content',
                ];
                foreach ($profileFields as $field) {
                    if (array_key_exists($field, $data)) {
                        $model->setData($field, $data[$field]);
                    }
                }

                if (isset($data['delivery_type'])) $model->setDeliveryType($data['delivery_type']);
                if (isset($data['delivery_host'])) $model->setDeliveryHost($data['delivery_host']);
                if (isset($data['delivery_port'])) $model->setDeliveryPort((int)$data['delivery_port']);
                if (isset($data['delivery_username'])) $model->setDeliveryUsername($data['delivery_username']);
                if (isset($data['delivery_path'])) $model->setDeliveryPath($data['delivery_path']);

                if (!empty($data['clear_delivery_password'])) {
                    $model->setDeliveryPassword(null);
                } elseif (isset($data['delivery_password']) && trim((string)$data['delivery_password']) !== '') {
                    $model->setDeliveryPassword(
                        $this->credentialProvider->encrypt((string)$data['delivery_password'])
                    );
                }
                
                $model->setAttributesMappingSerialized($data['attributes_mapping_serialized'] ?? null);
                $model->setConditionsSerialized($data['conditions_serialized'] ?? null);
                $model->setData('next_run_at', $this->scheduler->calculateNextRun(
                    $model->getData('frequency') ?: 'manual',
                    $model->getData('cron_expression'),
                    $model->getData('timezone') ?: 'UTC'
                ));
                if ((int)$model->getStatus() === 1) {
                    $this->profileValidator->assertValid($model);
                }
                $this->repository->save($model);
                
                $this->messageManager->addSuccessMessage(__('You saved the feed profile.'));
                
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($this->sanitizer->sanitize($e->getMessage()));
                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        
        return $resultRedirect->setPath('*/*/');
    }
}
