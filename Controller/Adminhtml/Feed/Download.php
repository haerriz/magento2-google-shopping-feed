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
        $profile = $this->repository->getById((int)$this->getRequest()->getParam('id'));
        $filename = basename((string)$profile->getFilename());
        if ($filename !== (string)$profile->getFilename() || $filename === '') {
            throw new LocalizedException(__('The feed filename is invalid.'));
        }
        $path = 'google_feed/profile_' . (int)$profile->getId() . '/' . $filename;
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        if (!$directory->isFile($path)) {
            throw new LocalizedException(__('No successfully published artifact exists for this profile.'));
        }
        $mime = [
            'xml' => 'application/xml',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'tsv' => 'text/tab-separated-values',
            'jsonl' => 'application/x-ndjson',
        ][strtolower((string)$profile->getFeedType())] ?? 'application/octet-stream';

        return $this->fileFactory->create(
            $filename,
            ['type' => 'filename', 'value' => $path, 'rm' => false],
            DirectoryList::MEDIA,
            $mime
        );
    }
}
