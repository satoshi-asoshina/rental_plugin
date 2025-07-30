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
use Plugin\Rental\Entity\RentalPayment;
use Plugin\Rental\Entity\RentalOrder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * レンタル決済データアクセス Repository (MySQL対応版)
 */
class RentalPaymentRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RentalPayment::class);
    }

    /**
     * レンタル注文IDで決済情報を取得
     *
     * @param RentalOrder $rentalOrder レンタル注文エンティティ
     * @return RentalPayment[]
     */
    public function findByRentalOrder(RentalOrder $rentalOrder)
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.RentalOrder = :rentalOrder')
            ->setParameter('rentalOrder', $rentalOrder)
            ->orderBy('rp.create_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * トランザクションIDで決済情報を取得
     *
     * @param string $transactionId トランザクションID
     * @return RentalPayment|null
     */
    public function findByTransactionId($transactionId)
    {
        return $this->findOneBy(['transaction_id' => $transactionId]);
    }

    /**
     * 決済ステータス別の決済を取得
     *
     * @param int $status 決済ステータス
     * @return RentalPayment[]
     */
    public function findByStatus($status)
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.payment_status = :status')
            ->setParameter('status', $status)
            ->orderBy('rp.create_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 決済方法別の決済を取得
     *
     * @param string $method 決済方法
     * @return RentalPayment[]
     */
    public function findByPaymentMethod($method)
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.payment_method = :method')
            ->setParameter('method', $method)
            ->orderBy('rp.create_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 期間別の決済統計を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getPaymentStatistics(\DateTime $startDate, \DateTime $endDate)
    {
        $qb = $this->createQueryBuilder('rp')
            ->select('
                rp.payment_method,
                rp.payment_status,
                COUNT(rp.id) as payment_count,
                SUM(rp.payment_amount) as total_amount
            ')
            ->where('rp.create_date >= :startDate')
            ->andWhere('rp.create_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('rp.payment_method, rp.payment_status')
            ->orderBy('rp.payment_method, rp.payment_status');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 成功した決済の合計金額を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return float
     */
    public function getSuccessfulPaymentTotal(\DateTime $startDate, \DateTime $endDate)
    {
        $result = $this->createQueryBuilder('rp')
            ->select('SUM(rp.payment_amount)')
            ->where('rp.payment_status = :status')
            ->andWhere('rp.create_date >= :startDate')
            ->andWhere('rp.create_date <= :endDate')
            ->setParameter('status', 1) // 成功
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0.0;
    }

    /**
     * 失敗した決済を取得
     *
     * @param int $limit 取得件数
     * @return RentalPayment[]
     */
    public function findFailedPayments($limit = 50)
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.payment_status IN (:statuses)')
            ->setParameter('statuses', [2, 3]) // 失敗、エラー
            ->orderBy('rp.create_date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 処理中の決済を取得
     *
     * @param int $timeoutMinutes タイムアウト分数
     * @return RentalPayment[]
     */
    public function findPendingPayments($timeoutMinutes = 30)
    {
        $timeoutDate = new \DateTime();
        $timeoutDate->sub(new \DateInterval("PT{$timeoutMinutes}M"));

        return $this->createQueryBuilder('rp')
            ->where('rp.payment_status = :status')
            ->andWhere('rp.create_date < :timeoutDate')
            ->setParameter('status', 0) // 処理中
            ->setParameter('timeoutDate', $timeoutDate)
            ->orderBy('rp.create_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 月別売上統計を取得 (MySQL対応版)
     *
     * @param int $months 遡る月数
     * @return array
     */
    public function getMonthlySales($months = 12)
    {
        $endDate = new \DateTime('last day of this month 23:59:59');
        $startDate = clone $endDate;
        $startDate->sub(new \DateInterval("P{$months}M"))->modify('first day of this month 00:00:00');

        $sql = "
            SELECT 
                DATE_FORMAT(rp.create_date, '%Y-%m') as month,
                COUNT(rp.id) as payment_count,
                SUM(rp.payment_amount) as total_amount,
                AVG(rp.payment_amount) as avg_amount
            FROM plg_rental_payment rp
            WHERE rp.payment_status = 1
              AND rp.create_date >= :startDate
              AND rp.create_date <= :endDate
            GROUP BY DATE_FORMAT(rp.create_date, '%Y-%m')
            ORDER BY month ASC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * 決済方法別統計を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getPaymentMethodStatistics(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('rp')
            ->select('
                rp.payment_method,
                COUNT(rp.id) as payment_count,
                SUM(rp.payment_amount) as total_amount,
                AVG(rp.payment_amount) as avg_amount,
                SUM(CASE WHEN rp.payment_status = 1 THEN 1 ELSE 0 END) as success_count,
                SUM(CASE WHEN rp.payment_status IN (2, 3) THEN 1 ELSE 0 END) as failed_count
            ')
            ->where('rp.create_date >= :startDate')
            ->andWhere('rp.create_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('rp.payment_method')
            ->orderBy('total_amount', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * 返金処理を記録
     *
     * @param RentalPayment $originalPayment 元の決済
     * @param float $refundAmount 返金金額
     * @param string $reason 返金理由
     * @return RentalPayment
     */
    public function createRefund(RentalPayment $originalPayment, $refundAmount, $reason = '')
    {
        $refund = new RentalPayment();
        $refund->setRentalOrder($originalPayment->getRentalOrder());
        $refund->setPaymentMethod($originalPayment->getPaymentMethod());
        $refund->setPaymentAmount(-$refundAmount); // マイナス値で返金
        $refund->setPaymentStatus(1); // 成功
        $refund->setTransactionId('REFUND_' . $originalPayment->getTransactionId() . '_' . time());
        $refund->setPaymentDate(new \DateTime());
        $refund->setNotes($reason);

        $this->_em->persist($refund);
        $this->_em->flush();

        return $refund;
    }

    /**
     * 延滞料金を記録
     *
     * @param RentalOrder $rentalOrder レンタル注文
     * @param float $feeAmount 延滞料金
     * @param int $overdueDays 延滞日数
     * @return RentalPayment
     */
    public function createOverdueFee(RentalOrder $rentalOrder, $feeAmount, $overdueDays)
    {
        $payment = new RentalPayment();
        $payment->setRentalOrder($rentalOrder);
        $payment->setPaymentMethod('overdue_fee');
        $payment->setPaymentAmount($feeAmount);
        $payment->setPaymentStatus(0); // 処理中（未請求）
        $payment->setTransactionId('OVERDUE_' . $rentalOrder->getOrderNo() . '_' . time());
        $payment->setNotes("延滞料金 ({$overdueDays}日)");

        $this->_em->persist($payment);
        $this->_em->flush();

        return $payment;
    }

    /**
     * 注文の決済合計を取得
     *
     * @param RentalOrder $rentalOrder レンタル注文
     * @return float
     */
    public function getTotalPaymentAmount(RentalOrder $rentalOrder)
    {
        $result = $this->createQueryBuilder('rp')
            ->select('SUM(rp.payment_amount)')
            ->where('rp.RentalOrder = :rentalOrder')
            ->andWhere('rp.payment_status = :status')
            ->setParameter('rentalOrder', $rentalOrder)
            ->setParameter('status', 1) // 成功のみ
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0.0;
    }

    /**
     * 決済ログを検索
     *
     * @param array $criteria 検索条件
     * @return RentalPayment[]
     */
    public function searchPayments(array $criteria)
    {
        $qb = $this->createQueryBuilder('rp')
            ->leftJoin('rp.RentalOrder', 'ro')
            ->leftJoin('ro.Customer', 'c');

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

        // 決済方法
        if (!empty($criteria['payment_method'])) {
            $qb->andWhere('rp.payment_method = :paymentMethod')
               ->setParameter('paymentMethod', $criteria['payment_method']);
        }

        // 決済ステータス
        if (isset($criteria['payment_status'])) {
            $qb->andWhere('rp.payment_status = :paymentStatus')
               ->setParameter('paymentStatus', $criteria['payment_status']);
        }

        // 金額範囲
        if (!empty($criteria['min_amount'])) {
            $qb->andWhere('rp.payment_amount >= :minAmount')
               ->setParameter('minAmount', $criteria['min_amount']);
        }

        if (!empty($criteria['max_amount'])) {
            $qb->andWhere('rp.payment_amount <= :maxAmount')
               ->setParameter('maxAmount', $criteria['max_amount']);
        }

        // 期間
        if (!empty($criteria['start_date'])) {
            $qb->andWhere('rp.create_date >= :startDate')
               ->setParameter('startDate', $criteria['start_date']);
        }

        if (!empty($criteria['end_date'])) {
            $qb->andWhere('rp.create_date <= :endDate')
               ->setParameter('endDate', $criteria['end_date']);
        }

        // 並び順
        $orderBy = $criteria['order_by'] ?? 'create_date';
        $sort = $criteria['sort'] ?? 'DESC';
        $qb->orderBy("rp.{$orderBy}", $sort);

        return $qb->getQuery()->getResult();
    }

    /**
     * 決済成功率を取得 (MySQL対応版)
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param string $groupBy グループ化（day, week, month）
     * @return array
     */
    public function getSuccessRate(\DateTime $startDate, \DateTime $endDate, $groupBy = 'day')
    {
        $dateFormat = match ($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $sql = "
            SELECT 
                DATE_FORMAT(rp.create_date, '{$dateFormat}') as period,
                COUNT(rp.id) as total_payments,
                SUM(CASE WHEN rp.payment_status = 1 THEN 1 ELSE 0 END) as successful_payments,
                ROUND(
                    (SUM(CASE WHEN rp.payment_status = 1 THEN 1 ELSE 0 END) / COUNT(rp.id)) * 100, 
                    2
                ) as success_rate
            FROM plg_rental_payment rp
            WHERE rp.create_date >= :startDate
              AND rp.create_date <= :endDate
            GROUP BY DATE_FORMAT(rp.create_date, '{$dateFormat}')
            ORDER BY period ASC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * 決済エラー分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getPaymentErrorAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $sql = "
            SELECT 
                rp.payment_method,
                rp.payment_status,
                COUNT(rp.id) as error_count,
                GROUP_CONCAT(DISTINCT rp.notes ORDER BY rp.create_date DESC SEPARATOR '; ') as error_messages,
                AVG(rp.payment_amount) as avg_failed_amount
            FROM plg_rental_payment rp
            WHERE rp.payment_status IN (2, 3)  -- 失敗、エラー
              AND rp.create_date >= :startDate
              AND rp.create_date <= :endDate
            GROUP BY rp.payment_method, rp.payment_status
            ORDER BY error_count DESC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * リアルタイム決済監視データを取得
     *
     * @param int $minutes 分数
     * @return array
     */
    public function getRealTimePaymentMonitoring($minutes = 60)
    {
        $startTime = new \DateTime();
        $startTime->sub(new \DateInterval("PT{$minutes}M"));

        $sql = "
            SELECT 
                DATE_FORMAT(rp.create_date, '%Y-%m-%d %H:%i:00') as time_period,
                COUNT(rp.id) as total_payments,
                SUM(CASE WHEN rp.payment_status = 1 THEN 1 ELSE 0 END) as successful_payments,
                SUM(CASE WHEN rp.payment_status IN (2, 3) THEN 1 ELSE 0 END) as failed_payments,
                SUM(CASE WHEN rp.payment_status = 0 THEN 1 ELSE 0 END) as pending_payments,
                SUM(rp.payment_amount) as total_amount
            FROM plg_rental_payment rp
            WHERE rp.create_date >= :startTime
            GROUP BY DATE_FORMAT(rp.create_date, '%Y-%m-%d %H:%i:00')
            ORDER BY time_period DESC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startTime', $startTime->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }
}