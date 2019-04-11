<?php

declare(strict_types=1);

namespace Shopsys\FrameworkBundle\Model\Product;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchRepository;

class ProductOnCurrentDomainFromElasticFacade implements ProductOnCurrentDomainFacadeInterface
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository
     */
    protected $productAccessoryRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchRepository
     */
    protected $productElasticsearchRepository;

    public function __construct(
        ProductRepository $productRepository,
        Domain $domain,
        CurrentCustomer $currentCustomer,
        ProductAccessoryRepository $productAccessoryRepository,
        ProductElasticsearchRepository $productElasticsearchRepository
    ) {

        $this->productRepository = $productRepository;
        $this->domain = $domain;
        $this->currentCustomer = $currentCustomer;
        $this->productAccessoryRepository = $productAccessoryRepository;
        $this->productElasticsearchRepository = $productElasticsearchRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibleProductById($productId): Product
    {
        return $this->productRepository->getVisible(
            $productId,
            $this->domain->getId(),
            $this->currentCustomer->getPricingGroup()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessoriesForProduct(Product $product): array
    {
        return $this->productAccessoryRepository->getAllOfferedAccessoriesByProduct(
            $product,
            $this->domain->getId(),
            $this->currentCustomer->getPricingGroup()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getVariantsForProduct(Product $product): array
    {
        return $this->productRepository->getAllSellableVariantsByMainVariant(
            $product,
            $this->domain->getId(),
            $this->currentCustomer->getPricingGroup()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedProductsInCategory(ProductFilterData $productFilterData, $orderingModeId, $page, $limit, $categoryId): PaginationResult
    {
        $productIds = $this->productElasticsearchRepository->getOrderedProductIdsByFilterDataFromCategory($this->domain->getId(), $productFilterData, $orderingModeId, $categoryId, $this->currentCustomer->getPricingGroup());

        return $this->productRepository->getOfferedPaginationResultForProductIds($this->domain->getId(), $this->currentCustomer->getPricingGroup(), $productIds, $page, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedProductsForBrand($orderingModeId, $page, $limit, $brandId)
    {
        // TODO: Implement getPaginatedProductsForBrand() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedProductsForSearch($searchText, ProductFilterData $productFilterData, $orderingModeId, $page, $limit)
    {
        // TODO: Implement getPaginatedProductsForSearch() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchAutocompleteProducts($searchText, $limit)
    {
        // TODO: Implement getSearchAutocompleteProducts() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getProductFilterCountDataInCategory($categoryId, ProductFilterConfig $productFilterConfig, ProductFilterData $productFilterData)
    {
        // TODO: Implement getProductFilterCountDataInCategory() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getProductFilterCountDataForSearch($searchText, ProductFilterConfig $productFilterConfig, ProductFilterData $productFilterData)
    {
        // TODO: Implement getProductFilterCountDataForSearch() method.
    }
}
