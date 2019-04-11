<?php

declare(strict_types=1);

namespace Shopsys\FrameworkBundle\Model\Product\Search;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingConfig;

class FilterQuery
{
    /** @var array */
    protected $filters = [];

    /** @var string|null */
    protected $orderingModeId;

    /** @var string */
    protected $indexName;

    public function __construct(string $indexName)
    {
        $this->indexName = $indexName;
    }

    /**
     * @param string $orderingModeId
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
     */
    public function applyOrdering(string $orderingModeId): self
    {
        $this->orderingModeId = $orderingModeId;

        return $this;
    }

    /**
     * @param array $parameters
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
     */
    public function filterByParameters(array $parameters): self
    {
        foreach ($parameters as $parameterId => $parameterValues) {
            $this->filters[] = [
                'nested' => [
                    'path' => 'parameters',
                    'query' => [
                        'bool' => [
                            'must' => [
                                'match_all' => new \stdClass(),
                            ],
                            'filter' => [
                                ['term' => [
                                    'parameters.parameter_id' => $parameterId,
                                ]],
                                ['terms' => [
                                    'parameters.parameter_value_id' => $parameterValues,
                                ]],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $this;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minimalPrice
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $maximalPrice
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
     */
    public function filterByPrices(PricingGroup $pricingGroup, Money $minimalPrice = null, Money $maximalPrice = null): self
    {
        $prices = [];
        if ($minimalPrice !== null) {
            $prices['gte'] = (float)$minimalPrice->getAmount();
        }
        if ($maximalPrice !== null) {
            $prices['lte'] = (float)$maximalPrice->getAmount();
        }

        $this->filters['prices'] = ['nested' => [
            'path' => 'prices',
            'query' => [
                'bool' => [
                    'must' => [
                        'match_all' => new \stdClass(),
                    ],
                    'filter' => [
                        ['term' => [
                            'prices.pricing_group_id' => $pricingGroup->getId(),
                        ]],
                        ['range' => [
                            'prices.amount' => $prices,
                        ],
                        ],
                    ],
                ],
            ],
        ]];

        return $this;
    }

    /**
     * @param int[] $categoryIds
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
     */
    public function filterByCategory(array $categoryIds): self
    {
        $this->filters[] = ['terms' => [
            'categories' => $categoryIds,
        ]];

        return $this;
    }

    /**
     * @param int[] $brandIds
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
     */
    public function filterByBrands(array $brandIds): self
    {
        $this->filters[] = ['terms' => [
            'brand' => $brandIds,
        ]];

        return $this;
    }

    /**
     * @param int[] $flagIds
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
     */
    public function filterByFlags(array $flagIds): self
    {
        $this->filters[] = ['terms' => [
            'flags' => $flagIds,
        ]];

        return $this;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
     */
    public function filterOnlyInStock(): self
    {
        $this->filters[] = ['term' => [
            'in_stock' => true,
        ]];

        return $this;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        $query = [
            'index' => $this->indexName,
            'type' => '_doc',
            'size' => 1000,
            'body' => [
                'query' => ['bool' => [
                    'must' => [
                        'match_all' => new \stdClass(),
                    ],
                    'filter' => [
                        \array_values($this->filters),
                    ],
                ],
                ],
            ],
        ];

        if ($this->orderingModeId !== null) {
            $query['body']['sort'] = $this->createOrdering($this->orderingModeId);
        }

        return $query;
    }

    /**
     * @param string $orderingModeId
     * @return array
     */
    protected function createOrdering(?string $orderingModeId): array
    {
        $sorting = ['ordering_priority' => 'asc'];

        if ($orderingModeId === ProductListOrderingConfig::ORDER_BY_NAME_ASC) {
            $sorting = [
                'name.keyword' => 'asc',
            ];
        } elseif ($orderingModeId === ProductListOrderingConfig::ORDER_BY_NAME_DESC) {
            $sorting = [
                'name.keyword' => 'desc',
            ];
        } elseif ($orderingModeId === ProductListOrderingConfig::ORDER_BY_PRICE_ASC) {
            $sorting = [
                'prices.amount' => [
                    'order' => 'asc',
                ],
            ];
        } elseif ($orderingModeId === ProductListOrderingConfig::ORDER_BY_PRICE_DESC) {
            $sorting = [
                'prices.amount' => [
                    'order' => 'desc',
                ],
            ];
        }

        return $sorting;
    }
}
