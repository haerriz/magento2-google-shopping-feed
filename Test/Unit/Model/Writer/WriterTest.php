<?php
namespace Haerriz\GoogleShoppingFeed\Test\Unit\Model\Writer;

use Haerriz\GoogleShoppingFeed\Model\FeedProfile;
use Haerriz\GoogleShoppingFeed\Model\Writer\JsonLines;
use PHPUnit\Framework\TestCase;

class WriterTest extends TestCase
{
    public function testJsonLinesProducesOneDecodableObjectPerRow()
    {
        $stream = new MemoryStream();
        $writer = new JsonLines();
        $profile = new FeedProfile();

        $writer->start($stream, $profile, ['g:id', 'g:title']);
        $writer->writeRow($stream, $profile, ['g:id' => '1', 'g:title' => 'Café']);
        $writer->writeRow($stream, $profile, ['g:id' => '2', 'g:title' => "Line\nTwo"]);
        $writer->finish($stream, $profile);

        $lines = array_values(array_filter(explode("\n", $stream->contents)));
        $this->assertCount(2, $lines);
        $this->assertSame('Café', json_decode($lines[0], true)['g:title']);
        $this->assertSame("Line\nTwo", json_decode($lines[1], true)['g:title']);
    }
}

class MemoryStream
{
    public $contents = '';

    public function write($value)
    {
        $this->contents .= $value;
    }
}
