<?php
namespace Haerriz\GoogleShoppingFeed\Model\Writer;

use Haerriz\GoogleShoppingFeed\Api\WriterInterface;

class Pool
{
    private $writers;

    public function __construct(array $writers = [])
    {
        $this->writers = $writers;
    }

    public function get($format)
    {
        $format = strtolower((string)$format);
        if (!isset($this->writers[$format]) || !$this->writers[$format] instanceof WriterInterface) {
            throw new \InvalidArgumentException('Unsupported feed format: ' . $format);
        }
        return $this->writers[$format];
    }
}
