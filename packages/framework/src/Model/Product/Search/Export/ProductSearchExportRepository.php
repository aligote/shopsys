<?php

namespace Shopsys\FrameworkBundle\Model\Product\Search\Export;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;

class ProductSearchExportRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository
     */
    private $parameterRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(EntityManagerInterface $em, ParameterRepository $parameterRepository, ProductFacade $productFacade)
    {
        $this->em = $em;
        $this->parameterRepository = $parameterRepository;
        $this->productFacade = $productFacade;
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @param int $startFrom
     * @param int $batchSize
     * @return array
     */
    public function getProductsData(int $domainId, string $locale, int $startFrom, int $batchSize): array
    {
        $queryBuilder = $this->createQueryBuilder($domainId, $locale)
            ->setFirstResult($startFrom)
            ->setMaxResults($batchSize);

        $query = $queryBuilder->getQuery();

        $result = [];
        /** @var \Shopsys\FrameworkBundle\Model\Product\Product $product */
        foreach ($query->getResult() as $product) {
            $flagIds = [];
            foreach ($product->getFlags() as $flag) {
                $flagIds[] = [$flag->getId()];
            }

            $categoryIds = [];
            foreach ($product->getCategoriesIndexedByDomainId()[$domainId] as $category) {
                $categoryIds[] = [$category->getId()];
            }

            $parameters = [];
            $productParameterValues = $this->parameterRepository->getProductParameterValuesByProductSortedByName($product, $locale);
            foreach ($productParameterValues as $index => $productParameterValue) {
                $parameter = $productParameterValue->getParameter();
                $parameterValue = $productParameterValue->getValue();
                if ($parameter->getName($locale) !== null && $parameterValue->getLocale() === $locale) {
                    $parameters[] = [
                        'parameter_id' => $parameter->getId(),
                        'parameter_value_id' => $parameterValue->getId(),
                    ];
                }
            }

            $prices = [];
            $productSellingPrices = $this->productFacade->getAllProductSellingPricesIndexedByDomainId($product)[$domainId];
            /** @var \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductSellingPrice $productSellingPrice */
            foreach ($productSellingPrices as $productSellingPrice) {
                $prices[] = [
                    'pricing_group_id' => $productSellingPrice->getPricingGroup()->getId(),
                    'amount' => $productSellingPrice->getSellingPrice()->getPriceWithVat()->getAmount(),
                ];
            }

            $result[] = [
                'id' => $product->getId(),
                'catnum' => $product->getCatnum(),
                'partno' => $product->getPartno(),
                'ean' => $product->getEan(),
                'name' => $product->getName($locale),
                'description' => $product->getDescription($domainId),
                'shortDescription' => $product->getShortDescription($domainId),
                'brand' => $product->getBrand() ? $product->getBrand()->getId() : '',
                'flags' => $flagIds,
                'categories' => $categoryIds,
                'in_stock' => $product->getCalculatedAvailability()->getDispatchTime() === 0,
                'prices' => $prices,
                'parameters' => $parameters,
                'ordering_priority' => $product->getOrderingPriority(),
                'calculated_selling_denied' => $product->getCalculatedSellingDenied()
            ];
        }

        return $result;
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createQueryBuilder(int $domainId, string $locale): QueryBuilder
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.variantType != :variantTypeVariant')
            ->join(ProductVisibility::class, 'prv', Join::WITH, 'prv.product = p.id')
            ->andWhere('prv.domainId = :domainId')
            ->andWhere('prv.visible = TRUE')
            ->join('p.translations', 't')
            ->andWhere('t.locale = :locale')
            ->join('p.domains', 'd')
            ->andWhere('d.domainId = :domainId')
            ->groupBy('p.id')
            ->orderBy('p.id');

        $queryBuilder->setParameter('domainId', $domainId)
            ->setParameter('locale', $locale)
            ->setParameter('variantTypeVariant', Product::VARIANT_TYPE_VARIANT);

        return $queryBuilder;
    }
}
