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
use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Plugin\Rental\Entity\RentalCart;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * レンタルカートデータアクセス Repository (MySQL対応完全版)
 */
class RentalCartRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RentalCart::class);
    }

    /**
     * セッションIDでカート商品を取得
     *
     * @param string $sessionId セッションID
     * @return RentalCart[]
     */
    public function findBySessionId($sessionId)
    {
        return $this->createQueryBuilder('rc')
            ->where('rc.session_id = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('rc.create_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 顧客のカート商品を取得
     *
     * @param Customer $customer 顧客エンティティ
     * @return RentalCart[]
     */
    public function findByCustomer(Customer $customer)
    {
        return $this->createQueryBuilder('rc')
            ->where('rc.Customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('rc.create_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * セッションIDまたは顧客でカート商品を取得
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return RentalCart[]
     */
    public function findBySessionOrCustomer($sessionId, Customer $customer = null)
    {
        $qb = $this->createQueryBuilder('rc');

        if ($customer) {
            $qb->where('rc.Customer = :customer OR rc.session_id = :sessionId')
               ->setParameter('customer', $customer)
               ->setParameter('sessionId', $sessionId);
        } else {
            $qb->where('rc.session_id = :sessionId')
               ->setParameter('sessionId', $sessionId);
        }

        return $qb->orderBy('rc.create_date', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * 商品でカート商品を検索
     *
     * @param Product $product 商品エンティティ
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return RentalCart|null
     */
    public function findByProduct(Product $product, $sessionId, Customer $customer = null)
    {
        $qb = $this->createQueryBuilder('rc')
            ->where('rc.Product = :product')
            ->setParameter('product', $product);

        if ($customer) {
            $qb->andWhere('(rc.Customer = :customer OR rc.session_id = :sessionId)')
               ->setParameter('customer', $customer)
               ->setParameter('sessionId', $sessionId);
        } else {
            $qb->andWhere('rc.session_id = :sessionId')
               ->setParameter('sessionId', $sessionId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 期間が重複するカート商品を検索
     *
     * @param Product $product 商品エンティティ
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @param RentalCart|null $excludeCart 除外するカート商品
     * @return RentalCart[]
     */
    public function findOverlappingPeriod(Product $product, \DateTime $startDate, \DateTime $endDate, $sessionId, Customer $customer = null, RentalCart $excludeCart = null)
    {
        $qb = $this->createQueryBuilder('rc')
            ->where('rc.Product = :product')
            ->andWhere('(rc.rental_start_date <= :endDate AND rc.rental_end_date >= :startDate)')
            ->setParameter('product', $product)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($customer) {
            $qb->andWhere('(rc.Customer = :customer OR rc.session_id = :sessionId)')
               ->setParameter('customer', $customer)
               ->setParameter('sessionId', $sessionId);
        } else {
            $qb->andWhere('rc.session_id = :sessionId')
               ->setParameter('sessionId', $sessionId);
        }

        if ($excludeCart) {
            $qb->andWhere('rc.id != :excludeId')
               ->setParameter('excludeId', $excludeCart->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * カートの合計金額を取得
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return string
     */
    public function getTotalAmount($sessionId, Customer $customer = null)
    {
        $cartItems = $this->findBySessionOrCustomer($sessionId, $customer);
        $total = '0';

        foreach ($cartItems as $cartItem) {
            $total = bcadd($total, $cartItem->getTotalPrice(), 2);
        }

        return $total;
    }

    /**
     * カートの商品数を取得
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return int
     */
    public function getTotalQuantity($sessionId, Customer $customer = null)
    {
        $qb = $this->createQueryBuilder('rc')
            ->select('SUM(rc.quantity)');

        if ($customer) {
            $qb->where('rc.Customer = :customer OR rc.session_id = :sessionId')
               ->setParameter('customer', $customer)
               ->setParameter('sessionId', $sessionId);
        } else {
            $qb->where('rc.session_id = :sessionId')
               ->setParameter('sessionId', $sessionId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * カートが空かどうかチェック
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return bool
     */
    public function isEmpty($sessionId, Customer $customer = null)
    {
        return $this->getTotalQuantity($sessionId, $customer) === 0;
    }

    /**
     * セッションIDまたは顧客のカートをクリア
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return int 削除された件数
     */
    public function clearBySessionOrCustomer($sessionId, Customer $customer = null)
    {
        $qb = $this->createQueryBuilder('rc')
            ->delete();

        if ($customer) {
            $qb->where('rc.Customer = :customer OR rc.session_id = :sessionId')
               ->setParameter('customer', $customer)
               ->setParameter('sessionId', $sessionId);
        } else {
            $qb->where('rc.session_id = :sessionId')
               ->setParameter('sessionId', $sessionId);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * 古いカート商品を削除
     *
     * @param int $days 保持日数
     * @return int 削除された件数
     */
    public function deleteOldCartItems($days = 7)
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->sub(new \DateInterval("P{$days}D"));

        return $this->createQueryBuilder('rc')
            ->delete()
            ->where('rc.update_date < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    /**
     * セッションから顧客にカートを移行
     *
     * @param string $sessionId セッションID
     * @param Customer $customer 顧客エンティティ
     * @return void
     */
    public function transferCartToCustomer($sessionId, Customer $customer)
    {
        $sessionCartItems = $this->findBySessionId($sessionId);
        $customerCartItems = $this->findByCustomer($customer);

        // 顧客の既存カート商品をマッピング
        $customerCartMap = [];
        foreach ($customerCartItems as $cartItem) {
            $key = $cartItem->getProduct()->getId() . '_' . 
                   $cartItem->getRentalStartDate()->format('Y-m-d') . '_' . 
                   $cartItem->getRentalEndDate()->format('Y-m-d');
            $customerCartMap[$key] = $cartItem;
        }

        foreach ($sessionCartItems as $sessionCart) {
            $key = $sessionCart->getProduct()->getId() . '_' . 
                   $sessionCart->getRentalStartDate()->format('Y-m-d') . '_' . 
                   $sessionCart->getRentalEndDate()->format('Y-m-d');

            if (isset($customerCartMap[$key])) {
                // 既存の顧客カートに数量を追加
                $existingCart = $customerCartMap[$key];
                $existingCart->setQuantity($existingCart->getQuantity() + $sessionCart->getQuantity());
                $this->_em->persist($existingCart);
                
                // セッションカートを削除
                $this->_em->remove($sessionCart);
            } else {
                // セッションカートを顧客カートに変換
                $sessionCart->setCustomer($customer);
                $sessionCart->setSessionId(null);
                $this->_em->persist($sessionCart);
            }
        }

        $this->_em->flush();
    }

    /**
     * カート商品の検証
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return array 検証結果 ['valid' => bool, 'errors' => array]
     */
    public function validateCartItems($sessionId, Customer $customer = null)
    {
        $cartItems = $this->findBySessionOrCustomer($sessionId, $customer);
        $errors = [];
        $valid = true;

        foreach ($cartItems as $cartItem) {
            // 商品の存在チェック
            if (!$cartItem->getProduct()) {
                $errors[] = 'カートに無効な商品が含まれています';
                $valid = false;
                continue;
            }

            // レンタル期間の妥当性チェック
            if ($cartItem->getRentalStartDate() >= $cartItem->getRentalEndDate()) {
                $errors[] = $cartItem->getProduct()->getName() . ': レンタル期間が無効です';
                $valid = false;
            }

            // 過去日チェック
            $today = new \DateTime('today');
            if ($cartItem->getRentalStartDate() < $today) {
                $errors[] = $cartItem->getProduct()->getName() . ': レンタル開始日が過去の日付です';
                $valid = false;
            }

            // 数量チェック
            if ($cartItem->getQuantity() <= 0) {
                $errors[] = $cartItem->getProduct()->getName() . ': 数量が無効です';
                $valid = false;
            }
        }

        return [
            'valid' => $valid,
            'errors' => $errors
        ];
    }

    /**
     * 在庫切れ商品をカートから削除
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return array 削除された商品名の配列
     */
    public function removeOutOfStockItems($sessionId, Customer $customer = null)
    {
        $cartItems = $this->findBySessionOrCustomer($sessionId, $customer);
        $removedItems = [];

        foreach ($cartItems as $cartItem) {
            // レンタル商品の在庫チェック（実際の在庫チェックロジックは別途実装）
            $rentalProduct = $cartItem->getProduct()->getRentalProduct();
            if ($rentalProduct && !$rentalProduct->hasAvailableStock($cartItem->getQuantity())) {
                $removedItems[] = $cartItem->getProduct()->getName();
                $this->_em->remove($cartItem);
            }
        }

        if (!empty($removedItems)) {
            $this->_em->flush();
        }

        return $removedItems;
    }

    /**
     * カート統計を取得 (MySQL対応版)
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getCartStatistics(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT rc.session_id) as total_sessions,
                COUNT(DISTINCT rc.customer_id) as total_customers,
                COUNT(rc.id) as total_items,
                AVG(items_per_session.item_count) as avg_items_per_session,
                SUM(rc.quantity) as total_quantity,
                AVG(rc.quantity) as avg_quantity_per_item
            FROM plg_rental_cart rc
            LEFT JOIN (
                SELECT session_id, COUNT(id) as item_count
                FROM plg_rental_cart
                WHERE create_date >= :startDate AND create_date <= :endDate
                GROUP BY session_id
            ) items_per_session ON rc.session_id = items_per_session.session_id
            WHERE rc.create_date >= :startDate 
              AND rc.create_date <= :endDate
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        $result = $stmt->executeQuery()->fetchAssociative();

        return [
            'total_sessions' => $result['total_sessions'] ?? 0,
            'total_customers' => $result['total_customers'] ?? 0,
            'total_items' => $result['total_items'] ?? 0,
            'avg_items_per_session' => round($result['avg_items_per_session'] ?? 0, 2),
            'total_quantity' => $result['total_quantity'] ?? 0,
            'avg_quantity_per_item' => round($result['avg_quantity_per_item'] ?? 0, 2)
        ];
    }

    /**
     * カート放棄分析を取得 (MySQL対応版)
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getCartAbandonmentAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                DATE_FORMAT(rc.create_date, '%Y-%m-%d') as date,
                COUNT(DISTINCT rc.session_id) as total_carts,
                COUNT(DISTINCT CASE 
                    WHEN ro.id IS NOT NULL THEN rc.session_id 
                END) as converted_carts,
                COUNT(DISTINCT CASE 
                    WHEN ro.id IS NULL THEN rc.session_id 
                END) as abandoned_carts,
                ROUND(
                    (COUNT(DISTINCT CASE WHEN ro.id IS NULL THEN rc.session_id END) / 
                     COUNT(DISTINCT rc.session_id)) * 100, 
                    2
                ) as abandonment_rate
            FROM plg_rental_cart rc
            LEFT JOIN plg_rental_order ro ON rc.session_id = ro.session_id 
                AND ro.create_date >= rc.create_date
                AND ro.create_date <= DATE_ADD(rc.create_date, INTERVAL 1 DAY)
            WHERE rc.create_date >= :startDate 
              AND rc.create_date <= :endDate
            GROUP BY DATE_FORMAT(rc.create_date, '%Y-%m-%d')
            ORDER BY date ASC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * 人気商品分析（カート投入ベース）
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param int $limit 取得件数
     * @return array
     */
    public function getPopularProductsInCart(\DateTime $startDate, \DateTime $endDate, $limit = 20)
    {
        return $this->createQueryBuilder('rc')
            ->select('
                p.id as product_id,
                p.name as product_name,
                COUNT(rc.id) as cart_count,
                SUM(rc.quantity) as total_quantity,
                COUNT(DISTINCT rc.session_id) as unique_sessions,
                COUNT(DISTINCT rc.customer_id) as unique_customers
            ')
            ->leftJoin('rc.Product', 'p')
            ->where('rc.create_date >= :startDate')
            ->andWhere('rc.create_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('p.id, p.name')
            ->orderBy('cart_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * レンタル期間別カート分析
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getRentalPeriodCartAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN DATEDIFF(rc.rental_end_date, rc.rental_start_date) + 1 <= 3 THEN '1-3日'
                    WHEN DATEDIFF(rc.rental_end_date, rc.rental_start_date) + 1 <= 7 THEN '4-7日'
                    WHEN DATEDIFF(rc.rental_end_date, rc.rental_start_date) + 1 <= 14 THEN '8-14日'
                    WHEN DATEDIFF(rc.rental_end_date, rc.rental_start_date) + 1 <= 30 THEN '15-30日'
                    ELSE '31日以上'
                END as period_range,
                COUNT(rc.id) as cart_count,
                AVG(rc.quantity) as avg_quantity,
                COUNT(DISTINCT rc.session_id) as unique_sessions
            FROM plg_rental_cart rc
            WHERE rc.create_date >= :startDate
              AND rc.create_date <= :endDate
            GROUP BY 
                CASE 
                    WHEN DATEDIFF(rc.rental_end_date, rc.rental_start_date) + 1 <= 3 THEN '1-3日'
                    WHEN DATEDIFF(rc.rental_end_date, rc.rental_start_date) + 1 <= 7 THEN '4-7日'
                    WHEN DATEDIFF(rc.rental_end_date, rc.rental_start_date) + 1 <= 14 THEN '8-14日'
                    WHEN DATEDIFF(rc.rental_end_date, rc.rental_start_date) + 1 <= 30 THEN '15-30日'
                    ELSE '31日以上'
                END
            ORDER BY 
                CASE period_range
                    WHEN '1-3日' THEN 1
                    WHEN '4-7日' THEN 2
                    WHEN '8-14日' THEN 3
                    WHEN '15-30日' THEN 4
                    ELSE 5
                END
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * カート保持期間分析
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getCartRetentionAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(HOUR, rc.create_date, rc.update_date) <= 1 THEN '1時間以内'
                    WHEN TIMESTAMPDIFF(HOUR, rc.create_date, rc.update_date) <= 6 THEN '1-6時間'
                    WHEN TIMESTAMPDIFF(HOUR, rc.create_date, rc.update_date) <= 24 THEN '6-24時間'
                    WHEN TIMESTAMPDIFF(HOUR, rc.create_date, rc.update_date) <= 72 THEN '1-3日'
                    ELSE '3日以上'
                END as retention_period,
                COUNT(rc.id) as cart_count,
                COUNT(DISTINCT rc.session_id) as unique_sessions,
                AVG(rc.quantity) as avg_quantity
            FROM plg_rental_cart rc
            WHERE rc.create_date >= :startDate
              AND rc.create_date <= :endDate
            GROUP BY 
                CASE 
                    WHEN TIMESTAMPDIFF(HOUR, rc.create_date, rc.update_date) <= 1 THEN '1時間以内'
                    WHEN TIMESTAMPDIFF(HOUR, rc.create_date, rc.update_date) <= 6 THEN '1-6時間'
                    WHEN TIMESTAMPDIFF(HOUR, rc.create_date, rc.update_date) <= 24 THEN '6-24時間'
                    WHEN TIMESTAMPDIFF(HOUR, rc.create_date, rc.update_date) <= 72 THEN '1-3日'
                    ELSE '3日以上'
                END
            ORDER BY 
                CASE retention_period
                    WHEN '1時間以内' THEN 1
                    WHEN '1-6時間' THEN 2
                    WHEN '6-24時間' THEN 3
                    WHEN '1-3日' THEN 4
                    ELSE 5
                END
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }
}