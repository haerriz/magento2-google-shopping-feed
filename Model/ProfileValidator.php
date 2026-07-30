<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Model\Mapping\RowBuilder;

class ProfileValidator
{
    private $rowBuilder;

    public function __construct(RowBuilder $rowBuilder)
    {
        $this->rowBuilder = $rowBuilder;
    }

    public function validate(FeedProfileInterface $profile): array
    {
        $errors = [];
        if (!$profile->getName()) {
            $errors[] = __('Profile name is required.');
        }
        if (!$profile->getFilename()) {
            $errors[] = __('Output filename is required.');
        }
        $mappingErrors = $this->rowBuilder->validate($profile);
        return array_merge($errors, $mappingErrors);
    }
}
