<?php
namespace Haerriz\GoogleShoppingFeed\Test\Unit\Model\Modifier;

use Haerriz\GoogleShoppingFeed\Model\FeedProfile;
use Haerriz\GoogleShoppingFeed\Model\Modifier\Pipeline;
use Haerriz\GoogleShoppingFeed\Model\Modifier\Pool;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    private function pipeline()
    {
        return new Pipeline($this->createMock(Pool::class));
    }

    public function testModifierOrderAndMultibyteTruncationAreStable()
    {
        $value = $this->pipeline()->apply(
            "  <b>café product</b>  ",
            [
                ['code' => 'strip_html'],
                ['code' => 'normalize_whitespace'],
                ['code' => 'upper'],
                ['code' => 'truncate', 'value' => 4],
                ['code' => 'append', 'value' => '!'],
            ],
            new Product(),
            new FeedProfile()
        );

        $this->assertSame('CAFÉ!', $value);
    }

    public function testZeroIsNotReplacedByDefault()
    {
        $value = $this->pipeline()->apply(
            0,
            [['code' => 'default', 'value' => 'fallback']],
            new Product(),
            new FeedProfile()
        );
        $this->assertSame(0, $value);
    }

    public function testInvalidRegexIsRejectedBeforeUse()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->pipeline()->validate([['code' => 'regex_replace', 'pattern' => '/[broken/']]);
    }
}
