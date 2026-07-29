<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\CredentialProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class CredentialProvider implements CredentialProviderInterface
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
    }

    public function encrypt($secret)
    {
        return $this->encryptor->encrypt((string)$secret);
    }

    public function decrypt($encryptedSecret)
    {
        if ($encryptedSecret === null || $encryptedSecret === '') {
            return '';
        }

        return $this->encryptor->decrypt((string)$encryptedSecret);
    }

    public function getConfigSecret($path, $scopeType = 'default', $scopeCode = null)
    {
        return $this->decrypt($this->scopeConfig->getValue($path, $scopeType, $scopeCode));
    }
}
