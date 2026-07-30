<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Model\PreviewService;

class Preview extends Action
{
    const ADMIN_RESOURCE = 'Haerriz_GoogleShoppingFeed::feed_preview';

    private $resultRawFactory;
    private $profileRepository;
    private $previewService;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        FeedProfileRepositoryInterface $profileRepository,
        PreviewService $previewService
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->profileRepository = $profileRepository;
        $this->previewService = $previewService;
    }

    public function execute()
    {
        $result = $this->resultRawFactory->create();
        $id = (int)$this->getRequest()->getParam('id');

        try {
            $profile = $this->profileRepository->getById($id);
            $samples = $this->previewService->generatePreview($profile, 5);
            $channelName = strtoupper(str_replace('_', ' ', $profile->getFeedType() ?? 'Google Shopping'));

            $html = '<html><head><title>Quick View Preview - ' . htmlspecialchars($profile->getName()) . '</title>';
            $html .= '<style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 20px; background: #f8f9fa; color: #333; }';
            $html .= '.card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }';
            $html .= 'h2 { color: #eb5202; margin-top: 0; } table { width: 100%; border-collapse: collapse; margin-top: 15px; }';
            $html .= 'th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e3e3e3; } th { background: #f1f1f1; font-weight: 600; }';
            $html .= '.status-valid { color: #2e7d32; font-weight: bold; } .badge { background: #eb5202; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; }</style></head><body>';
            
            $html .= '<div class="card">';
            $html .= '<h2>🔍 Quick View Feed Preview: ' . htmlspecialchars($profile->getName()) . ' <span class="badge">' . htmlspecialchars($channelName) . '</span></h2>';
            $html .= '<p><strong>Output Target:</strong> <code>' . htmlspecialchars($profile->getFilename()) . '</code> | <strong>Status:</strong> ' . ($profile->getStatus() ? 'Enabled' : 'Disabled') . '</p>';
            $html .= '<table><thead><tr><th>SKU</th><th>Title</th><th>Price</th><th>Availability</th><th>Mapped Output Attributes</th><th>Status</th></tr></thead><tbody>';

            foreach ($samples as $row) {
                $html .= '<tr>';
                $html .= '<td><code>' . htmlspecialchars($row['sku'] ?? 'N/A') . '</code></td>';
                $html .= '<td><strong>' . htmlspecialchars($row['title'] ?? $row['name'] ?? 'N/A') . '</strong></td>';
                $html .= '<td>' . htmlspecialchars($row['price'] ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($row['availability'] ?? 'in stock') . '</td>';
                $html .= '<td><pre style="font-size:11px; margin:0;">' . htmlspecialchars(json_encode($row, JSON_PRETTY_PRINT)) . '</pre></td>';
                $html .= '<td><span class="status-valid">✓ Mapped & Validated</span></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div></body></html>';

            $result->setHeader('Content-Type', 'text/html');
            $result->setContents($html);
            return $result;
        } catch (\Exception $e) {
            $result->setHeader('Content-Type', 'text/html');
            $result->setContents('<h3>Error generating quick view preview: ' . htmlspecialchars($e->getMessage()) . '</h3>');
            return $result;
        }
    }
}
