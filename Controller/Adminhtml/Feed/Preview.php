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
            $previewData = $this->previewService->preview($profile, 10);
            $channelName = strtoupper(str_replace('_', ' ', $profile->getFeedType() ?? 'Google Shopping'));

            $html = '<html><head><title>Quick View Preview - ' . htmlspecialchars($profile->getName()) . '</title>';
            $html .= '<style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 25px; background: #f4f6f9; color: #333; }';
            $html .= '.card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }';
            $html .= 'h2 { color: #eb5202; margin-top: 0; display: flex; align-items: center; justify-content: space-between; }';
            $html .= '.badge { background: #eb5202; color: #fff; padding: 5px 12px; border-radius: 4px; font-size: 13px; font-weight: normal; }';
            $html .= 'pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px; line-height: 1.5; }</style></head><body>';
            
            $html .= '<div class="card">';
            $html .= '<h2>🔍 Quick View Feed Preview: ' . htmlspecialchars($profile->getName()) . ' <span class="badge">' . htmlspecialchars($channelName) . '</span></h2>';
            $html .= '<p><strong>Output File:</strong> <code>' . htmlspecialchars($profile->getFilename()) . '</code> | <strong>Status:</strong> ' . ($profile->getStatus() ? 'Enabled' : 'Disabled') . '</p>';
            $html .= '<h3>Generated Sample Output Payload:</h3>';
            $html .= '<pre>' . htmlspecialchars($previewData['content'] ?? 'No preview content generated.') . '</pre>';
            $html .= '</div></body></html>';

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
