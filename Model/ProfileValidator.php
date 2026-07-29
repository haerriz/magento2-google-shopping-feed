<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Model\Mapping\RowBuilder;
use Haerriz\GoogleShoppingFeed\Model\Writer\Pool as WriterPool;
use Magento\Store\Model\StoreManagerInterface;

class ProfileValidator
{
    private $rowBuilder;
    private $writerPool;
    private $storeManager;
    private $configReader;

    public function __construct(
        RowBuilder $rowBuilder,
        WriterPool $writerPool,
        StoreManagerInterface $storeManager,
        ProfileConfigReader $configReader
    ) {
        $this->rowBuilder = $rowBuilder;
        $this->writerPool = $writerPool;
        $this->storeManager = $storeManager;
        $this->configReader = $configReader;
    }

    public function validate(FeedProfileInterface $profile)
    {
        $errors = [];
        $warnings = [];
        if (trim((string)$profile->getName()) === '') {
            $errors['name'][] = 'Profile name is required.';
        }
        try {
            $this->storeManager->getStore((int)$profile->getStoreId());
        } catch (\Throwable $exception) {
            $errors['store_id'][] = 'Select a valid store view.';
        }
        $format = strtolower((string)$profile->getFeedType());
        try {
            $this->writerPool->get($format);
        } catch (\InvalidArgumentException $exception) {
            $errors['feed_type'][] = $exception->getMessage();
        }
        $filename = basename((string)$profile->getFilename());
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($filename === '' || $filename !== (string)$profile->getFilename()) {
            $errors['filename'][] = 'Filename must be a plain file name without directories.';
        } elseif ($extension !== $format) {
            $errors['filename'][] = 'Filename extension must match the selected format.';
        }
        foreach ($this->rowBuilder->validate($profile) as $error) {
            $errors['mappings'][] = $error;
        }
        $required = ['g:id', 'g:title', 'g:description', 'g:link', 'g:image_link', 'g:price', 'g:availability'];
        $fields = array_map(static function (array $mapping) {
            return (string)($mapping['google_attribute'] ?? '');
        }, $this->rowBuilder->getMappings($profile));
        foreach ($required as $field) {
            if (!in_array($field, $fields, true) && !in_array(substr($field, 2), $fields, true)) {
                $errors['mappings'][] = 'Required Google field is not mapped: ' . $field;
            }
        }
        if (in_array($format, ['csv', 'txt'], true)) {
            $delimiter = (string)$this->configReader->get($profile, 'delimiter', ',');
            $enclosure = (string)$this->configReader->get($profile, 'enclosure', '"');
            if (strlen($delimiter) !== 1 || strlen($enclosure) !== 1) {
                $errors['delimiter'][] = 'Delimiter and enclosure must each be one byte.';
            }
        }
        $delivery = (string)$profile->getDeliveryType();
        if (in_array($delivery, ['ftp', 'sftp'], true)) {
            foreach (['delivery_host', 'delivery_username', 'delivery_path'] as $field) {
                if (trim((string)$this->configReader->get($profile, $field, '')) === '') {
                    $errors[$field][] = 'This delivery field is required.';
                }
            }
        }
        if (!$fields) {
            $warnings['mappings'][] = 'No output will be generated until mappings are configured.';
        }
        return ['valid' => !$errors, 'errors' => $errors, 'warnings' => $warnings];
    }

    public function assertValid(FeedProfileInterface $profile)
    {
        $result = $this->validate($profile);
        if (!$result['valid']) {
            $messages = [];
            foreach ($result['errors'] as $fieldMessages) {
                $messages = array_merge($messages, $fieldMessages);
            }
            throw new \InvalidArgumentException(implode(' ', $messages));
        }
    }
}
