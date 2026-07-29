<?php
namespace Haerriz\GoogleShoppingFeed\Test\Unit\Model;

use Haerriz\GoogleShoppingFeed\Model\FeedProfile;
use Haerriz\GoogleShoppingFeed\Model\ProductTypeResolver;
use Haerriz\GoogleShoppingFeed\Model\ProfileConfigReader;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status;
use Magento\Framework\App\ResourceConnection;
use PHPUnit\Framework\TestCase;

class ProductTypeResolverTest extends TestCase
{
    private function createResolver()
    {
        return new ProductTypeResolver(
            $this->createMock(ResourceConnection::class),
            $this->createMock(CollectionFactory::class),
            $this->createMock(Status::class),
            new ProfileConfigReader()
        );
    }

    public function testSimpleProductProducesOneRow()
    {
        $product = new Product();
        $product->setTypeId('simple');

        $this->assertSame([$product], $this->createResolver()->resolve($product, new FeedProfile()));
    }

    public function testVirtualAndDownloadableRequireExplicitOptIn()
    {
        $profile = new FeedProfile();
        $virtual = new Product();
        $virtual->setTypeId('virtual');
        $downloadable = new Product();
        $downloadable->setTypeId('downloadable');

        $resolver = $this->createResolver();
        $this->assertSame([], $resolver->resolve($virtual, $profile));
        $this->assertSame([], $resolver->resolve($downloadable, $profile));

        $profile->setData('include_virtual', 1);
        $profile->setData('include_downloadable', 1);
        $this->assertSame([$virtual], $resolver->resolve($virtual, $profile));
        $this->assertSame([$downloadable], $resolver->resolve($downloadable, $profile));
    }
}
