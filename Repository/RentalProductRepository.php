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
     * 商品IDからレンタル設定を取得
     *
     * @param Product $product 商品エンティティ
     * @return RentalProduct|null
     */
    public function findByProduct(Product $product)
    {
        return $this->findOneBy(['Product' => $product]);
    }

    /**
     * レンタル可能な商品のみを取得
     *
     * @param bool $isEnabled レンタル有効フラグ
     * @return RentalProduct[]
     */
    public function findByEnabled($isEnabled = true)
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', $isEnabled)
            ->orderBy('rp.create_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 商品IDでレンタル設定を取得
     *
     * @param int $productId 商品ID
     * @return RentalProduct|null
     */
    public function findByProductId($productId)
    {
        return $this->createQueryBuilder('rp')
            ->innerJoin('rp.Product', 'p')
            ->where('p.id = :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 料金タイプ別の商品を取得
     *
     * @param string $priceType 料金タイプ（daily, weekly, monthly）
     * @return RentalProduct[]
     */
    public function findByPriceType($priceType)
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.price_type = :priceType')
            ->andWhere('rp.is_rental_enabled = :enabled')
            ->setParameter('priceType', $priceType)
            ->setParameter('enabled', true)
            ->orderBy('rp.rental_price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 価格帯で商品を検索
     *
     * @param float|null $minPrice 最低価格
     * @param float|null $maxPrice 最高価格
     * @return RentalProduct[]
     */
    public function findByPriceRange($minPrice = null, $maxPrice = null)
    {
        $qb = $this->createQueryBuilder('rp')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', true);

        if ($minPrice !== null) {
            $qb->andWhere('rp.rental_price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('rp.rental_price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        return $qb->orderBy('rp.rental_price', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * 在庫が設定されている商品を取得
     *
     * @param bool $hasStock 在庫の有無
     * @return RentalProduct[]
     */
    public function findByStockStatus($hasStock = true)
    {
        $qb = $this->createQueryBuilder('rp')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', true);

        if ($hasStock) {
            $qb->andWhere('rp.stock_unlimited = :unlimited OR rp.stock > 0')
               ->setParameter('unlimited', true);
        } else {
            $qb->andWhere('rp.stock_unlimited = :unlimited AND rp.stock <= 0')
               ->setParameter('unlimited', false);
        }

        return $qb->orderBy('rp.create_date', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * レンタル期間制限で商品を検索
     *
     * @param int $days 日数
     * @return RentalProduct[]
     */
    public function findByMaxRentalDays($days)
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.is_rental_enabled = :enabled')
            ->andWhere('rp.max_rental_days >= :days OR rp.max_rental_days IS NULL')
            ->setParameter('enabled', true)
            ->setParameter('days', $days)
            ->orderBy('rp.max_rental_days', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * レンタル可能数を更新
     *
     * @param RentalProduct $rentalProduct
     * @param int $quantity 変更数量（マイナス値で減少）
     * @return bool
     */
    public function updateStock(RentalProduct $rentalProduct, $quantity)
    {
        if ($rentalProduct->getStockUnlimited()) {
            return true; // 無制限の場合は更新不要
        }

        $newStock = $rentalProduct->getStock() + $quantity;
        
        if ($newStock < 0) {
            return false; // 在庫不足
        }

        $rentalProduct->setStock($newStock);
        $this->_em->persist($rentalProduct);
        $this->_em->flush();

        return true;
    }

    /**
     * 検索条件で商品を取得
     *
     * @param array $criteria 検索条件
     * @return RentalProduct[]
     */
    public function findByCriteria(array $criteria)
    {
        $qb = $this->createQueryBuilder('rp')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', true);

        // 商品名検索
        if (!empty($criteria['name'])) {
            $qb->innerJoin('rp.Product', 'p')
               ->andWhere('p.name LIKE :name')
               ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        // 料金タイプ
        if (!empty($criteria['price_type'])) {
            $qb->andWhere('rp.price_type = :priceType')
               ->setParameter('priceType', $criteria['price_type']);
        }

        // 価格範囲
        if (!empty($criteria['min_price'])) {
            $qb->andWhere('rp.rental_price >= :minPrice')
               ->setParameter('minPrice', $criteria['min_price']);
        }

        if (!empty($criteria['max_price'])) {
            $qb->andWhere('rp.rental_price <= :maxPrice')
               ->setParameter('maxPrice', $criteria['max_price']);
        }

        // レンタル期間
        if (!empty($criteria['max_days'])) {
            $qb->andWhere('rp.max_rental_days >= :maxDays OR rp.max_rental_days IS NULL')
               ->setParameter('maxDays', $criteria['max_days']);
        }

        // 並び順
        $orderBy = $criteria['order_by'] ?? 'create_date';
        $sort = $criteria['sort'] ?? 'DESC';
        $qb->orderBy("rp.{$orderBy}", $sort);

        return $qb->getQuery()->getResult();
    }

    /**
     * レンタル商品の統計情報を取得
     *
     * @return array
     */
    public function getStatistics()
    {
        $qb = $this->createQueryBuilder('rp');
        
        return [
            'total_products' => $qb->select('COUNT(rp.id)')
                                  ->getQuery()
                                  ->getSingleScalarResult(),
            
            'enabled_products' => $qb->select('COUNT(rp.id)')
                                    ->where('rp.is_rental_enabled = :enabled')
                                    ->setParameter('enabled', true)
                                    ->getQuery()
                                    ->getSingleScalarResult(),
            
            'avg_price' => $qb->select('AVG(rp.rental_price)')
                             ->where('rp.is_rental_enabled = :enabled')
                             ->setParameter('enabled', true)
                             ->getQuery()
                             ->getSingleScalarResult(),
            
            'total_stock' => $qb->select('SUM(rp.stock)')
                               ->where('rp.is_rental_enabled = :enabled')
                               ->andWhere('rp.stock_unlimited = :unlimited')
                               ->setParameter('enabled', true)
                               ->setParameter('unlimited', false)
                               ->getQuery()
                               ->getSingleScalarResult(),
        ];
    }
}