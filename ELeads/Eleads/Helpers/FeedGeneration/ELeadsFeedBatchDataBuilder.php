<?php

namespace Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration;

use Okay\Core\Database;
use Okay\Core\QueryFactory;
use Okay\Entities\BrandsEntity;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\ImagesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Entities\VariantsEntity;
use Okay\Helpers\ProductsHelper;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsFeedDataBuilder;

class ELeadsFeedBatchDataBuilder
{
    private QueryFactory $queryFactory;
    private Database $db;
    private ProductsEntity $productsEntity;
    private CategoriesEntity $categoriesEntity;
    private VariantsEntity $variantsEntity;
    private ImagesEntity $imagesEntity;
    private BrandsEntity $brandsEntity;
    private ProductsHelper $productsHelper;

    public function __construct(
        QueryFactory $queryFactory,
        Database $db,
        ProductsEntity $productsEntity,
        CategoriesEntity $categoriesEntity,
        VariantsEntity $variantsEntity,
        ImagesEntity $imagesEntity,
        BrandsEntity $brandsEntity,
        ProductsHelper $productsHelper
    ) {
        $this->queryFactory = $queryFactory;
        $this->db = $db;
        $this->productsEntity = $productsEntity;
        $this->categoriesEntity = $categoriesEntity;
        $this->variantsEntity = $variantsEntity;
        $this->imagesEntity = $imagesEntity;
        $this->brandsEntity = $brandsEntity;
        $this->productsHelper = $productsHelper;
    }

    public function getVisibleProductBatch(int $lastProductId, int $limit): array
    {
        $select = $this->queryFactory->newSelect();
        $select->cols(['id'])
            ->from(ProductsEntity::getTable())
            ->where('visible = 1')
            ->where('id > :last_product_id')
            ->orderBy(['id ASC'])
            ->limit(max(1, $limit))
            ->bindValue('last_product_id', $lastProductId);

        $this->db->query($select);
        $productIds = array_map('intval', (array) $this->db->results('id'));
        if (empty($productIds)) {
            return [];
        }

        $productsById = [];
        foreach ($this->productsEntity->find(['id' => $productIds, 'limit' => count($productIds)]) as $product) {
            $productsById[(int) $product->id] = $product;
        }

        $products = [];
        foreach ($productIds as $productId) {
            if (isset($productsById[$productId])) {
                $products[] = $productsById[$productId];
            }
        }

        return $products;
    }

    public function buildBatchData(array $products): array
    {
        $productIds = ELeadsFeedDataBuilder::buildProductIds($products);

        return [
            'product_ids' => $productIds,
            'product_categories' => ELeadsFeedDataBuilder::buildProductCategories($this->categoriesEntity, $productIds),
            'variants_by_product' => ELeadsFeedDataBuilder::buildVariantsByProduct($this->variantsEntity, $productIds),
            'brands_by_id' => ELeadsFeedDataBuilder::buildBrandsById($this->brandsEntity),
            'images_by_product' => ELeadsFeedDataBuilder::buildImagesByProduct($this->imagesEntity, $productIds),
            'feature_map' => ELeadsFeedDataBuilder::buildProductFeatures($this->productsHelper, $products),
        ];
    }
}
