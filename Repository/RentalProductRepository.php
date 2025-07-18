<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Rental\Repository;

use Eccube\Repository\AbstractRepository;
use Eccube\Entity\Product;
use Plugin\Rental\Entity\RentalProduct;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * レンタル商品データアクセス Repository
 */
class RentalProductRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RentalProduct::class);
    }

    /**
     * 商品IDからレンタル商品設定を取得
     *
     * @param int $productId 商品ID
     * @return RentalProduct|null
     */
    public function findByProductId($productId)
    {
        return $this->findOneBy(['Product' => $productId]);
    }

    /**
     * 商品エンティティからレンタル商品設定を取得
     *
     * @param Product $product 商品エンティティ
     * @return RentalProduct|null
     */
    public function findByProduct(Product $product)
    {
        return $this->findOneBy(['Product' => $product]);
    }

    /**
     * レンタル有効な商品のみを取得
     *
     * @return RentalProduct[]
     */
    public function findEnabledProducts()
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * 自動承認設定の商品を取得
     *
     * @return RentalProduct[]
     */
    public function findAutoApprovalProducts()
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.is_rental_enabled = :enabled')
            ->andWhere('rp.auto_approval = :autoApproval')
            ->setParameter('enabled', true)
            ->setParameter('autoApproval', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * 価格帯で商品を検索
     *
     * @param float|null $minPrice 最小価格
     * @param float|null $maxPrice 最大価格
     * @param string $priceType 価格タイプ（daily, weekly, monthly）
     * @return RentalProduct[]
     */
    public function findByPriceRange($minPrice = null, $maxPrice = null, $priceType = 'daily')
    {
        $qb = $this->createQueryBuilder('rp')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', true);

        $priceField = $priceType . '_price';
        
        if ($minPrice !== null) {
            $qb->andWhere("rp.{$priceField} >= :minPrice")
               ->setParameter('minPrice', $minPrice);
        }
        
        if ($maxPrice !== null) {
            $qb->andWhere("rp.{$priceField} <= :maxPrice")
               ->setParameter('maxPrice', $maxPrice);
        }
        
        $qb->andWhere("rp.{$priceField} IS NOT NULL");
        
        return $qb->getQuery()->getResult();
    }

    /**
     * レンタル期間で商品を検索
     *
     * @param int|null $minDays 最小日数
     * @param int|null $maxDays 最大日数
     * @return RentalProduct[]
     */
    public function findByRentalPeriod($minDays = null, $maxDays = null)
    {
        $qb = $this->createQueryBuilder('rp')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', true);

        if ($minDays !== null) {
            $qb->andWhere('rp.min_rental_days <= :minDays')
               ->setParameter('minDays', $minDays);
        }
        
        if ($maxDays !== null) {
            $qb->andWhere('(rp.max_rental_days IS NULL OR rp.max_rental_days >= :maxDays)')
               ->setParameter('maxDays', $maxDays);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * 商品とレンタル設定を結合して検索
     *
     * @param array $criteria 検索条件
     * @param array $orderBy 並び順
     * @param int|null $limit 取得件数
     * @param int|null $offset オフセット
     * @return array
     */
    public function findProductsWithRental(array $criteria = [], array $orderBy = [], $limit = null, $offset = null)
    {
        $qb = $this->createQueryBuilder('rp')
            ->innerJoin('rp.Product', 'p')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', true);

        // 検索条件適用
        foreach ($criteria as $field => $value) {
            if (strpos($field, 'product.') === 0) {
                $productField = str_replace('product.', 'p.', $field);
                $qb->andWhere("{$productField} = :{$field}")
                   ->setParameter($field, $value);
            } else {
                $qb->andWhere("rp.{$field} = :{$field}")
                   ->setParameter($field, $value);
            }
        }

        // 並び順適用
        foreach ($orderBy as $field => $direction) {
            if (strpos($field, 'product.') === 0) {
                $productField = str_replace('product.', 'p.', $field);
                $qb->orderBy($productField, $direction);
            } else {
                $qb->orderBy("rp.{$field}", $direction);
            }
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 商品の料金設定があるかチェック
     *
     * @param Product $product 商品エンティティ
     * @return bool
     */
    public function hasPricingSetting(Product $product)
    {
        $rentalProduct = $this->findByProduct($product);
        return $rentalProduct ? $rentalProduct->hasPricingSetting() : false;
    }

    /**
     * 商品の最安価格を取得
     *
     * @param Product $product 商品エンティティ
     * @return float|null
     */
    public function getMinPrice(Product $product)
    {
        $rentalProduct = $this->findByProduct($product);
        if (!$rentalProduct) {
            return null;
        }

        $prices = array_filter([
            $rentalProduct->getDailyPrice(),
            $rentalProduct->getWeeklyPrice() ? $rentalProduct->getWeeklyPrice() / 7 : null,
            $rentalProduct->getMonthlyPrice() ? $rentalProduct->getMonthlyPrice() / 30 : null,
        ]);

        return !empty($prices) ? min($prices) : null;
    }

    /**
     * 準備日数が必要な商品を取得
     *
     * @return RentalProduct[]
     */
    public function findProductsWithPreparationDays()
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.is_rental_enabled = :enabled')
            ->andWhere('rp.preparation_days > 0')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * 商品をレンタル有効化
     *
     * @param Product $product 商品エンティティ
     * @return RentalProduct
     */
    public function enableRental(Product $product)
    {
        $rentalProduct = $this->findByProduct($product);
        
        if (!$rentalProduct) {
            $rentalProduct = new RentalProduct();
            $rentalProduct->setProduct($product);
        }
        
        $rentalProduct->setIsRentalEnabled(true);
        
        $this->getEntityManager()->persist($rentalProduct);
        $this->getEntityManager()->flush();
        
        return $rentalProduct;
    }

    /**
     * 商品をレンタル無効化
     *
     * @param Product $product 商品エンティティ
     * @return void
     */
    public function disableRental(Product $product)
    {
        $rentalProduct = $this->findByProduct($product);
        
        if ($rentalProduct) {
            $rentalProduct->setIsRentalEnabled(false);
            $this->getEntityManager()->persist($rentalProduct);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 商品の料金設定を更新
     *
     * @param Product $product 商品エンティティ
     * @param array $priceData 料金データ
     * @return RentalProduct
     */
    public function updatePricing(Product $product, array $priceData)
    {
        $rentalProduct = $this->findByProduct($product);
        
        if (!$rentalProduct) {
            $rentalProduct = new RentalProduct();
            $rentalProduct->setProduct($product);
        }
        
        if (isset($priceData['daily_price'])) {
            $rentalProduct->setDailyPrice($priceData['daily_price']);
        }
        
        if (isset($priceData['weekly_price'])) {
            $rentalProduct->setWeeklyPrice($priceData['weekly_price']);
        }
        
        if (isset($priceData['monthly_price'])) {
            $rentalProduct->setMonthlyPrice($priceData['monthly_price']);
        }
        
        if (isset($priceData['deposit_amount'])) {
            $rentalProduct->setDepositAmount($priceData['deposit_amount']);
        }
        
        $this->getEntityManager()->persist($rentalProduct);
        $this->getEntityManager()->flush();
        
        return $rentalProduct;
    }

    /**
     * 一括料金更新
     *
     * @param array $updates 更新データ配列 [product_id => price_data]
     * @return void
     */
    public function bulkUpdatePricing(array $updates)
    {
        foreach ($updates as $productId => $priceData) {
            $product = $this->getEntityManager()->getRepository(Product::class)->find($productId);
            if ($product) {
                $this->updatePricing($product, $priceData);
            }
        }
    }

    /**
     * 統計情報を取得
     *
     * @return array
     */
    public function getStatistics()
    {
        $qb = $this->createQueryBuilder('rp');
        
        $total = $qb->select('COUNT(rp.id)')
            ->getQuery()
            ->getSingleScalarResult();
            
        $enabled = $qb->select('COUNT(rp.id)')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleScalarResult();
            
        $autoApproval = $qb->select('COUNT(rp.id)')
            ->where('rp.is_rental_enabled = :enabled')
            ->andWhere('rp.auto_approval = :autoApproval')
            ->setParameter('enabled', true)
            ->setParameter('autoApproval', true)
            ->getQuery()
            ->getSingleScalarResult();
            
        return [
            'total' => $total,
            'enabled' => $enabled,
            'disabled' => $total - $enabled,
            'auto_approval' => $autoApproval,
        ];
    }

    /**
     * 商品を削除する際のクリーンアップ
     *
     * @param Product $product 商品エンティティ
     * @return void
     */
    public function cleanupByProduct(Product $product)
    {
        $rentalProduct = $this->findByProduct($product);
        
        if ($rentalProduct) {
            $this->getEntityManager()->remove($rentalProduct);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 検索クエリビルダーを取得
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderForSearch()
    {
        return $this->createQueryBuilder('rp')
            ->innerJoin('rp.Product', 'p')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', true);
    }
}