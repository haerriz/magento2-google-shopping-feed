<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Exception\LocalizedException;

class Download extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Haerriz_GoogleShoppingFeed::download';

    private $repository;
    private $filesystem;
    private $fileFactory;

    public function __construct(
        Context $context,
        FeedProfileRepositoryInterface $repository,
        Filesystem $filesystem,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->filesystem = $filesystem;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        try {
            $id = (int)$this->getRequest()->getParam('id');
            if ($id <= 0) {
                $this->messageManager->addErrorMessage(__('Invalid feed profile ID.'));
                return $redirect->setPath('*/*/');
            }

            $profile = $this->repository->getById($id);
            $filename = basename((string)$profile->getFilename());

            if (!$filename) {
                $this->messageManager->addErrorMessage(__('The profile has an invalid filename.'));
                return $redirect->setPath('*/*/');
            }

            $directoryRead = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            // Fast Path Resolution: Check direct media root, then subdirectories
            $candidates = [
                $filename,
                'google_feed/profile_' . $profile->getId() . '/' . $filename,
                'pub/media/' . $filename
            ];

            $resolvedPath = null;
            foreach ($candidates as $candidate) {
                if ($directoryRead->isFile($candidate)) {
                    $resolvedPath = $candidate;
                    break;
                }
            }

            if (!$resolvedPath) {
                $this->messageManager->addNoticeMessage(
                    __('Feed file "%1" is not yet generated. Click "Generate Now" or wait for scheduled cron to build it.', $filename)
                );
                return $redirect->setPath('*/*/');
            }

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $contentTypeMap = [
                'xml' => 'application/xml',
                'csv' => 'text/csv',
                'txt' => 'text/plain',
                'tsv' => 'text/tab-separated-values',
                'jsonl' => 'application/x-ndjson',
                'json' => 'application/json'
            ];
            $contentType = $contentTypeMap[$ext] ?? 'application/octet-stream';

            // Instant file download streaming without HTTP timeout
            return $this->fileFactory->create(
                $filename,
                ['type' => 'filename', 'value' => $resolvedPath, 'rm' => false],
                DirectoryList::MEDIA,
                $contentType
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Download failed: %1', $e->getMessage()));
            return $redirect->setPath('*/*/');
        }
    }
}
