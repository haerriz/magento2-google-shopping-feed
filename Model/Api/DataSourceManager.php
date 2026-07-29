<?php
namespace Haerriz\GoogleShoppingFeed\Model\Api;

use Haerriz\GoogleShoppingFeed\Model\Api\MerchantClientV1;
use Google\Shopping\Merchant\DataSources\V1beta\DataSource;
use Google\Shopping\Merchant\DataSources\V1beta\PrimaryProductDataSource;

class DataSourceManager
{
    /**
     * @var MerchantClientV1
     */
    protected $clientV1;

    /**
     * @param MerchantClientV1 $clientV1
     */
    public function __construct(MerchantClientV1 $clientV1)
    {
        $this->clientV1 = $clientV1;
    }

    /**
     * Locate or provision an API Primary Data Source
     *
     * @param string $displayName
     * @param string $languageCode
     * @param string $feedLabel
     * @return string Data source resource name
     */
    public function getOrCreateDataSource($displayName, $languageCode, $feedLabel)
    {
        try {
            $client = $this->clientV1->getDataSourcesClient();
            $parent = 'accounts/' . $this->clientV1->getMerchantId();

            $response = $client->listDataSources($parent);
            foreach ($response->iterateAllElements() as $source) {
                if ($source->getDisplayName() === $displayName) {
                    return $source->getName();
                }
            }

            // Provision a new API product data source
            $dataSource = new DataSource();
            $dataSource->setDisplayName($displayName);
            
            $primarySource = new PrimaryProductDataSource();
            // Default online channels
            $primarySource->setChannel(1); // Online channel enum representation
            $primarySource->setContentLanguage($languageCode);
            $primarySource->setFeedLabel($feedLabel);

            $dataSource->setPrimaryProductDataSource($primarySource);

            $created = $client->createDataSource($parent, $dataSource);
            return $created->getName();
        } catch (\Exception $e) {
            return '';
        }
    }
}
