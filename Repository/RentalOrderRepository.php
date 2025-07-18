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
 * レンタル注文データアクセス Repository
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
     * 期間内の注文を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return RentalOrder[]
     */
    public function findByPeriod(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('ro')
            ->where('ro.rental_start_date <= :endDate')
            ->andWhere('ro.rental_end_date >= :startDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('ro.rental_start_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 延滞している注文を取得
     *
     * @return RentalOrder[]
     */
    public function findOverdueOrders()
    {
        return $this->createQueryBuilder('ro')
            ->where('ro.status IN (:activeStatuses)')
            ->andWhere('ro.rental_end_date < :now')
            ->andWhere('ro.actual_return_date IS NULL')
            ->setParameter('activeStatuses', [RentalOrder::STATUS_ACTIVE, RentalOrder::STATUS_RESERVED])
            ->setParameter('now', new \DateTime())
            ->orderBy('ro.rental_end_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 返却期限が近い注文を取得
     *
     * @param int $days 何日前から通知するか
     * @return RentalOrder[]
     */
    public function findUpcomingReturns($days = 3)
    {
        $fromDate = new \DateTime();
        $toDate = new \DateTime();
        $toDate->add(new \DateInterval('P' . $days . 'D'));

        return $this->createQueryBuilder('ro')
            ->where('ro.status = :status')
            ->andWhere('ro.rental_end_date BETWEEN :fromDate AND :toDate')
            ->andWhere('ro.actual_return_date IS NULL')
            ->setParameter('status', RentalOrder::STATUS_ACTIVE)
            ->setParameter('fromDate', $fromDate)
            ->setParameter('toDate', $toDate)
            ->orderBy('ro.rental_end_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 商品の予約重複をチェック
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param int|null $excludeOrderId 除外する注文ID
     * @return RentalOrder[]
     */
    public function findConflictingOrders(RentalProduct $rentalProduct, \DateTime $startDate, \DateTime $endDate, $excludeOrderId = null)
    {
        $qb = $this->createQueryBuilder('ro')
            ->where('ro.RentalProduct = :rentalProduct')
            ->andWhere('ro.status IN (:activeStatuses)')
            ->andWhere('ro.rental_start_date < :endDate')
            ->andWhere('ro.rental_end_date > :startDate')
            ->setParameter('rentalProduct', $rentalProduct)
            ->setParameter('activeStatuses', [
                RentalOrder::STATUS_RESERVED,
                RentalOrder::STATUS_ACTIVE
            ])
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($excludeOrderId !== null) {
            $qb->andWhere('ro.id != :excludeOrderId')
               ->setParameter('excludeOrderId', $excludeOrderId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 月次売上レポートデータを取得
     *
     * @param int $year 年
     * @param int|null $month 月（nullの場合は年間）
     * @return array
     */
    public function getMonthlyReport($year, $month = null)
    {
        $qb = $this->createQueryBuilder('ro')
            ->select('
                COUNT(ro.id) as order_count,
                SUM(ro.total_amount) as total_revenue,
                SUM(ro.deposit_amount) as total_deposit,
                SUM(ro.overdue_fee) as total_overdue_fee,
                AVG(ro.total_amount) as avg_order_amount
            ')
            ->where('YEAR(ro.create_date) = :year')
            ->setParameter('year', $year);

        if ($month !== null) {
            $qb->andWhere('MONTH(ro.create_date) = :month')
               ->setParameter('month', $month);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * 顧客別の注文統計を取得
     *
     * @param Customer $customer 顧客エンティティ
     * @return array
     */
    public function getCustomerStatistics(Customer $customer)
    {
        $qb = $this->createQueryBuilder('ro')
            ->select('
                COUNT(ro.id) as total_orders,
                SUM(ro.total_amount) as total_spent,
                MAX(ro.create_date) as last_order_date,
                COUNT(CASE WHEN ro.status = :completed THEN 1 END) as completed_orders,
                COUNT(CASE WHEN ro.status = :overdue THEN 1 END) as overdue_orders
            ')
            ->where('ro.Customer = :customer')
            ->setParameter('customer', $customer)
            ->setParameter('completed', RentalOrder::STATUS_RETURNED)
            ->setParameter('overdue', RentalOrder::STATUS_OVERDUE);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * 日別の注文数を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getDailyOrderCount(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('ro')
            ->select('DATE(ro.create_date) as date, COUNT(ro.id) as count')
            ->where('ro.create_date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('DATE(ro.create_date)')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 人気商品ランキングを取得
     *
     * @param int $limit 取得件数
     * @param \DateTime|null $startDate 開始日
     * @param \DateTime|null $endDate 終了日
     * @return array
     */
    public function getPopularProducts($limit = 10, \DateTime $startDate = null, \DateTime $endDate = null)
    {
        $qb = $this->createQueryBuilder('ro')
            ->select('
                rp.id as rental_product_id,
                p.name as product_name,
                COUNT(ro.id) as order_count,
                SUM(ro.quantity) as total_quantity,
                SUM(ro.total_amount) as total_revenue
            ')
            ->innerJoin('ro.RentalProduct', 'rp')
            ->innerJoin('rp.Product', 'p')
            ->groupBy('rp.id, p.name')
            ->orderBy('order_count', 'DESC')
            ->setMaxResults($limit);

        if ($startDate !== null) {
            $qb->andWhere('ro.create_date >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('ro.create_date <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 検索条件で注文を取得
     *
     * @param array $searchData 検索条件
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchData(array $searchData)
    {
        $qb = $this->createQueryBuilder('ro')
            ->innerJoin('ro.Customer', 'c')
            ->innerJoin('ro.RentalProduct', 'rp')
            ->innerJoin('rp.Product', 'p');

        // 注文番号
        if (!empty($searchData['order_no'])) {
            $qb->andWhere('ro.order_no LIKE :order_no')
               ->setParameter('order_no', '%' . $searchData['order_no'] . '%');
        }

        // 顧客名
        if (!empty($searchData['customer_name'])) {
            $qb->andWhere('c.name01 LIKE :customer_name OR c.name02 LIKE :customer_name')
               ->setParameter('customer_name', '%' . $searchData['customer_name'] . '%');
        }

        // 商品名
        if (!empty($searchData['product_name'])) {
            $qb->andWhere('p.name LIKE :product_name')
               ->setParameter('product_name', '%' . $searchData['product_name'] . '%');
        }

        // ステータス
        if (!empty($searchData['status'])) {
            $qb->andWhere('ro.status = :status')
               ->setParameter('status', $searchData['status']);
        }

        // 期間
        if (!empty($searchData['start_date'])) {
            $qb->andWhere('ro.create_date >= :start_date')
               ->setParameter('start_date', $searchData['start_date']);
        }

        if (!empty($searchData['end_date'])) {
            $qb->andWhere('ro.create_date <= :end_date')
               ->setParameter('end_date', $searchData['end_date']);
        }

        return $qb;
    }

    /**
     * 注文を一括更新
     *
     * @param array $orderIds 注文IDの配列
     * @param array $updateData 更新データ
     * @return int 更新件数
     */
    public function bulkUpdate(array $orderIds, array $updateData)
    {
        $qb = $this->createQueryBuilder('ro')
            ->update()
            ->where('ro.id IN (:orderIds)')
            ->setParameter('orderIds', $orderIds);

        foreach ($updateData as $field => $value) {
            $qb->set("ro.{$field}", ":{$field}")
               ->setParameter($field, $value);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * 注文をキャンセル
     *
     * @param RentalOrder $order 注文エンティティ
     * @param string $reason キャンセル理由
     * @return void
     */
    public function cancel(RentalOrder $order, $reason = '')
    {
        $order->setStatus(RentalOrder::STATUS_CANCELLED);
        $order->setAdminMemo($reason);
        
        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->flush();
    }

    /**
     * 注文を完了
     *
     * @param RentalOrder $order 注文エンティティ
     * @param \DateTime|null $returnDate 返却日
     * @return void
     */
    public function complete(RentalOrder $order, \DateTime $returnDate = null)
    {
        $order->setStatus(RentalOrder::STATUS_RETURNED);
        $order->setActualReturnDate($returnDate ?: new \DateTime());
        
        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->flush();
    }

    /**
     * 統計情報を取得
     *
     * @return array
     */
    public function getStatistics()
    {
        $qb = $this->createQueryBuilder('ro');
        
        $total = $qb->select('COUNT(ro.id)')
            ->getQuery()
            ->getSingleScalarResult();
            
        $active = $qb->select('COUNT(ro.id)')
            ->where('ro.status = :status')
            ->setParameter('status', RentalOrder::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
            
        $overdue = $qb->select('COUNT(ro.id)')
            ->where('ro.status = :status')
            ->setParameter('status', RentalOrder::STATUS_OVERDUE)
            ->getQuery()
            ->getSingleScalarResult();
            
        $completed = $qb->select('COUNT(ro.id)')
            ->where('ro.status = :status')
            ->setParameter('status', RentalOrder::STATUS_RETURNED)
            ->getQuery()
            ->getSingleScalarResult();
            
        return [
            'total' => $total,
            'active' => $active,
            'overdue' => $overdue,
            'completed' => $completed,
        ];
    }
}