<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterfaceFactory;
use Haerriz\GoogleShoppingFeed\Model\PreviewService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class PreviewAjax extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Haerriz_GoogleShoppingFeed::generate';

    private JsonFactory $jsonFactory;
    private PreviewService $previewService;
    private FeedProfileInterfaceFactory $profileFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        PreviewService $previewService,
        FeedProfileInterfaceFactory $profileFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->previewService = $previewService;
        $this->profileFactory = $profileFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (empty($data)) {
            return $result->setData(['success' => false, 'message' => 'No form data received.']);
        }

        // UI form components often nest values under "data".
        if (isset($data['data']) && is_array($data['data'])) {
            $data = array_merge($data, $data['data']);
        }

        try {
            $profile = $this->profileFactory->create();
            $fields = [
                'name',
                'feed_type',
                'store_id',
                'currency',
                'filename',
                'status',
                'attributes_mapping_serialized',
                'conditions_serialized',
                'delivery_type',
            ];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $profile->setData($field, $data[$field]);
                }
            }

            if (!$profile->getFeedType()) {
                $profile->setFeedType('google_shopping_v1');
            }
            if (!$profile->getFilename()) {
                $profile->setFilename('preview_' . time() . '.xml');
            }
            if (!$profile->getDeliveryType()) {
                $profile->setDeliveryType('local');
            }

            $preview = $this->previewService->preview($profile, 5);

            return $result->setData([
                'success' => true,
                'content' => $preview['content'] !== ''
                    ? $preview['content']
                    : 'No products found for the current mapping/filters.',
                'counts' => $preview['counts'] ?? [],
                'format' => $preview['format'] ?? $profile->getFeedType(),
            ]);
        } catch (\Throwable $e) {
            return $result->setHttpResponseCode(500)->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
