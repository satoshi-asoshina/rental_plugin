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
use Plugin\Rental\Entity\RentalInventory;
use Plugin\Rental\Entity\RentalProduct;
use Plugin\Rental\Entity\RentalOrder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * レンタル在庫データアクセス Repository (MySQL対応版)
 */
class RentalInventoryRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RentalInventory::class);
    }

    /**
     * レンタル商品IDで在庫を取得
     *
     * @param RentalProduct $rentalProduct レンタル商品エンティティ
     * @return RentalInventory[]
     */
    public function findByRentalProduct(RentalProduct $rentalProduct)
    {
        return $this->createQueryBuilder('ri')
            ->where('ri.RentalProduct = :rentalProduct')
            ->setParameter('rentalProduct', $rentalProduct)
            ->orderBy('ri.create_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ステータス別の在庫を取得
     *
     * @param int $status ステータス（1:利用可能, 2:レンタル中, 3:返却処理中, 4:メンテナンス中）
     * @return RentalInventory[]
     */
    public function findByStatus($status)
    {
        return $this->createQueryBuilder('ri')
            ->where('ri.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ri.create_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * レンタル注文で在庫を取得
     *
     * @param RentalOrder $rentalOrder レンタル注文エンティティ
     * @return RentalInventory[]
     */
    public function findByRentalOrder(RentalOrder $rentalOrder)
    {
        return $this->createQueryBuilder('ri')
            ->where('ri.RentalOrder = :rentalOrder')
            ->setParameter('rentalOrder', $rentalOrder)
            ->orderBy('ri.create_date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 指定期間で利用可能な在庫数を取得
     *
     * @param RentalProduct $rentalProduct レンタル商品エンティティ
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return int
     */
    public function getAvailableStock(RentalProduct $rentalProduct, \DateTime $startDate, \DateTime $endDate)
    {
        // 無制限在庫の場合
        if ($rentalProduct->getStockUnlimited()) {
            return PHP_INT_MAX;
        }

        // 総在庫数
        $totalStock = $this->createQueryBuilder('ri')
            ->select('COUNT(ri.id)')
            ->where('ri.RentalProduct = :rentalProduct')
            ->andWhere('ri.status != :maintenance')
            ->setParameter('rentalProduct', $rentalProduct)
            ->setParameter('maintenance', 4) // メンテナンス中を除外
            ->getQuery()
            ->getSingleScalarResult();

        // 指定期間にレンタル中の在庫数
        $rentedStock = $this->createQueryBuilder('ri')
            ->select('COUNT(ri.id)')
            ->innerJoin('ri.RentalOrder', 'ro')
            ->where('ri.RentalProduct = :rentalProduct')
            ->andWhere('ri.status = :rented')
            ->andWhere('(ro.rental_start_date <= :endDate AND ro.rental_end_date >= :startDate)')
            ->setParameter('rentalProduct', $rentalProduct)
            ->setParameter('rented', 2) // レンタル中
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return max(0, $totalStock - $rentedStock);
    }

    /**
     * 利用可能な在庫を取得
     *
     * @param RentalProduct $rentalProduct レンタル商品エンティティ
     * @param int $quantity 必要数量
     * @return RentalInventory[]
     */
    public function findAvailableInventory(RentalProduct $rentalProduct, $quantity = 1)
    {
        return $this->createQueryBuilder('ri')
            ->where('ri.RentalProduct = :rentalProduct')
            ->andWhere('ri.status = :available')
            ->setParameter('rentalProduct', $rentalProduct)
            ->setParameter('available', 1) // 利用可能
            ->orderBy('ri.last_returned_date', 'ASC')
            ->setMaxResults($quantity)
            ->getQuery()
            ->getResult();
    }

    /**
     * レンタル中の在庫を取得
     *
     * @param \DateTime|null $overdueDate 延滞判定日（この日以前に返却予定のもの）
     * @return RentalInventory[]
     */
    public function findRentedInventory(\DateTime $overdueDate = null)
    {
        $qb = $this->createQueryBuilder('ri')
            ->innerJoin('ri.RentalOrder', 'ro')
            ->where('ri.status = :rented')
            ->setParameter('rented', 2); // レンタル中

        if ($overdueDate) {
            $qb->andWhere('ro.rental_end_date < :overdueDate')
               ->setParameter('overdueDate', $overdueDate);
        }

        return $qb->orderBy('ro.rental_end_date', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * 在庫をレンタル中にする
     *
     * @param RentalInventory[] $inventories 在庫エンティティ配列
     * @param RentalOrder $rentalOrder レンタル注文エンティティ
     */
    public function setRented(array $inventories, RentalOrder $rentalOrder)
    {
        foreach ($inventories as $inventory) {
            $inventory->setStatus(2); // レンタル中
            $inventory->setRentalOrder($rentalOrder);
            $this->_em->persist($inventory);
        }
        $this->_em->flush();
    }

    /**
     * 在庫を返却済みにする
     *
     * @param RentalInventory[] $inventories 在庫エンティティ配列
     */
    public function setReturned(array $inventories)
    {
        $returnDate = new \DateTime();
        
        foreach ($inventories as $inventory) {
            $inventory->setStatus(1); // 利用可能
            $inventory->setRentalOrder(null);
            $inventory->setLastReturnedDate($returnDate);
            $this->_em->persist($inventory);
        }
        $this->_em->flush();
    }

    /**
     * 在庫をメンテナンス中にする
     *
     * @param RentalInventory[] $inventories 在庫エンティティ配列
     * @param string $notes メンテナンス内容
     */
    public function setMaintenance(array $inventories, $notes = '')
    {
        foreach ($inventories as $inventory) {
            $inventory->setStatus(4); // メンテナンス中
            $inventory->setNotes($notes);
            $this->_em->persist($inventory);
        }
        $this->_em->flush();
    }

    /**
     * 在庫の使用履歴を取得
     *
     * @param RentalInventory $inventory 在庫エンティティ
     * @param int $limit 取得件数
     * @return array
     */
    public function getUsageHistory(RentalInventory $inventory, $limit = 10)
    {
        return $this->_em->createQueryBuilder()
            ->select('ro.order_no, ro.rental_start_date, ro.rental_end_date, c.name01, c.name02')
            ->from('Plugin\Rental\Entity\RentalOrder', 'ro')
            ->leftJoin('ro.Customer', 'c')
            ->where('ro.id IN (
                SELECT IDENTITY(ri.RentalOrder) 
                FROM Plugin\Rental\Entity\RentalInventory ri 
                WHERE ri = :inventory AND ri.RentalOrder IS NOT NULL
            )')
            ->setParameter('inventory', $inventory)
            ->orderBy('ro.rental_start_date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * 商品別在庫統計を取得
     *
     * @param RentalProduct $rentalProduct レンタル商品エンティティ
     * @return array
     */
    public function getInventoryStatistics(RentalProduct $rentalProduct)
    {
        $qb = $this->createQueryBuilder('ri')
            ->select('ri.status, COUNT(ri.id) as count')
            ->where('ri.RentalProduct = :rentalProduct')
            ->setParameter('rentalProduct', $rentalProduct)
            ->groupBy('ri.status');

        $results = $qb->getQuery()->getArrayResult();
        
        $statistics = [
            'available' => 0,    // 利用可能
            'rented' => 0,       // レンタル中
            'processing' => 0,   // 返却処理中
            'maintenance' => 0,  // メンテナンス中
            'total' => 0
        ];

        $statusMap = [
            1 => 'available',
            2 => 'rented',
            3 => 'processing',
            4 => 'maintenance'
        ];

        foreach ($results as $result) {
            $status = $statusMap[$result['status']] ?? 'other';
            $statistics[$status] = $result['count'];
            $statistics['total'] += $result['count'];
        }

        return $statistics;
    }

    /**
     * 期間別在庫稼働率を取得 (MySQL対応版)
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getUtilizationRate(\DateTime $startDate, \DateTime $endDate)
    {
        // レンタル中だった日数の合計を計算 (MySQL対応)
        $sql = "
            SELECT 
                rp.id as rental_product_id,
                p.name as product_name,
                COUNT(DISTINCT ri.id) as total_inventory,
                SUM(
                    CASE 
                        WHEN ro.rental_start_date <= :endDate AND ro.rental_end_date >= :startDate
                        THEN DATEDIFF(LEAST(ro.rental_end_date, :endDate), GREATEST(ro.rental_start_date, :startDate)) + 1
                        ELSE 0
                    END
                ) as rented_days
            FROM plg_rental_inventory ri
            LEFT JOIN plg_rental_order ro ON ri.rental_order_id = ro.id
            LEFT JOIN plg_rental_product rp ON ri.rental_product_id = rp.id
            LEFT JOIN dtb_product p ON rp.product_id = p.id
            WHERE ri.status != 4  -- メンテナンス中を除外
            GROUP BY rp.id, p.name
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d'));
        
        $results = $stmt->executeQuery()->fetchAllAssociative();
        
        $totalDays = $startDate->diff($endDate)->days + 1;
        
        foreach ($results as &$result) {
            $availableDays = $result['total_inventory'] * $totalDays;
            $result['utilization_rate'] = $availableDays > 0 ? 
                ($result['rented_days'] / $availableDays) * 100 : 0;
        }

        return $results;
    }

    /**
     * 在庫回転率を取得 (MySQL対応版)
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return float
     */
    public function getInventoryTurnoverRate(RentalProduct $rentalProduct, \DateTime $startDate, \DateTime $endDate)
    {
        // 期間中のレンタル回数
        $rentalCount = $this->createQueryBuilder('ri')
            ->select('COUNT(ri.id)')
            ->innerJoin('ri.RentalOrder', 'ro')
            ->where('ri.RentalProduct = :rentalProduct')
            ->andWhere('ro.rental_start_date >= :startDate')
            ->andWhere('ro.rental_start_date <= :endDate')
            ->andWhere('ri.status = :rented')
            ->setParameter('rentalProduct', $rentalProduct)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('rented', 2)
            ->getQuery()
            ->getSingleScalarResult();

        // 平均在庫数
        $avgInventory = $this->createQueryBuilder('ri')
            ->select('COUNT(ri.id)')
            ->where('ri.RentalProduct = :rentalProduct')
            ->andWhere('ri.status != :maintenance')
            ->setParameter('rentalProduct', $rentalProduct)
            ->setParameter('maintenance', 4)
            ->getQuery()
            ->getSingleScalarResult();

        return $avgInventory > 0 ? ($rentalCount / $avgInventory) : 0;
    }

    /**
     * 在庫メンテナンス履歴を取得
     *
     * @param RentalInventory $inventory 在庫エンティティ
     * @param int $limit 取得件数
     * @return array
     */
    public function getMaintenanceHistory(RentalInventory $inventory, $limit = 20)
    {
        $sql = "
            SELECT 
                rl.create_date,
                rl.message,
                rl.log_data,
                COALESCE(c.name01, 'システム') as operator_name
            FROM plg_rental_log rl
            LEFT JOIN dtb_customer c ON rl.customer_id = c.id
            WHERE JSON_EXTRACT(rl.log_data, '$.inventory_id') = :inventoryId
              AND rl.action IN ('maintenance_start', 'maintenance_end', 'inspection')
            ORDER BY rl.create_date DESC
            LIMIT :limit
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->bindValue('inventoryId', $inventory->getId());
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * 在庫アラートを取得
     *
     * @return array
     */
    public function getInventoryAlerts()
    {
        $sql = "
            SELECT 
                rp.id as rental_product_id,
                p.name as product_name,
                COUNT(CASE WHEN ri.status = 1 THEN 1 END) as available_count,
                COUNT(CASE WHEN ri.status = 2 THEN 1 END) as rented_count,
                COUNT(CASE WHEN ri.status = 4 THEN 1 END) as maintenance_count,
                COUNT(ri.id) as total_count,
                rp.min_stock_alert,
                CASE 
                    WHEN COUNT(CASE WHEN ri.status = 1 THEN 1 END) <= rp.min_stock_alert THEN 'LOW_STOCK'
                    WHEN COUNT(CASE WHEN ri.status = 4 THEN 1 END) / COUNT(ri.id) > 0.3 THEN 'HIGH_MAINTENANCE'
                    ELSE 'OK'
                END as alert_type
            FROM plg_rental_product rp
            INNER JOIN dtb_product p ON rp.product_id = p.id
            LEFT JOIN plg_rental_inventory ri ON rp.id = ri.rental_product_id
            WHERE rp.is_rental_enabled = 1
            GROUP BY rp.id, p.name, rp.min_stock_alert
            HAVING alert_type != 'OK'
            ORDER BY 
                CASE alert_type 
                    WHEN 'LOW_STOCK' THEN 1 
                    WHEN 'HIGH_MAINTENANCE' THEN 2 
                    ELSE 3 
                END,
                available_count ASC
        ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}