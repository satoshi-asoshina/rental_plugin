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
use Plugin\Rental\Entity\RentalLog;
use Plugin\Rental\Entity\RentalOrder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * レンタルログデータアクセス Repository (MySQL対応版)
 */
class RentalLogRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RentalLog::class);
    }

    /**
     * レンタル注文に関連するログを取得
     *
     * @param RentalOrder $rentalOrder レンタル注文エンティティ
     * @return RentalLog[]
     */
    public function findByRentalOrder(RentalOrder $rentalOrder)
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.rental_order_id = :rentalOrderId')
            ->setParameter('rentalOrderId', $rentalOrder->getId())
            ->orderBy('rl.create_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ログレベル別のログを取得
     *
     * @param string $level ログレベル（info, warning, error）
     * @param int $limit 取得件数
     * @return RentalLog[]
     */
    public function findByLevel($level, $limit = 100)
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.log_level = :level')
            ->setParameter('level', $level)
            ->orderBy('rl.create_date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * アクション別のログを取得
     *
     * @param string $action アクション名
     * @param int $limit 取得件数
     * @return RentalLog[]
     */
    public function findByAction($action, $limit = 100)
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.action = :action')
            ->setParameter('action', $action)
            ->orderBy('rl.create_date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 顧客のアクションログを取得
     *
     * @param Customer $customer 顧客エンティティ
     * @param int $limit 取得件数
     * @return RentalLog[]
     */
    public function findByCustomer(Customer $customer, $limit = 50)
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.customer_id = :customerId')
            ->setParameter('customerId', $customer->getId())
            ->orderBy('rl.create_date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 期間別のログを取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param string|null $level ログレベル
     * @return RentalLog[]
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, $level = null)
    {
        $qb = $this->createQueryBuilder('rl')
            ->where('rl.create_date >= :startDate')
            ->andWhere('rl.create_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($level !== null) {
            $qb->andWhere('rl.log_level = :level')
               ->setParameter('level', $level);
        }

        return $qb->orderBy('rl.create_date', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * エラーログを取得
     *
     * @param int $limit 取得件数
     * @return RentalLog[]
     */
    public function findErrors($limit = 100)
    {
        return $this->findByLevel('error', $limit);
    }

    /**
     * 警告ログを取得
     *
     * @param int $limit 取得件数
     * @return RentalLog[]
     */
    public function findWarnings($limit = 100)
    {
        return $this->findByLevel('warning', $limit);
    }

    /**
     * システム管理ログを記録
     *
     * @param string $action アクション名
     * @param string $message メッセージ
     * @param string $level ログレベル
     * @param array $data 追加データ
     * @param Customer|null $customer 顧客エンティティ
     * @param RentalOrder|null $rentalOrder レンタル注文エンティティ
     */
    public function log($action, $message, $level = 'info', array $data = [], Customer $customer = null, RentalOrder $rentalOrder = null)
    {
        $log = new RentalLog();
        $log->setAction($action);
        $log->setMessage($message);
        $log->setLogLevel($level);
        $log->setLogData(json_encode($data));
        
        if ($customer) {
            $log->setCustomerId($customer->getId());
        }
        
        if ($rentalOrder) {
            $log->setRentalOrderId($rentalOrder->getId());
        }

        $this->_em->persist($log);
        $this->_em->flush();
    }

    /**
     * 注文作成ログを記録
     *
     * @param RentalOrder $rentalOrder レンタル注文エンティティ
     * @param Customer|null $customer 顧客エンティティ
     */
    public function logOrderCreated(RentalOrder $rentalOrder, Customer $customer = null)
    {
        $data = [
            'order_no' => $rentalOrder->getOrderNo(),
            'total_amount' => $rentalOrder->getTotalAmount(),
            'rental_start_date' => $rentalOrder->getRentalStartDate()->format('Y-m-d'),
            'rental_end_date' => $rentalOrder->getRentalEndDate()->format('Y-m-d'),
        ];

        $this->log(
            'order_created',
            'レンタル注文が作成されました: ' . $rentalOrder->getOrderNo(),
            'info',
            $data,
            $customer,
            $rentalOrder
        );
    }

    /**
     * 注文キャンセルログを記録
     *
     * @param RentalOrder $rentalOrder レンタル注文エンティティ
     * @param string $reason キャンセル理由
     * @param Customer|null $customer 顧客エンティティ
     */
    public function logOrderCanceled(RentalOrder $rentalOrder, $reason = '', Customer $customer = null)
    {
        $data = [
            'order_no' => $rentalOrder->getOrderNo(),
            'cancel_reason' => $reason,
        ];

        $this->log(
            'order_canceled',
            'レンタル注文がキャンセルされました: ' . $rentalOrder->getOrderNo(),
            'warning',
            $data,
            $customer,
            $rentalOrder
        );
    }

    /**
     * 返却ログを記録
     *
     * @param RentalOrder $rentalOrder レンタル注文エンティティ
     * @param \DateTime $returnDate 返却日
     * @param string $condition 返却状態
     * @param Customer|null $customer 顧客エンティティ
     */
    public function logItemReturned(RentalOrder $rentalOrder, \DateTime $returnDate, $condition = '', Customer $customer = null)
    {
        $data = [
            'order_no' => $rentalOrder->getOrderNo(),
            'return_date' => $returnDate->format('Y-m-d H:i:s'),
            'condition' => $condition,
            'is_overdue' => $returnDate > $rentalOrder->getRentalEndDate(),
        ];

        $level = $data['is_overdue'] ? 'warning' : 'info';
        $message = $data['is_overdue'] ? 
            'レンタル商品が延滞返却されました: ' . $rentalOrder->getOrderNo() :
            'レンタル商品が返却されました: ' . $rentalOrder->getOrderNo();

        $this->log(
            'item_returned',
            $message,
            $level,
            $data,
            $customer,
            $rentalOrder
        );
    }

    /**
     * 決済ログを記録
     *
     * @param RentalOrder $rentalOrder レンタル注文エンティティ
     * @param string $paymentMethod 決済方法
     * @param float $amount 金額
     * @param bool $success 成功フラグ
     * @param string $transactionId トランザクションID
     * @param Customer|null $customer 顧客エンティティ
     */
    public function logPayment(RentalOrder $rentalOrder, $paymentMethod, $amount, $success, $transactionId = '', Customer $customer = null)
    {
        $data = [
            'order_no' => $rentalOrder->getOrderNo(),
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'transaction_id' => $transactionId,
        ];

        $level = $success ? 'info' : 'error';
        $message = $success ? 
            '決済が完了しました: ' . $rentalOrder->getOrderNo() :
            '決済が失敗しました: ' . $rentalOrder->getOrderNo();

        $this->log(
            $success ? 'payment_success' : 'payment_failed',
            $message,
            $level,
            $data,
            $customer,
            $rentalOrder
        );
    }

    /**
     * システムエラーログを記録
     *
     * @param string $message エラーメッセージ
     * @param \Exception $exception 例外オブジェクト
     * @param array $context コンテキスト情報
     */
    public function logError($message, \Exception $exception, array $context = [])
    {
        $data = array_merge($context, [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->log(
            'system_error',
            $message,
            'error',
            $data
        );
    }

    /**
     * ログ統計を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getLogStatistics(\DateTime $startDate, \DateTime $endDate)
    {
        $qb = $this->createQueryBuilder('rl')
            ->select('rl.log_level, rl.action, COUNT(rl.id) as count')
            ->where('rl.create_date >= :startDate')
            ->andWhere('rl.create_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('rl.log_level, rl.action')
            ->orderBy('rl.log_level, count', 'DESC');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 最新のエラーログを取得
     *
     * @param int $hours 何時間前まで
     * @return RentalLog[]
     */
    public function getRecentErrors($hours = 24)
    {
        $startDate = new \DateTime();
        $startDate->sub(new \DateInterval("PT{$hours}H"));

        return $this->createQueryBuilder('rl')
            ->where('rl.log_level = :level')
            ->andWhere('rl.create_date >= :startDate')
            ->setParameter('level', 'error')
            ->setParameter('startDate', $startDate)
            ->orderBy('rl.create_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ログを検索
     *
     * @param array $criteria 検索条件
     * @return RentalLog[]
     */
    public function searchLogs(array $criteria)
    {
        $qb = $this->createQueryBuilder('rl');

        // ログレベル
        if (!empty($criteria['log_level'])) {
            $qb->andWhere('rl.log_level = :logLevel')
               ->setParameter('logLevel', $criteria['log_level']);
        }

        // アクション
        if (!empty($criteria['action'])) {
            $qb->andWhere('rl.action = :action')
               ->setParameter('action', $criteria['action']);
        }

        // メッセージ検索
        if (!empty($criteria['message'])) {
            $qb->andWhere('rl.message LIKE :message')
               ->setParameter('message', '%' . $criteria['message'] . '%');
        }

        // 顧客ID
        if (!empty($criteria['customer_id'])) {
            $qb->andWhere('rl.customer_id = :customerId')
               ->setParameter('customerId', $criteria['customer_id']);
        }

        // 注文ID
        if (!empty($criteria['rental_order_id'])) {
            $qb->andWhere('rl.rental_order_id = :rentalOrderId')
               ->setParameter('rentalOrderId', $criteria['rental_order_id']);
        }

        // 期間
        if (!empty($criteria['start_date'])) {
            $qb->andWhere('rl.create_date >= :startDate')
               ->setParameter('startDate', $criteria['start_date']);
        }

        if (!empty($criteria['end_date'])) {
            $qb->andWhere('rl.create_date <= :endDate')
               ->setParameter('endDate', $criteria['end_date']);
        }

        // 並び順
        $orderBy = $criteria['order_by'] ?? 'create_date';
        $sort = $criteria['sort'] ?? 'DESC';
        $qb->orderBy("rl.{$orderBy}", $sort);

        // 件数制限
        if (!empty($criteria['limit'])) {
            $qb->setMaxResults($criteria['limit']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 古いログを削除
     *
     * @param int $days 保持日数
     * @return int 削除件数
     */
    public function deleteOldLogs($days = 90)
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->sub(new \DateInterval("P{$days}D"));

        $qb = $this->createQueryBuilder('rl')
            ->delete()
            ->where('rl.create_date < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate);

        return $qb->getQuery()->execute();
    }

    /**
     * エラー頻度分析を取得 (MySQL対応版)
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param string $groupBy グループ化（hour, day, week）
     * @return array
     */
    public function getErrorFrequencyAnalysis(\DateTime $startDate, \DateTime $endDate, $groupBy = 'day')
    {
        $dateFormat = match ($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'week' => '%Y-%u',
            default => '%Y-%m-%d'
        };

        $sql = "
            SELECT 
                DATE_FORMAT(rl.create_date, '{$dateFormat}') as period,
                COUNT(rl.id) as total_logs,
                COUNT(CASE WHEN rl.log_level = 'error' THEN 1 END) as error_count,
                COUNT(CASE WHEN rl.log_level = 'warning' THEN 1 END) as warning_count,
                COUNT(CASE WHEN rl.log_level = 'info' THEN 1 END) as info_count,
                ROUND(
                    (COUNT(CASE WHEN rl.log_level = 'error' THEN 1 END) / COUNT(rl.id)) * 100, 
                    2
                ) as error_rate
            FROM plg_rental_log rl
            WHERE rl.create_date >= :startDate
              AND rl.create_date <= :endDate
            GROUP BY DATE_FORMAT(rl.create_date, '{$dateFormat}')
            ORDER BY period ASC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * 人気アクション分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param int $limit 取得件数
     * @return array
     */
    public function getPopularActionAnalysis(\DateTime $startDate, \DateTime $endDate, $limit = 20)
    {
        return $this->createQueryBuilder('rl')
            ->select('
                rl.action,
                COUNT(rl.id) as action_count,
                COUNT(CASE WHEN rl.log_level = "error" THEN 1 END) as error_count,
                COUNT(CASE WHEN rl.log_level = "warning" THEN 1 END) as warning_count,
                COUNT(DISTINCT rl.customer_id) as unique_customers
            ')
            ->where('rl.create_date >= :startDate')
            ->andWhere('rl.create_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('rl.action')
            ->orderBy('action_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * システム健全性チェック
     *
     * @param int $hours チェック時間（時間）
     * @return array
     */
    public function getSystemHealthCheck($hours = 24)
    {
        $startTime = new \DateTime();
        $startTime->sub(new \DateInterval("PT{$hours}H"));

        $sql = "
            SELECT 
                'エラー率' as metric_name,
                ROUND(
                    (COUNT(CASE WHEN rl.log_level = 'error' THEN 1 END) / COUNT(rl.id)) * 100, 
                    2
                ) as metric_value,
                CASE 
                    WHEN (COUNT(CASE WHEN rl.log_level = 'error' THEN 1 END) / COUNT(rl.id)) * 100 > 5 THEN 'CRITICAL'
                    WHEN (COUNT(CASE WHEN rl.log_level = 'error' THEN 1 END) / COUNT(rl.id)) * 100 > 2 THEN 'WARNING'
                    ELSE 'OK'
                END as status
            FROM plg_rental_log rl
            WHERE rl.create_date >= :startTime
            
            UNION ALL
            
            SELECT 
                '警告率' as metric_name,
                ROUND(
                    (COUNT(CASE WHEN rl.log_level = 'warning' THEN 1 END) / COUNT(rl.id)) * 100, 
                    2
                ) as metric_value,
                CASE 
                    WHEN (COUNT(CASE WHEN rl.log_level = 'warning' THEN 1 END) / COUNT(rl.id)) * 100 > 10 THEN 'WARNING'
                    ELSE 'OK'
                END as status
            FROM plg_rental_log rl
            WHERE rl.create_date >= :startTime
            
            UNION ALL
            
            SELECT 
                '決済エラー率' as metric_name,
                ROUND(
                    (COUNT(CASE WHEN rl.action = 'payment_failed' THEN 1 END) / 
                     NULLIF(COUNT(CASE WHEN rl.action IN ('payment_success', 'payment_failed') THEN 1 END), 0)) * 100, 
                    2
                ) as metric_value,
                CASE 
                    WHEN (COUNT(CASE WHEN rl.action = 'payment_failed' THEN 1 END) / 
                          NULLIF(COUNT(CASE WHEN rl.action IN ('payment_success', 'payment_failed') THEN 1 END), 0)) * 100 > 5 THEN 'CRITICAL'
                    WHEN (COUNT(CASE WHEN rl.action = 'payment_failed' THEN 1 END) / 
                          NULLIF(COUNT(CASE WHEN rl.action IN ('payment_success', 'payment_failed') THEN 1 END), 0)) * 100 > 2 THEN 'WARNING'
                    ELSE 'OK'
                END as status
            FROM plg_rental_log rl
            WHERE rl.create_date >= :startTime
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startTime', $startTime->format('Y-m-d H:i:s'));

        return $stmt->executeQuery()->fetchAllAssociative();
    }
}