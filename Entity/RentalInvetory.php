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

namespace Plugin\Rental\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Product;

/**
 * レンタル在庫エンティティ
 * 
 * @ORM\Table(name="plg_rental_inventory")
 * @ORM\Entity(repositoryClass="Plugin\Rental\Repository\RentalInventoryRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RentalInventory
{
    /**
     * @var int
     * 
     * @ORM\Column(name="id", type="integer", options={"comment":"レンタル在庫ID"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * 
     * @ORM\Column(name="available_quantity", type="integer", nullable=false, options={"default":0, "comment":"利用可能数量"})
     */
    private $available_quantity = 0;

    /**
     * @var int
     * 
     * @ORM\Column(name="reserved_quantity", type="integer", nullable=false, options={"default":0, "comment":"予約中数量"})
     */
    private $reserved_quantity = 0;

    /**
     * @var int
     * 
     * @ORM\Column(name="rented_quantity", type="integer", nullable=false, options={"default":0, "comment":"レンタル中数量"})
     */
    private $rented_quantity = 0;

    /**
     * @var int
     * 
     * @ORM\Column(name="maintenance_quantity", type="integer", nullable=false, options={"default":0, "comment":"メンテナンス中数量"})
     */
    private $maintenance_quantity = 0;

    /**
     * @var int
     * 
     * @ORM\Column(name="damaged_quantity", type="integer", nullable=false, options={"default":0, "comment":"損傷品数量"})
     */
    private $damaged_quantity = 0;

    /**
     * @var int
     * 
     * @ORM\Column(name="lost_quantity", type="integer", nullable=false, options={"default":0, "comment":"紛失品数量"})
     */
    private $lost_quantity = 0;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="stock_location", type="string", length=255, nullable=true, options={"comment":"在庫保管場所"})
     */
    private $stock_location;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="warehouse_code", type="string", length=50, nullable=true, options={"comment":"倉庫コード"})
     */
    private $warehouse_code;

    /**
     * @var int
     * 
     * @ORM\Column(name="alert_threshold", type="integer", nullable=false, options={"default":5, "comment":"アラート閾値"})
     */
    private $alert_threshold = 5;

    /**
     * @var bool
     * 
     * @ORM\Column(name="auto_reorder_enabled", type="boolean", nullable=false, options={"default":false, "comment":"自動発注有効フラグ"})
     */
    private $auto_reorder_enabled = false;

    /**
     * @var int|null
     * 
     * @ORM\Column(name="reorder_point", type="integer", nullable=true, options={"comment":"発注点"})
     */
    private $reorder_point;

    /**
     * @var int|null
     * 
     * @ORM\Column(name="reorder_quantity", type="integer", nullable=true, options={"comment":"発注数量"})
     */
    private $reorder_quantity;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="last_updated", type="datetime", nullable=false, options={"comment":"最終更新日時"})
     */
    private $last_updated;

    /**
     * @var \DateTime|null
     * 
     * @ORM\Column(name="last_inventory_date", type="datetime", nullable=true, options={"comment":"最終棚卸日"})
     */
    private $last_inventory_date;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="inventory_notes", type="text", nullable=true, options={"comment":"在庫メモ"})
     */
    private $inventory_notes;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="create_date", type="datetime", nullable=false, options={"comment":"作成日時"})
     */
    private $create_date;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="update_date", type="datetime", nullable=false, options={"comment":"更新日時"})
     */
    private $update_date;

    /**
     * @var Product
     * 
     * @ORM\OneToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false)
     */
    private $Product;

    // 在庫ステータス定数
    const STATUS_AVAILABLE = 'available';
    const STATUS_RESERVED = 'reserved';
    const STATUS_RENTED = 'rented';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_DAMAGED = 'damaged';
    const STATUS_LOST = 'lost';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->available_quantity = 0;
        $this->reserved_quantity = 0;
        $this->rented_quantity = 0;
        $this->maintenance_quantity = 0;
        $this->damaged_quantity = 0;
        $this->lost_quantity = 0;
        $this->alert_threshold = 5;
        $this->auto_reorder_enabled = false;
        $this->last_updated = new \DateTime();
        $this->create_date = new \DateTime();
        $this->update_date = new \DateTime();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set availableQuantity.
     *
     * @param int $availableQuantity
     *
     * @return RentalInventory
     */
    public function setAvailableQuantity($availableQuantity)
    {
        $this->available_quantity = $availableQuantity;

        return $this;
    }

    /**
     * Get availableQuantity.
     *
     * @return int
     */
    public function getAvailableQuantity()
    {
        return $this->available_quantity;
    }

    /**
     * Set reservedQuantity.
     *
     * @param int $reservedQuantity
     *
     * @return RentalInventory
     */
    public function setReservedQuantity($reservedQuantity)
    {
        $this->reserved_quantity = $reservedQuantity;

        return $this;
    }

    /**
     * Get reservedQuantity.
     *
     * @return int
     */
    public function getReservedQuantity()
    {
        return $this->reserved_quantity;
    }

    /**
     * Set rentedQuantity.
     *
     * @param int $rentedQuantity
     *
     * @return RentalInventory
     */
    public function setRentedQuantity($rentedQuantity)
    {
        $this->rented_quantity = $rentedQuantity;

        return $this;
    }

    /**
     * Get rentedQuantity.
     *
     * @return int
     */
    public function getRentedQuantity()
    {
        return $this->rented_quantity;
    }

    /**
     * Set maintenanceQuantity.
     *
     * @param int $maintenanceQuantity
     *
     * @return RentalInventory
     */
    public function setMaintenanceQuantity($maintenanceQuantity)
    {
        $this->maintenance_quantity = $maintenanceQuantity;

        return $this;
    }

    /**
     * Get maintenanceQuantity.
     *
     * @return int
     */
    public function getMaintenanceQuantity()
    {
        return $this->maintenance_quantity;
    }

    /**
     * Set damagedQuantity.
     *
     * @param int $damagedQuantity
     *
     * @return RentalInventory
     */
    public function setDamagedQuantity($damagedQuantity)
    {
        $this->damaged_quantity = $damagedQuantity;

        return $this;
    }

    /**
     * Get damagedQuantity.
     *
     * @return int
     */
    public function getDamagedQuantity()
    {
        return $this->damaged_quantity;
    }

    /**
     * Set lostQuantity.
     *
     * @param int $lostQuantity
     *
     * @return RentalInventory
     */
    public function setLostQuantity($lostQuantity)
    {
        $this->lost_quantity = $lostQuantity;

        return $this;
    }

    /**
     * Get lostQuantity.
     *
     * @return int
     */
    public function getLostQuantity()
    {
        return $this->lost_quantity;
    }

    /**
     * Set stockLocation.
     *
     * @param string|null $stockLocation
     *
     * @return RentalInventory
     */
    public function setStockLocation($stockLocation)
    {
        $this->stock_location = $stockLocation;

        return $this;
    }

    /**
     * Get stockLocation.
     *
     * @return string|null
     */
    public function getStockLocation()
    {
        return $this->stock_location;
    }

    /**
     * Set warehouseCode.
     *
     * @param string|null $warehouseCode
     *
     * @return RentalInventory
     */
    public function setWarehouseCode($warehouseCode)
    {
        $this->warehouse_code = $warehouseCode;

        return $this;
    }

    /**
     * Get warehouseCode.
     *
     * @return string|null
     */
    public function getWarehouseCode()
    {
        return $this->warehouse_code;
    }

    /**
     * Set alertThreshold.
     *
     * @param int $alertThreshold
     *
     * @return RentalInventory
     */
    public function setAlertThreshold($alertThreshold)
    {
        $this->alert_threshold = $alertThreshold;

        return $this;
    }

    /**
     * Get alertThreshold.
     *
     * @return int
     */
    public function getAlertThreshold()
    {
        return $this->alert_threshold;
    }

    /**
     * Set autoReorderEnabled.
     *
     * @param bool $autoReorderEnabled
     *
     * @return RentalInventory
     */
    public function setAutoReorderEnabled($autoReorderEnabled)
    {
        $this->auto_reorder_enabled = $autoReorderEnabled;

        return $this;
    }

    /**
     * Get autoReorderEnabled.
     *
     * @return bool
     */
    public function getAutoReorderEnabled()
    {
        return $this->auto_reorder_enabled;
    }

    /**
     * Set reorderPoint.
     *
     * @param int|null $reorderPoint
     *
     * @return RentalInventory
     */
    public function setReorderPoint($reorderPoint)
    {
        $this->reorder_point = $reorderPoint;

        return $this;
    }

    /**
     * Get reorderPoint.
     *
     * @return int|null
     */
    public function getReorderPoint()
    {
        return $this->reorder_point;
    }

    /**
     * Set reorderQuantity.
     *
     * @param int|null $reorderQuantity
     *
     * @return RentalInventory
     */
    public function setReorderQuantity($reorderQuantity)
    {
        $this->reorder_quantity = $reorderQuantity;

        return $this;
    }

    /**
     * Get reorderQuantity.
     *
     * @return int|null
     */
    public function getReorderQuantity()
    {
        return $this->reorder_quantity;
    }

    /**
     * Set lastUpdated.
     *
     * @param \DateTime $lastUpdated
     *
     * @return RentalInventory
     */
    public function setLastUpdated($lastUpdated)
    {
        $this->last_updated = $lastUpdated;

        return $this;
    }

    /**
     * Get lastUpdated.
     *
     * @return \DateTime
     */
    public function getLastUpdated()
    {
        return $this->last_updated;
    }

    /**
     * Set lastInventoryDate.
     *
     * @param \DateTime|null $lastInventoryDate
     *
     * @return RentalInventory
     */
    public function setLastInventoryDate($lastInventoryDate)
    {
        $this->last_inventory_date = $lastInventoryDate;

        return $this;
    }

    /**
     * Get lastInventoryDate.
     *
     * @return \DateTime|null
     */
    public function getLastInventoryDate()
    {
        return $this->last_inventory_date;
    }

    /**
     * Set inventoryNotes.
     *
     * @param string|null $inventoryNotes
     *
     * @return RentalInventory
     */
    public function setInventoryNotes($inventoryNotes)
    {
        $this->inventory_notes = $inventoryNotes;

        return $this;
    }

    /**
     * Get inventoryNotes.
     *
     * @return string|null
     */
    public function getInventoryNotes()
    {
        return $this->inventory_notes;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return RentalInventory
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get createDate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set updateDate.
     *
     * @param \DateTime $updateDate
     *
     * @return RentalInventory
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * Set product.
     *
     * @param Product $product
     *
     * @return RentalInventory
     */
    public function setProduct(Product $product)
    {
        $this->Product = $product;

        return $this;
    }

    /**
     * Get product.
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * 実際に利用可能な数量を取得
     *
     * @return int
     */
    public function getActualAvailableQuantity()
    {
        return max(0, $this->available_quantity - $this->reserved_quantity - $this->rented_quantity);
    }

    /**
     * 総在庫数を取得
     *
     * @return int
     */
    public function getTotalQuantity()
    {
        return $this->available_quantity + 
               $this->reserved_quantity + 
               $this->rented_quantity + 
               $this->maintenance_quantity + 
               $this->damaged_quantity + 
               $this->lost_quantity;
    }

    /**
     * 利用率を取得（%）
     *
     * @return float
     */
    public function getUtilizationRate()
    {
        if ($this->available_quantity === 0) {
            return 0.0;
        }
        
        $utilized = $this->reserved_quantity + $this->rented_quantity;
        return round(($utilized / $this->available_quantity) * 100, 2);
    }

    /**
     * 在庫切れかどうか
     *
     * @return bool
     */
    public function isOutOfStock()
    {
        return $this->getActualAvailableQuantity() <= 0;
    }

    /**
     * 在庫が少ないかどうか
     *
     * @return bool
     */
    public function isLowStock()
    {
        return $this->getActualAvailableQuantity() <= $this->alert_threshold;
    }

    /**
     * 発注が必要かどうか
     *
     * @return bool
     */
    public function needsReorder()
    {
        return $this->auto_reorder_enabled && 
               $this->reorder_point !== null && 
               $this->getActualAvailableQuantity() <= $this->reorder_point;
    }

    /**
     * 在庫を予約
     *
     * @param int $quantity
     * @return bool
     */
    public function reserve($quantity)
    {
        if ($this->getActualAvailableQuantity() < $quantity) {
            return false;
        }
        
        $this->reserved_quantity += $quantity;
        $this->updateLastUpdated();
        
        return true;
    }

    /**
     * 予約をキャンセル
     *
     * @param int $quantity
     * @return void
     */
    public function cancelReservation($quantity)
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->updateLastUpdated();
    }

    /**
     * 予約からレンタル中に移行
     *
     * @param int $quantity
     * @return void
     */
    public function activateRental($quantity)
    {
        $actualQuantity = min($quantity, $this->reserved_quantity);
        $this->reserved_quantity -= $actualQuantity;
        $this->rented_quantity += $actualQuantity;
        $this->updateLastUpdated();
    }

    /**
     * レンタル中から返却
     *
     * @param int $quantity
     * @return void
     */
    public function returnFromRental($quantity)
    {
        $actualQuantity = min($quantity, $this->rented_quantity);
        $this->rented_quantity -= $actualQuantity;
        $this->updateLastUpdated();
    }

    /**
     * メンテナンスに移行
     *
     * @param int $quantity
     * @param string $source ソース（'available', 'rented', 'returned'）
     * @return void
     */
    public function moveToMaintenance($quantity, $source = 'rented')
    {
        switch ($source) {
            case 'available':
                $actualQuantity = min($quantity, $this->available_quantity);
                $this->available_quantity -= $actualQuantity;
                break;
            case 'rented':
                $actualQuantity = min($quantity, $this->rented_quantity);
                $this->rented_quantity -= $actualQuantity;
                break;
            default:
                $actualQuantity = $quantity;
                break;
        }
        
        $this->maintenance_quantity += $actualQuantity;
        $this->updateLastUpdated();
    }

    /**
     * メンテナンス完了
     *
     * @param int $quantity
     * @return void
     */
    public function completeMaintenance($quantity)
    {
        $actualQuantity = min($quantity, $this->maintenance_quantity);
        $this->maintenance_quantity -= $actualQuantity;
        $this->updateLastUpdated();
    }

    /**
     * 損傷として記録
     *
     * @param int $quantity
     * @param string $source ソース
     * @return void
     */
    public function markAsDamaged($quantity, $source = 'rented')
    {
        switch ($source) {
            case 'available':
                $actualQuantity = min($quantity, $this->available_quantity);
                $this->available_quantity -= $actualQuantity;
                break;
            case 'rented':
                $actualQuantity = min($quantity, $this->rented_quantity);
                $this->rented_quantity -= $actualQuantity;
                break;
            case 'maintenance':
                $actualQuantity = min($quantity, $this->maintenance_quantity);
                $this->maintenance_quantity -= $actualQuantity;
                break;
            default:
                $actualQuantity = $quantity;
                break;
        }
        
        $this->damaged_quantity += $actualQuantity;
        $this->updateLastUpdated();
    }

    /**
     * 紛失として記録
     *
     * @param int $quantity
     * @param string $source ソース
     * @return void
     */
    public function markAsLost($quantity, $source = 'rented')
    {
        switch ($source) {
            case 'available':
                $actualQuantity = min($quantity, $this->available_quantity);
                $this->available_quantity -= $actualQuantity;
                break;
            case 'rented':
                $actualQuantity = min($quantity, $this->rented_quantity);
                $this->rented_quantity -= $actualQuantity;
                break;
            default:
                $actualQuantity = $quantity;
                break;
        }
        
        $this->lost_quantity += $actualQuantity;
        $this->updateLastUpdated();
    }

    /**
     * 在庫を補充
     *
     * @param int $quantity
     * @return void
     */
    public function addStock($quantity)
    {
        $this->available_quantity += $quantity;
        $this->updateLastUpdated();
    }

    /**
     * 在庫状況を取得
     *
     * @return array
     */
    public function getStockStatus()
    {
        return [
            'available' => $this->available_quantity,
            'reserved' => $this->reserved_quantity,
            'rented' => $this->rented_quantity,
            'maintenance' => $this->maintenance_quantity,
            'damaged' => $this->damaged_quantity,
            'lost' => $this->lost_quantity,
            'actual_available' => $this->getActualAvailableQuantity(),
            'total' => $this->getTotalQuantity(),
            'utilization_rate' => $this->getUtilizationRate(),
            'is_out_of_stock' => $this->isOutOfStock(),
            'is_low_stock' => $this->isLowStock(),
            'needs_reorder' => $this->needsReorder(),
        ];
    }

    /**
     * 最終更新日時を更新
     *
     * @return void
     */
    private function updateLastUpdated()
    {
        $this->last_updated = new \DateTime();
        $this->update_date = new \DateTime();
    }

    /**
     * エンティティ更新前処理
     * 
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->update_date = new \DateTime();
        $this->last_updated = new \DateTime();
    }

    /**
     * エンティティ永続化前処理
     * 
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $now = new \DateTime();
        $this->create_date = $now;
        $this->update_date = $now;
        $this->last_updated = $now;
    }

    /**
     * 文字列表現
     *
     * @return string
     */
    public function __toString()
    {
        $productName = $this->Product ? $this->Product->getName() : '商品名不明';
        return sprintf('%s 在庫: %d (利用可能: %d)', 
            $productName, 
            $this->getTotalQuantity(),
            $this->getActualAvailableQuantity()
        );
    }
}