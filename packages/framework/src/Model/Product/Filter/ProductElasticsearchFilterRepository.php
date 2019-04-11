<?php

declare(strict_types=1);

namespace Shopsys\FrameworkBundle\Model\Product\Filter;

use Elasticsearch\Client;

class ProductElasticsearchFilterRepository
{
    /**
     * @var string
     */
    protected $indexPrefix;

    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    public function __construct(string $indexPrefix, Client $client)
    {
        $this->indexPrefix = $indexPrefix;
        $this->client = $client;
    }



}
