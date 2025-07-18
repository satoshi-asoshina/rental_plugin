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
use Plugin\Rental\Entity\RentalInventory;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * レンタル在庫データアクセス Repository
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
     * 商品IDから在庫情報を取得
     *
     * @param int $productId 商品ID
     * @return RentalInventory|null
     */
    public function findByProductId($productId)
    {
        return $this->findOneBy(['Product' => $productId]);
    }

    /**
     * 商品エンティティから在庫情報を取得
     *
     * @param Product $product 商品エンティティ
     * @return RentalInventory|null
     */
    public function findByProduct(Product $product)
    {
        return $this->findOneBy(['Product' => $product]);
    }

    /**
     * 在庫情報を取得または作成
     *
     * @param Product $product 商品エンティティ
     * @return RentalInventory
     */
    public function findOrCreate(Product $product)
    {
        $inventory = $this->findByProduct($product);
        
        if (!$inventory) {
            $inventory = new RentalInventory();
            $inventory->setProduct($product);
            $inventory->setAvailableQuantity(0);
            $inventory->setReservedQuantity(0);
            $inventory->setRentedQuantity(0);
            $inventory->setMaintenanceQuantity(0);
            $inventory->setLastUpdated(new \DateTime());
            
            $this->getEntityManager()->persist($inventory);
            $this->getEntityManager()->flush();
        }
        
        return $inventory;
    }

    /**
     * 在庫切れの商品を取得
     *
     * @return RentalInventory[]
     */
    public function findOutOfStock()
    {
        return $this->createQueryBuilder('ri')
            ->where('ri.available_quantity <= ri.reserved_quantity + ri.rented_quantity')
            ->getQuery()
            ->getResult();
    }

    /**
     * 在庫が少ない商品を取得
     *
     * @param int $threshold 閾値
     * @return RentalInventory[]
     */
    public function findLowStock($threshold = 5)
    {
        return $this->createQueryBuilder('ri')
            ->where('ri.available_quantity - ri.reserved_quantity - ri.rented_quantity <= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * 実際の利用可能数量を取得
     *
     * @param Product $product 商品エンティティ
     * @return int
     */
    public function getActualAvailableQuantity(Product $product)
    {
        $inventory = $this->findByProduct($product);
        
        if (!$inventory) {
            return 0;
        }
        
        return max(0, $inventory->getAvailableQuantity() - $inventory->getReservedQuantity() - $inventory->getRentedQuantity());
    }

    /**
     * 在庫を予約
     *
     * @param Product $product 商品エンティティ
     * @param int $quantity 予約数量
     * @return bool
     */
    public function reserve(Product $product, $quantity)
    {
        $inventory = $this->findOrCreate($product);
        
        if ($this->getActualAvailableQuantity($product) < $quantity) {
            return false;
        }
        
        $inventory->setReservedQuantity($inventory->getReservedQuantity() + $quantity);
        $inventory->setLastUpdated(new \DateTime());
        
        $this->getEntityManager()->persist($inventory);
        $this->getEntityManager()->flush();
        
        return true;
    }

    /**
     * 予約を取消
     *
     * @param Product $product 商品エンティティ
     * @param int $quantity 取消数量
     * @return void
     */
    public function cancelReservation(Product $product, $quantity)
    {
        $inventory = $this->findByProduct($product);
        
        if ($inventory) {
            $inventory->setReservedQuantity(max(0, $inventory->getReservedQuantity() - $quantity));
            $inventory->setLastUpdated(new \DateTime());
            
            $this->getEntityManager()->persist($inventory);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 予約からレンタル中に移行
     *
     * @param Product $product 商品エンティティ
     * @param int $quantity 数量
     * @return void
     */
    public function activateRental(Product $product, $quantity)
    {
        $inventory = $this->findByProduct($product);
        
        if ($inventory) {
            $inventory->setReservedQuantity(max(0, $inventory->getReservedQuantity() - $quantity));
            $inventory->setRentedQuantity($inventory->getRentedQuantity() + $quantity);
            $inventory->setLastUpdated(new \DateTime());
            
            $this->getEntityManager()->persist($inventory);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * レンタル中から返却に移行
     *
     * @param Product $product 商品エンティティ
     * @param int $quantity 数量
     * @return void
     */
    public function returnRental(Product $product, $quantity)
    {
        $inventory = $this->findByProduct($product);
        
        if ($inventory) {
            $inventory->setRentedQuantity(max(0, $inventory->getRentedQuantity() - $quantity));
            $inventory->setLastUpdated(new \DateTime());
            
            $this->getEntityManager()->persist($inventory);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * メンテナンス中に移行
     *
     * @param Product $product 商品エンティティ
     * @param int $quantity 数量
     * @return void
     */
    public function moveToMaintenance(Product $product, $quantity)
    {
        $inventory = $this->findByProduct($product);
        
        if ($inventory) {
            $inventory->setRentedQuantity(max(0, $inventory->getRentedQuantity() - $quantity));
            $inventory->setMaintenanceQuantity($inventory->getMaintenanceQuantity() + $quantity);
            $inventory->setLastUpdated(new \DateTime());
            
            $this->getEntityManager()->persist($inventory);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * メンテナンス完了
     *
     * @param Product $product 商品エンティティ
     * @param int $quantity 数量
     * @return void
     */
    public function completeMaintenance(Product $product, $quantity)
    {
        $inventory = $this->findByProduct($product);
        
        if ($inventory) {
            $inventory->setMaintenanceQuantity(max(0, $inventory->getMaintenanceQuantity() - $quantity));
            $inventory->setLastUpdated(new \DateTime());
            
            $this->getEntityManager()->persist($inventory);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 利用可能在庫を設定
     *
     * @param Product $product 商品エンティティ
     * @param int $quantity 在庫数量
     * @return void
     */
    public function setAvailableQuantity(Product $product, $quantity)
    {
        $inventory = $this->findOrCreate($product);
        $inventory->setAvailableQuantity($quantity);
        $inventory->setLastUpdated(new \DateTime());
        
        $this->getEntityManager()->persist($inventory);
        $this->getEntityManager()->flush();
    }

    /**
     * 在庫を調整
     *
     * @param Product $product 商品エンティティ
     * @param int $availableQuantity 利用可能数量
     * @param int $reservedQuantity 予約中数量
     * @param int $rentedQuantity レンタル中数量
     * @param int $maintenanceQuantity メンテナンス中数量
     * @return void
     */
    public function adjustQuantity(Product $product, $availableQuantity, $reservedQuantity = null, $rentedQuantity = null, $maintenanceQuantity = null)
    {
        $inventory = $this->findOrCreate($product);
        
        $inventory->setAvailableQuantity($availableQuantity);
        
        if ($reservedQuantity !== null) {
            $inventory->setReservedQuantity($reservedQuantity);
        }
        
        if ($rentedQuantity !== null) {
            $inventory->setRentedQuantity($rentedQuantity);
        }
        
        if ($maintenanceQuantity !== null) {
            $inventory->setMaintenanceQuantity($maintenanceQuantity);
        }
        
        $inventory->setLastUpdated(new \DateTime());
        
        $this->getEntityManager()->persist($inventory);
        $this->getEntityManager()->flush();
    }

    /**
     * 在庫状況の詳細を取得
     *
     * @param Product $product 商品エンティティ
     * @return array
     */
    public function getInventoryDetails(Product $product)
    {
        $inventory = $this->findByProduct($product);
        
        if (!$inventory) {
            return [
                'available' => 0,
                'reserved' => 0,
                'rented' => 0,
                'maintenance' => 0,
                'actual_available' => 0,
                'total' => 0,
                'last_updated' => null,
            ];
        }
        
        $actualAvailable = $this->getActualAvailableQuantity($product);
        
        return [
            'available' => $inventory->getAvailableQuantity(),
            'reserved' => $inventory->getReservedQuantity(),
            'rented' => $inventory->getRentedQuantity(),
            'maintenance' => $inventory->getMaintenanceQuantity(),
            'actual_available' => $actualAvailable,
            'total' => $inventory->getAvailableQuantity(),
            'last_updated' => $inventory->getLastUpdated(),
        ];
    }

    /**
     * 在庫不足をチェック
     *
     * @param Product $product 商品エンティティ
     * @param int $requiredQuantity 必要数量
     * @return bool
     */
    public function isStockSufficient(Product $product, $requiredQuantity)
    {
        return $this->getActualAvailableQuantity($product) >= $requiredQuantity;
    }

    /**
     * 在庫アラート対象を取得
     *
     * @param int $threshold 閾値
     * @return array
     */
    public function getStockAlerts($threshold = 5)
    {
        $qb = $this->createQueryBuilder('ri')
            ->innerJoin('ri.Product', 'p')
            ->select('
                ri.id,
                p.name as product_name,
                ri.available_quantity,
                ri.reserved_quantity,
                ri.rented_quantity,
                ri.maintenance_quantity,
                (ri.available_quantity - ri.reserved_quantity - ri.rented_quantity) as actual_available
            ')
            ->where('(ri.available_quantity - ri.reserved_quantity - ri.rented_quantity) <= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('actual_available', 'ASC');
            
        return $qb->getQuery()->getResult();
    }

    /**
     * 在庫履歴を記録
     *
     * @param Product $product 商品エンティティ
     * @param string $action アクション
     * @param int $quantity 数量
     * @param string $note メモ
     * @return void
     */
    public function recordInventoryHistory(Product $product, $action, $quantity, $note = '')
    {
        // 在庫履歴テーブルがある場合の処理
        // 現在の設計では履歴テーブルは未実装のため、ログに記録
        $inventory = $this->findByProduct($product);
        
        if ($inventory) {
            $message = sprintf(
                '在庫変更: %s, 数量: %d, 商品: %s, メモ: %s',
                $action,
                $quantity,
                $product->getName(),
                $note
            );
            
            // ログ記録処理（実装は後のフェーズで）
            // $this->logService->info($message);
        }
    }

    /**
     * 在庫統計情報を取得
     *
     * @return array
     */
    public function getInventoryStatistics()
    {
        $qb = $this->createQueryBuilder('ri');
        
        $result = $qb->select('
                COUNT(ri.id) as total_products,
                SUM(ri.available_quantity) as total_available,
                SUM(ri.reserved_quantity) as total_reserved,
                SUM(ri.rented_quantity) as total_rented,
                SUM(ri.maintenance_quantity) as total_maintenance,
                SUM(ri.available_quantity - ri.reserved_quantity - ri.rented_quantity) as total_actual_available
            ')
            ->getQuery()
            ->getSingleResult();
            
        // 在庫切れ商品数
        $outOfStockCount = $this->createQueryBuilder('ri')
            ->select('COUNT(ri.id)')
            ->where('ri.available_quantity <= ri.reserved_quantity + ri.rented_quantity')
            ->getQuery()
            ->getSingleScalarResult();
            
        $result['out_of_stock'] = $outOfStockCount;
        
        return $result;
    }

    /**
     * 商品別在庫利用率を取得
     *
     * @param int $limit 取得件数
     * @return array
     */
    public function getUtilizationRate($limit = 10)
    {
        return $this->createQueryBuilder('ri')
            ->innerJoin('ri.Product', 'p')
            ->select('
                p.name as product_name,
                ri.available_quantity,
                ri.rented_quantity,
                CASE 
                    WHEN ri.available_quantity > 0 THEN 
                        (ri.rented_quantity * 100.0 / ri.available_quantity)
                    ELSE 0 
                END as utilization_rate
            ')
            ->where('ri.available_quantity > 0')
            ->orderBy('utilization_rate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 在庫を一括更新
     *
     * @param array $updates 更新データ [product_id => quantity]
     * @return void
     */
    public function bulkUpdateStock(array $updates)
    {
        foreach ($updates as $productId => $quantity) {
            $product = $this->getEntityManager()->getRepository(Product::class)->find($productId);
            if ($product) {
                $this->setAvailableQuantity($product, $quantity);
            }
        }
    }

    /**
     * 在庫データを削除
     *
     * @param Product $product 商品エンティティ
     * @return void
     */
    public function removeInventory(Product $product)
    {
        $inventory = $this->findByProduct($product);
        
        if ($inventory) {
            $this->getEntityManager()->remove($inventory);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 在庫データを初期化
     *
     * @param Product $product 商品エンティティ
     * @return void
     */
    public function resetInventory(Product $product)
    {
        $inventory = $this->findOrCreate($product);
        $inventory->setAvailableQuantity(0);
        $inventory->setReservedQuantity(0);
        $inventory->setRentedQuantity(0);
        $inventory->setMaintenanceQuantity(0);
        $inventory->setLastUpdated(new \DateTime());
        
        $this->getEntityManager()->persist($inventory);
        $this->getEntityManager()->flush();
    }

    /**
     * 在庫同期処理
     *
     * @return void
     */
    public function synchronizeInventory()
    {
        // 実際の注文データと在庫データを同期
        // 詳細な実装は後のフェーズで
        $inventories = $this->findAll();
        
        foreach ($inventories as $inventory) {
            $inventory->setLastUpdated(new \DateTime());
            $this->getEntityManager()->persist($inventory);
        }
        
        $this->getEntityManager()->flush();
    }
}