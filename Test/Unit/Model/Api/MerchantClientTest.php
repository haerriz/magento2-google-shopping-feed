<?php
namespace Haerriz\GoogleShoppingFeed\Test\Unit\Model\Api;

use PHPUnit\Framework\TestCase;
use Haerriz\GoogleShoppingFeed\Model\Api\MerchantClient;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

class MerchantClientTest extends TestCase
{
    protected $merchantClient;
    protected $scopeConfigMock;
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->merchantClient = new MerchantClient(
            $this->scopeConfigMock,
            $this->loggerMock
        );
    }

    public function testInsertProductThrowsUnimplementedError()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Merchant API delivery is not yet implemented.');

        $this->merchantClient->insertProduct(['id' => '123']);
    }
}
