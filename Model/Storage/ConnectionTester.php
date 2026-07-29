<?php
namespace Haerriz\GoogleShoppingFeed\Model\Storage;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;

class ConnectionTester
{
    private $ftp;
    private $sftp;

    public function __construct(Ftp $ftp, Sftp $sftp)
    {
        $this->ftp = $ftp;
        $this->sftp = $sftp;
    }

    public function test(FeedProfileInterface $profile)
    {
        if ($profile->getDeliveryType() === 'ftp') {
            return $this->ftp->testConnection($profile);
        }
        if ($profile->getDeliveryType() === 'sftp') {
            return $this->sftp->testConnection($profile);
        }
        if ($profile->getDeliveryType() === 'local') {
            return true;
        }
        throw new \InvalidArgumentException('Connection testing is unsupported for this delivery type.');
    }
}
