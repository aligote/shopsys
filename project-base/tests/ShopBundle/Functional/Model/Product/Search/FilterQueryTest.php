<?php

namespace Tests\ShopBundle\Functional\Model\Product\Search;

use Elasticsearch\Client;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingConfig;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery;
use Shopsys\ShopBundle\DataFixtures\Demo\PricingGroupDataFixture;
use Tests\ShopBundle\Test\TransactionFunctionalTestCase;

class FilterQueryTest extends TransactionFunctionalTestCase
{
    private const ELASTICSEARCH_INDEX = 'product1';

    public function testBrand(): void
    {
        $filter = new FilterQuery(self::ELASTICSEARCH_INDEX);
        $filter->filterByBrands([1]);

        $this->assertIdWithFilter($filter, ['5']);
    }

    public function testFlag(): void
    {
        $filter = new FilterQuery(self::ELASTICSEARCH_INDEX);
        $filter->filterByFlags([3]);

        $this->assertIdWithFilter($filter, ['1', '5', '16', '33', '39', '40', '45', '50', '70']);
    }

    public function testFlagBrand(): void
    {
        $filter = new FilterQuery(self::ELASTICSEARCH_INDEX);
        $filter->filterByBrands([12]);
        $filter->filterByFlags([1]);

        $this->assertIdWithFilter($filter, ['17', '19']);
    }

    public function testMultiFilter(): void
    {
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup */
        $pricingGroup = $this->getReference(PricingGroupDataFixture::PRICING_GROUP_ORDINARY_DOMAIN_1);

        $filter = new FilterQuery(self::ELASTICSEARCH_INDEX);
        $filter->filterOnlyInStock()
            ->filterByCategory([9])
            ->filterByFlags([1])
            ->filterByPrices($pricingGroup, null, Money::create(20));

        $this->assertIdWithFilter($filter, ['50']);
    }

    public function testParameters(): void
    {
        $filter = new FilterQuery(self::ELASTICSEARCH_INDEX);

        $parameters = [50 => [109, 115], 49 => [105, 121], 10 => [107]];

        $filter->filterByParameters($parameters);

        $this->assertIdWithFilter($filter, ['25', '28']);
    }

    public function testOrdering(): void
    {
        $filter = new FilterQuery(self::ELASTICSEARCH_INDEX);
        $filter->filterByCategory([9]);

        $this->assertIdWithFilter($filter, ['72', '25', '27', '29', '28', '26', '50', '33', '39', '40']);

        $filter->applyOrdering(ProductListOrderingConfig::ORDER_BY_NAME_ASC);
        $this->assertIdWithFilter($filter, ['15', '25', '26', '27', '28', '29', '33', '39', '40', '47', '50', '72']);

        $filter->applyOrdering(ProductListOrderingConfig::ORDER_BY_NAME_DESC);
        $this->assertIdWithFilter($filter, ['72', '50', '47', '40', '39', '33', '29', '28', '27', '26', '25', '15']);
    }

    protected function assertIdWithFilter(FilterQuery $filterQuery, array $ids): void
    {
        /** @var \Elasticsearch\Client $es */
        $es = $this->getContainer()->get(Client::class);

        $params = $filterQuery->getQuery();

        $params['_source'] = false;

        $result = $es->search($params);
        $this->assertSame($ids, $this->extractIds($result));
    }

    /**
     * @param array $result
     * @return int[]
     */
    protected function extractIds(array $result): array
    {
        $hits = $result['hits']['hits'];

        return array_column($hits, '_id');
    }
}
