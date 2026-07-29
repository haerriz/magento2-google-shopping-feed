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
                'history' => [
                    'href' => $this->urlBuilder->getUrl(
                        'haerriz_googleshoppingfeed/job/index',
                        ['filters' => ['profile_id' => $id]]
                    ),
                    'label' => __('Job History'),
                ],
                'download' => [
                    'href' => $this->urlBuilder->getUrl('*/*/download', ['id' => $id]),
                    'label' => __('Download Current Feed'),
                ],
            ];
        }
        unset($item);
        return $dataSource;
    }
}
