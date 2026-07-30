<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\CredentialProviderInterface;
use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class CredentialProvider implements CredentialProviderInterface
{
    private $encryptor;

    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    public function getDecryptedPassword(FeedProfileInterface $profile): string
    {
        $encrypted = $profile->getDeliveryPassword();
        if (!$encrypted) {
            return '';
        }
        return $this->encryptor->decrypt($encrypted);
    }
}
