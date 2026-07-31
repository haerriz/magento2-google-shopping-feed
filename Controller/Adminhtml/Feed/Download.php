<?php
namespace Haerriz\GoogleShoppingFeed\Controller\Adminhtml\Feed;

use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Model\FeedExporter;
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
    private $exporter;

    public function __construct(
        Context $context,
        FeedProfileRepositoryInterface $repository,
        Filesystem $filesystem,
        FileFactory $fileFactory,
        FeedExporter $exporter
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->filesystem = $filesystem;
        $this->fileFactory = $fileFactory;
        $this->exporter = $exporter;
    }

    public function execute()
    {
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $profile = $this->repository->getById($id);
            $filename = basename((string)$profile->getFilename());

            if (!$filename) {
                throw new LocalizedException(__('The feed filename is invalid.'));
            }

            $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            // Path Option 1: Profile-scoped subdirectory
            $path1 = 'google_feed/profile_' . $profile->getId() . '/' . $filename;
            // Path Option 2: Direct media root file
            $path2 = $filename;

            $targetPath = null;
            if ($directory->isFile($path1)) {
                $targetPath = $path1;
            } elseif ($directory->isFile($path2)) {
                $targetPath = $path2;
            } else {
                // Auto-generate feed artifact if missing
                $this->exporter->export($profile, 'pub/media/' . $filename);
                if ($directory->isFile($path2)) {
                    $targetPath = $path2;
                }
            }

            if (!$targetPath || !$directory->isFile($targetPath)) {
                throw new LocalizedException(__('Feed file "%1" does not exist and could not be generated.', $filename));
            }

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mimeMap = [
                'xml' => 'application/xml',
                'csv' => 'text/csv',
                'txt' => 'text/plain',
                'tsv' => 'text/tab-separated-values',
                'jsonl' => 'application/x-ndjson',
                'json' => 'application/json'
            ];
            $contentType = $mimeMap[$ext] ?? 'application/octet-stream';

            return $this->fileFactory->create(
                $filename,
                ['type' => 'filename', 'value' => $targetPath, 'rm' => false],
                DirectoryList::MEDIA,
                $contentType
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error downloading feed: %1', $e->getMessage()));
            $redirect = $this->resultRedirectFactory->create();
            return $redirect->setPath('*/*/');
        }
    }
}
