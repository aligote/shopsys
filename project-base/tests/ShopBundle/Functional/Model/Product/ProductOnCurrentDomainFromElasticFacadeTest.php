<?php

namespace Shopsys\FrameworkBundle\Model\Product;

use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingConfig;
use Shopsys\ShopBundle\Model\Category\Category;
use Tests\ShopBundle\Functional\Model\Product\ProductOnCurrentDomainFacadeTest;

class ProductOnCurrentDomainFromElasticFacadeTest extends ProductOnCurrentDomainFacadeTest
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @return mixed
     */
    public function getPaginationResultInCategory(ProductFilterData $productFilterData, Category $category): PaginationResult
    {
        /** @var \Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacade $productOnCurrentDomainFacade */
        $productOnCurrentDomainFacade = $this->getContainer()->get(ProductOnCurrentDomainFromElasticFacade::class);
        $page = 1;
        $limit = PHP_INT_MAX;

        return $productOnCurrentDomainFacade->getPaginatedProductsInCategory(
            $productFilterData,
            ProductListOrderingConfig::ORDER_BY_NAME_ASC,
            $page,
            $limit,
            $category->getId()
        );
    }
}
