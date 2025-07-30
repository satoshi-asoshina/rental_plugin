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
use Plugin\Rental\Entity\RentalOrder;
use Plugin\Rental\Entity\RentalProduct;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * レンタル注文データアクセス Repository (MySQL対応完全版)
 */
class RentalOrderRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RentalOrder::class);
    }

    /**
     * 注文番号から注文を取得
     *
     * @param string $orderNo 注文番号
     * @return RentalOrder|null
     */
    public function findByOrderNo($orderNo)
    {
        return $this->findOneBy(['order_no' => $orderNo]);
    }

    /**
     * 顧客の注文履歴を取得
     *
     * @param Customer $customer 顧客エンティティ
     * @param array $orderBy 並び順
     * @param int|null $limit 取得件数
     * @return RentalOrder[]
     */
    public function findByCustomer(Customer $customer, array $orderBy = ['create_date' => 'DESC'], $limit = null)
    {
        $qb = $this->createQueryBuilder('ro')
            ->where('ro.Customer = :customer')
            ->setParameter('customer', $customer);

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("ro.{$field}", $direction);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * ステータス別の注文を取得
     *
     * @param int $status ステータス
     * @param array $orderBy 並び順
     * @return RentalOrder[]
     */
    public function findByStatus($status, array $orderBy = ['create_date' => 'DESC'])
    {
        $qb = $this->createQueryBuilder('ro')
            ->where('ro.status = :status')
            ->setParameter('status', $status);

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("ro.{$field}", $direction);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 期間別の注文を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param int|null $status ステータス
     * @return RentalOrder[]
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, $status = null)
    {
        $qb = $this->createQueryBuilder('ro')
            ->where('ro.create_date >= :startDate')
            ->andWhere('ro.create_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($status !== null) {
            $qb->andWhere('ro.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('ro.create_date', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * レンタル期間で注文を検索
     *
     * @param \DateTime $startDate レンタル開始日
     * @param \DateTime $endDate レンタル終了日
     * @param bool $overlap 期間重複を含むか
     * @return RentalOrder[]
     */
    public function findByRentalPeriod(\DateTime $startDate, \DateTime $endDate, $overlap = true)
    {
        $qb = $this->createQueryBuilder('ro');

        if ($overlap) {
            // 期間が重複する注文を検索
            $qb->where('(ro.rental_start_date <= :endDate AND ro.rental_end_date >= :startDate)')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        } else {
            // 期間内に完全に含まれる注文を検索
            $qb->where('ro.rental_start_date >= :startDate')
               ->andWhere('ro.rental_end_date <= :endDate')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('ro.rental_start_date', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * 延滞中の注文を取得
     *
     * @param \DateTime|null $currentDate 現在日時（nullの場合は現在時刻）
     * @return RentalOrder[]
     */
    public function findOverdueOrders(\DateTime $currentDate = null)
    {
        if ($currentDate === null) {
            $currentDate = new \DateTime();
        }

        return $this->createQueryBuilder('ro')
            ->where('ro.status = :rentedStatus')
            ->andWhere('ro.rental_end_date < :currentDate')
            ->setParameter('rentedStatus', 2) // レンタル中
            ->setParameter('currentDate', $currentDate)
            ->orderBy('ro.rental_end_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 返却予定日が近い注文を取得
     *
     * @param int $reminderDays リマインダー日数
     * @return RentalOrder[]
     */
    public function findUpcomingReturns($reminderDays = 3)
    {
        $startDate = new \DateTime();
        $endDate = new \DateTime();
        $endDate->add(new \DateInterval("P{$reminderDays}D"));

        return $this->createQueryBuilder('ro')
            ->where('ro.status = :rentedStatus')
            ->andWhere('ro.rental_end_date >= :startDate')
            ->andWhere('ro.rental_end_date <= :endDate')
            ->setParameter('rentedStatus', 2) // レンタル中
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('ro.rental_end_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 商品別の注文統計を取得
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getProductStatistics(RentalProduct $rentalProduct, \DateTime $startDate, \DateTime $endDate)
    {
        $qb = $this->createQueryBuilder('ro')
            ->where('ro.RentalProduct = :rentalProduct')
            ->andWhere('ro.create_date >= :startDate')
            ->andWhere('ro.create_date <= :endDate')
            ->setParameter('rentalProduct', $rentalProduct)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        $totalOrders = $qb->select('COUNT(ro.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalAmount = $qb->select('SUM(ro.total_amount)')
            ->getQuery()
            ->getSingleScalarResult();

        $avgAmount = $totalOrders > 0 ? ($totalAmount / $totalOrders) : 0;

        $totalQuantity = $qb->select('SUM(ro.quantity)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_orders' => $totalOrders,
            'total_amount' => $totalAmount ?? 0,
            'avg_amount' => round($avgAmount, 2),
            'total_quantity' => $totalQuantity ?? 0,
        ];
    }

    /**
     * 月別売上統計を取得 (MySQL対応版)
     *
     * @param int $months 取得月数
     * @return array
     */
    public function getMonthlySales($months = 12)
    {
        $endDate = new \DateTime('last day of this month 23:59:59');
        $startDate = clone $endDate;
        $startDate->sub(new \DateInterval("P{$months}M"))->modify('first day of this month 00:00:00');

        $sql = "
            SELECT 
                DATE_FORMAT(ro.create_date, '%Y-%m') as month,
                COUNT(ro.id) as order_count,
                SUM(ro.total_amount) as total_amount,
                AVG(ro.total_amount) as avg_amount,
                SUM(ro.quantity) as total_quantity
            FROM plg_rental_order ro
            WHERE ro.create_date >= :startDate
              AND ro.create_date <= :endDate
              AND ro.status != 0  -- 仮注文以外
            GROUP BY DATE_FORMAT(ro.create_date, '%Y-%m')
            ORDER BY month ASC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * 延滞分析を取得 (MySQL対応版)
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getOverdueAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                c.id as customer_id,
                CONCAT(c.name01, ' ', c.name02) as customer_name,
                c.email,
                COUNT(ro.id) as total_orders,
                COUNT(CASE WHEN ro.status = 5 THEN 1 END) as overdue_orders,
                ROUND(
                    (COUNT(CASE WHEN ro.status = 5 THEN 1 END) / COUNT(ro.id)) * 100, 
                    2
                ) as overdue_rate,
                SUM(CASE WHEN ro.status = 5 THEN ro.overdue_fee ELSE 0 END) as total_overdue_fees,
                MAX(CASE WHEN ro.status = 5 THEN DATEDIFF(NOW(), ro.rental_end_date) ELSE 0 END) as max_overdue_days
            FROM plg_rental_order ro
            INNER JOIN dtb_customer c ON ro.customer_id = c.id
            WHERE ro.create_date >= :startDate
              AND ro.create_date <= :endDate
            GROUP BY c.id, c.name01, c.name02, c.email
            HAVING overdue_orders > 0
            ORDER BY overdue_rate DESC, total_overdue_fees DESC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * 季節性分析を取得 (MySQL対応版)
     *
     * @param int $years 分析年数
     * @return array
     */
    public function getSeasonalityAnalysis($years = 2)
    {
        $startDate = new \DateTime();
        $startDate->sub(new \DateInterval("P{$years}Y"));

        $sql = "
            SELECT 
                MONTH(ro.create_date) as month,
                MONTHNAME(ro.create_date) as month_name,
                COUNT(ro.id) as order_count,
                SUM(ro.total_amount) as total_amount,
                AVG(ro.total_amount) as avg_amount,
                SUM(ro.quantity) as total_quantity
            FROM plg_rental_order ro
            WHERE ro.create_date >= :startDate
              AND ro.status != 0
            GROUP BY MONTH(ro.create_date), MONTHNAME(ro.create_date)
            ORDER BY MONTH(ro.create_date)
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * リピート顧客分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getRepeatCustomerAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                customer_order_counts.order_count_range,
                COUNT(customer_order_counts.customer_id) as customer_count,
                SUM(customer_order_counts.total_amount) as total_revenue,
                AVG(customer_order_counts.total_amount) as avg_revenue_per_customer
            FROM (
                SELECT 
                    c.id as customer_id,
                    COUNT(ro.id) as order_count,
                    SUM(ro.total_amount) as total_amount,
                    CASE 
                        WHEN COUNT(ro.id) = 1 THEN '1回'
                        WHEN COUNT(ro.id) <= 3 THEN '2-3回'
                        WHEN COUNT(ro.id) <= 5 THEN '4-5回'
                        WHEN COUNT(ro.id) <= 10 THEN '6-10回'
                        ELSE '11回以上'
                    END as order_count_range
                FROM plg_rental_order ro
                INNER JOIN dtb_customer c ON ro.customer_id = c.id
                WHERE ro.create_date >= :startDate
                  AND ro.create_date <= :endDate
                  AND ro.status != 0
                GROUP BY c.id
            ) as customer_order_counts
            GROUP BY customer_order_counts.order_count_range
            ORDER BY 
                CASE customer_order_counts.order_count_range
                    WHEN '1回' THEN 1
                    WHEN '2-3回' THEN 2
                    WHEN '4-5回' THEN 3
                    WHEN '6-10回' THEN 4
                    ELSE 5
                END
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * 在庫効率分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getInventoryEfficiencyAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                rp.id as rental_product_id,
                p.name as product_name,
                COUNT(DISTINCT ri.id) as total_inventory,
                COUNT(ro.id) as rental_count,
                SUM(DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1) as total_rental_days,
                ROUND(
                    SUM(DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1) / 
                    (COUNT(DISTINCT ri.id) * DATEDIFF(:endDate, :startDate)), 
                    4
                ) as inventory_utilization_rate,
                ROUND(
                    COUNT(ro.id) / COUNT(DISTINCT ri.id), 
                    2
                ) as turnover_rate
            FROM plg_rental_product rp
            INNER JOIN dtb_product p ON rp.product_id = p.id
            LEFT JOIN plg_rental_inventory ri ON rp.id = ri.rental_product_id
            LEFT JOIN plg_rental_order ro ON rp.id = ro.rental_product_id 
                AND ro.create_date >= :startDate 
                AND ro.create_date <= :endDate
                AND ro.status != 0
            WHERE rp.is_rental_enabled = 1
            GROUP BY rp.id, p.name
            HAVING total_inventory > 0
            ORDER BY inventory_utilization_rate DESC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * 価格帯別分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getPriceRangeAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN ro.total_amount < 1000 THEN '1,000円未満'
                    WHEN ro.total_amount < 3000 THEN '1,000-2,999円'
                    WHEN ro.total_amount < 5000 THEN '3,000-4,999円'
                    WHEN ro.total_amount < 10000 THEN '5,000-9,999円'
                    WHEN ro.total_amount < 20000 THEN '10,000-19,999円'
                    ELSE '20,000円以上'
                END as price_range,
                COUNT(ro.id) as order_count,
                SUM(ro.total_amount) as total_amount,
                AVG(ro.total_amount) as avg_amount,
                COUNT(DISTINCT ro.customer_id) as unique_customers
            FROM plg_rental_order ro
            WHERE ro.create_date >= :startDate
              AND ro.create_date <= :endDate
              AND ro.status != 0
            GROUP BY 
                CASE 
                    WHEN ro.total_amount < 1000 THEN '1,000円未満'
                    WHEN ro.total_amount < 3000 THEN '1,000-2,999円'
                    WHEN ro.total_amount < 5000 THEN '3,000-4,999円'
                    WHEN ro.total_amount < 10000 THEN '5,000-9,999円'
                    WHEN ro.total_amount < 20000 THEN '10,000-19,999円'
                    ELSE '20,000円以上'
                END
            ORDER BY 
                CASE price_range
                    WHEN '1,000円未満' THEN 1
                    WHEN '1,000-2,999円' THEN 2
                    WHEN '3,000-4,999円' THEN 3
                    WHEN '5,000-9,999円' THEN 4
                    WHEN '10,000-19,999円' THEN 5
                    ELSE 6
                END
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * ステータス別統計を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getStatusStatistics(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('ro')
            ->select('
                ro.status,
                COUNT(ro.id) as order_count,
                SUM(ro.total_amount) as total_amount,
                SUM(ro.quantity) as total_quantity
            ')
            ->where('ro.create_date >= :startDate')
            ->andWhere('ro.create_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('ro.status')
            ->orderBy('ro.status')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * 顧客別統計を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param int $limit 取得件数
     * @return array
     */
    public function getCustomerStatistics(\DateTime $startDate, \DateTime $endDate, $limit = 10)
    {
        return $this->createQueryBuilder('ro')
            ->select('
                c.id as customer_id,
                c.name01,
                c.name02,
                c.email,
                COUNT(ro.id) as order_count,
                SUM(ro.total_amount) as total_amount,
                AVG(ro.total_amount) as avg_amount
            ')
            ->leftJoin('ro.Customer', 'c')
            ->where('ro.create_date >= :startDate')
            ->andWhere('ro.create_date <= :endDate')
            ->andWhere('ro.Customer IS NOT NULL')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('c.id, c.name01, c.name02, c.email')
            ->orderBy('total_amount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * 注文を検索
     *
     * @param array $criteria 検索条件
     * @return RentalOrder[]
     */
    public function searchOrders(array $criteria)
    {
        $qb = $this->createQueryBuilder('ro')
            ->leftJoin('ro.Customer', 'c')
            ->leftJoin('ro.RentalProduct', 'rp')
            ->leftJoin('rp.Product', 'p');

        // 注文番号
        if (!empty($criteria['order_no'])) {
            $qb->andWhere('ro.order_no LIKE :orderNo')
               ->setParameter('orderNo', '%' . $criteria['order_no'] . '%');
        }

        // 顧客名
        if (!empty($criteria['customer_name'])) {
            $qb->andWhere('(c.name01 LIKE :customerName OR c.name02 LIKE :customerName)')
               ->setParameter('customerName', '%' . $criteria['customer_name'] . '%');
        }

        // 顧客メール
        if (!empty($criteria['customer_email'])) {
            $qb->andWhere('c.email LIKE :customerEmail')
               ->setParameter('customerEmail', '%' . $criteria['customer_email'] . '%');
        }

        // 商品名
        if (!empty($criteria['product_name'])) {
            $qb->andWhere('p.name LIKE :productName')
               ->setParameter('productName', '%' . $criteria['product_name'] . '%');
        }

        // ステータス
        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $qb->andWhere('ro.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        // 注文日期間
        if (!empty($criteria['order_date_start'])) {
            $qb->andWhere('ro.create_date >= :orderDateStart')
               ->setParameter('orderDateStart', $criteria['order_date_start']);
        }

        if (!empty($criteria['order_date_end'])) {
            $qb->andWhere('ro.create_date <= :orderDateEnd')
               ->setParameter('orderDateEnd', $criteria['order_date_end']);
        }

        // レンタル期間
        if (!empty($criteria['rental_start_date'])) {
            $qb->andWhere('ro.rental_start_date >= :rentalStartDate')
               ->setParameter('rentalStartDate', $criteria['rental_start_date']);
        }

        if (!empty($criteria['rental_end_date'])) {
            $qb->andWhere('ro.rental_end_date <= :rentalEndDate')
               ->setParameter('rentalEndDate', $criteria['rental_end_date']);
        }

        // 金額範囲
        if (!empty($criteria['min_amount'])) {
            $qb->andWhere('ro.total_amount >= :minAmount')
               ->setParameter('minAmount', $criteria['min_amount']);
        }

        if (!empty($criteria['max_amount'])) {
            $qb->andWhere('ro.total_amount <= :maxAmount')
               ->setParameter('maxAmount', $criteria['max_amount']);
        }

        // 並び順
        $orderBy = $criteria['order_by'] ?? 'create_date';
        $sort = $criteria['sort'] ?? 'DESC';
        $qb->orderBy("ro.{$orderBy}", $sort);

        return $qb->getQuery()->getResult();
    }

    /**
     * 次の注文番号を生成
     *
     * @param string $prefix プレフィックス
     * @return string
     */
    public function generateNextOrderNo($prefix = 'R')
    {
        $today = new \DateTime();
        $datePrefix = $prefix . $today->format('Ymd');
        
        // 今日作成された最新の注文番号を取得
        $lastOrder = $this->createQueryBuilder('ro')
            ->where('ro.order_no LIKE :prefix')
            ->setParameter('prefix', $datePrefix . '%')
            ->orderBy('ro.order_no', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastOrder) {
            // 既存の番号から連番を抽出
            $lastOrderNo = $lastOrder->getOrderNo();
            $sequence = (int) substr($lastOrderNo, strlen($datePrefix));
            $nextSequence = $sequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $datePrefix . sprintf('%04d', $nextSequence);
    }

    /**
     * 売上データを取得（期間別） (MySQL対応版)
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param string $groupBy グループ化（day, week, month）
     * @return array
     */
    public function getSalesData(\DateTime $startDate, \DateTime $endDate, $groupBy = 'day')
    {
        $dateFormat = match ($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $sql = "
            SELECT 
                DATE_FORMAT(ro.create_date, '{$dateFormat}') as period,
                COUNT(ro.id) as order_count,
                SUM(ro.total_amount) as total_amount,
                AVG(ro.total_amount) as avg_amount,
                SUM(ro.quantity) as total_quantity
            FROM plg_rental_order ro
            WHERE ro.create_date >= :startDate
              AND ro.create_date <= :endDate
              AND ro.status != 0  -- 仮注文以外
            GROUP BY DATE_FORMAT(ro.create_date, '{$dateFormat}')
            ORDER BY period ASC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * レンタル期間分析を取得 (MySQL対応版)
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getRentalPeriodAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1 <= 3 THEN '1-3日'
                    WHEN DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1 <= 7 THEN '4-7日'
                    WHEN DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1 <= 14 THEN '8-14日'
                    WHEN DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1 <= 30 THEN '15-30日'
                    ELSE '31日以上'
                END as period_range,
                COUNT(ro.id) as order_count,
                SUM(ro.total_amount) as total_amount,
                AVG(ro.total_amount) as avg_amount,
                AVG(DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1) as avg_days
            FROM plg_rental_order ro
            WHERE ro.create_date >= :startDate
              AND ro.create_date <= :endDate
              AND ro.status != 0
            GROUP BY 
                CASE 
                    WHEN DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1 <= 3 THEN '1-3日'
                    WHEN DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1 <= 7 THEN '4-7日'
                    WHEN DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1 <= 14 THEN '8-14日'
                    WHEN DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1 <= 30 THEN '15-30日'
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
     * 収益性分析を取得 (MySQL対応版)
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getProfitabilityAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                rp.id as rental_product_id,
                p.name as product_name,
                COUNT(ro.id) as order_count,
                SUM(ro.total_amount) as total_revenue,
                AVG(ro.total_amount) as avg_revenue_per_order,
                SUM(ro.quantity) as total_quantity,
                AVG(DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1) as avg_rental_days,
                SUM(ro.total_amount) / SUM(DATEDIFF(ro.rental_end_date, ro.rental_start_date) + 1) as revenue_per_day,
                COUNT(CASE WHEN ro.status = 4 THEN 1 END) as returned_count,
                COUNT(CASE WHEN ro.status = 5 THEN 1 END) as overdue_count
            FROM plg_rental_order ro
            INNER JOIN plg_rental_product rp ON ro.rental_product_id = rp.id
            INNER JOIN dtb_product p ON rp.product_id = p.id
            WHERE ro.create_date >= :startDate
              AND ro.create_date <= :endDate
              AND ro.status != 0
            GROUP BY rp.id, p.name
            ORDER BY total_revenue DESC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }
}