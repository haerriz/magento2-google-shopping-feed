<?php
namespace Haerriz\GoogleShoppingFeed\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class FeedActions extends Column
{
    private $urlBuilder;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    public function prepareDataSource(array $dataSource)
    {
        foreach ($dataSource['data']['items'] ?? [] as &$item) {
            $id = (int)($item['profile_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $item[$this->getData('name')] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl('*/*/edit', ['id' => $id]),
                    'label' => __('Edit'),
                ],
                'quick_view' => [
                    'href' => $this->urlBuilder->getUrl('*/*/preview', ['id' => $id]),
                    'label' => __('Quick View'),
                ],
                'generate' => [
                    'href' => $this->urlBuilder->getUrl('*/*/trigger', ['id' => $id]),
                    'label' => __('Generate Now'),
                    'confirm' => [
                        'title' => __('Generate Feed'),
                        'message' => __('Trigger feed generation for %1?', $item['name'] ?? '')
                    ]
                ],
                'duplicate' => [
                    'href' => $this->urlBuilder->getUrl('*/*/duplicate', ['id' => $id]),
                    'label' => __('Duplicate'),
                ],
                'history' => [
                    'href' => $this->urlBuilder->getUrl(
                        'haerriz_googleshoppingfeed/job/index',
                        ['filters' => ['profile_id' => $id]]
                    ),
                    'label' => __('Job History'),
                ],
                'download' => [
                    'href' => $this->urlBuilder->getUrl('*/*/download', ['id' => $id]),
                    'label' => __('Download Feed'),
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl('*/*/delete', ['id' => $id]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Feed Profile'),
                        'message' => __('Are you sure you want to delete %1?', $item['name'] ?? '')
                    ]
                ]
            ];
        }
        unset($item);
        return $dataSource;
    }
}
