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
use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderStatus;

/**
 * レンタル注文エンティティ
 * 
 * @ORM\Table(name="plg_rental_order")
 * @ORM\Entity(repositoryClass="Plugin\Rental\Repository\RentalOrderRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RentalOrder
{
    /**
     * @var int
     * 
     * @ORM\Column(name="id", type="integer", options={"comment":"レンタル注文ID"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="order_no", type="string", length=255, nullable=false, unique=true, options={"comment":"注文番号"})
     */
    private $order_no;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="rental_start_date", type="datetime", nullable=false, options={"comment":"レンタル開始日"})
     */
    private $rental_start_date;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="rental_end_date", type="datetime", nullable=false, options={"comment":"レンタル終了日"})
     */
    private $rental_end_date;

    /**
     * @var \DateTime|null
     * 
     * @ORM\Column(name="actual_return_date", type="datetime", nullable=true, options={"comment":"実際の返却日"})
     */
    private $actual_return_date;

    /**
     * @var string
     * 
     * @ORM\Column(name="total_amount", type="decimal", nullable=false, options={"comment":"合計金額"})
     */
    private $total_amount;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="deposit_amount", type="decimal", nullable=true, options={"comment":"保証金額"})
     */
    private $deposit_amount;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="overdue_fee", type="decimal", nullable=true, options={"comment":"延滞料金"})
     */
    private $overdue_fee;

    /**
     * @var int
     * 
     * @ORM\Column(name="status", type="integer", nullable=false, options={"comment":"ステータス"})
     */
    private $status;

    /**
     * @var int
     * 
     * @ORM\Column(name="quantity", type="integer", nullable=false, options={"default":1, "comment":"数量"})
     */
    private $quantity = 1;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="delivery_address", type="text", nullable=true, options={"comment":"配送先住所"})
     */
    private $delivery_address;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="delivery_phone", type="string", length=14, nullable=true, options={"comment":"配送先電話番号"})
     */
    private $delivery_phone;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="note", type="text", nullable=true, options={"comment":"備考"})
     */
    private $note;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="admin_memo", type="text", nullable=true, options={"comment":"管理者メモ"})
     */
    private $admin_memo;

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
     * @var Customer
     * 
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", nullable=false)
     */
    private $Customer;

    /**
     * @var RentalProduct
     * 
     * @ORM\ManyToOne(targetEntity="Plugin\Rental\Entity\RentalProduct")
     * @ORM\JoinColumn(name="rental_product_id", referencedColumnName="id", nullable=false)
     */
    private $RentalProduct;

    // ステータス定数
    const STATUS_PENDING = 1;       // 申込中
    const STATUS_RESERVED = 2;      // 予約中
    const STATUS_ACTIVE = 3;        // レンタル中
    const STATUS_RETURNED = 4;      // 返却済み
    const STATUS_OVERDUE = 5;       // 返却遅延
    const STATUS_CANCELLED = 6;     // キャンセル
    const STATUS_DAMAGED = 7;       // 損傷
    const STATUS_LOST = 8;          // 紛失

    /**
     * ステータス名配列
     */
    const STATUS_NAMES = [
        self::STATUS_PENDING => '申込中',
        self::STATUS_RESERVED => '予約中',
        self::STATUS_ACTIVE => 'レンタル中',
        self::STATUS_RETURNED => '返却済み',
        self::STATUS_OVERDUE => '返却遅延',
        self::STATUS_CANCELLED => 'キャンセル',
        self::STATUS_DAMAGED => '損傷',
        self::STATUS_LOST => '紛失',
    ];

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->status = self::STATUS_PENDING;
        $this->quantity = 1;
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
     * Set orderNo.
     *
     * @param string $orderNo
     *
     * @return RentalOrder
     */
    public function setOrderNo($orderNo)
    {
        $this->order_no = $orderNo;

        return $this;
    }

    /**
     * Get orderNo.
     *
     * @return string
     */
    public function getOrderNo()
    {
        return $this->order_no;
    }

    /**
     * Set rentalStartDate.
     *
     * @param \DateTime $rentalStartDate
     *
     * @return RentalOrder
     */
    public function setRentalStartDate($rentalStartDate)
    {
        $this->rental_start_date = $rentalStartDate;

        return $this;
    }

    /**
     * Get rentalStartDate.
     *
     * @return \DateTime
     */
    public function getRentalStartDate()
    {
        return $this->rental_start_date;
    }

    /**
     * Set rentalEndDate.
     *
     * @param \DateTime $rentalEndDate
     *
     * @return RentalOrder
     */
    public function setRentalEndDate($rentalEndDate)
    {
        $this->rental_end_date = $rentalEndDate;

        return $this;
    }

    /**
     * Get rentalEndDate.
     *
     * @return \DateTime
     */
    public function getRentalEndDate()
    {
        return $this->rental_end_date;
    }

    /**
     * Set actualReturnDate.
     *
     * @param \DateTime|null $actualReturnDate
     *
     * @return RentalOrder
     */
    public function setActualReturnDate($actualReturnDate)
    {
        $this->actual_return_date = $actualReturnDate;

        return $this;
    }

    /**
     * Get actualReturnDate.
     *
     * @return \DateTime|null
     */
    public function getActualReturnDate()
    {
        return $this->actual_return_date;
    }

    /**
     * Set totalAmount.
     *
     * @param string $totalAmount
     *
     * @return RentalOrder
     */
    public function setTotalAmount($totalAmount)
    {
        $this->total_amount = $totalAmount;

        return $this;
    }

    /**
     * Get totalAmount.
     *
     * @return string
     */
    public function getTotalAmount()
    {
        return $this->total_amount;
    }

    /**
     * Set depositAmount.
     *
     * @param string|null $depositAmount
     *
     * @return RentalOrder
     */
    public function setDepositAmount($depositAmount)
    {
        $this->deposit_amount = $depositAmount;

        return $this;
    }

    /**
     * Get depositAmount.
     *
     * @return string|null
     */
    public function getDepositAmount()
    {
        return $this->deposit_amount;
    }

    /**
     * Set overdueFee.
     *
     * @param string|null $overdueFee
     *
     * @return RentalOrder
     */
    public function setOverdueFee($overdueFee)
    {
        $this->overdue_fee = $overdueFee;

        return $this;
    }

    /**
     * Get overdueFee.
     *
     * @return string|null
     */
    public function getOverdueFee()
    {
        return $this->overdue_fee;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return RentalOrder
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set quantity.
     *
     * @param int $quantity
     *
     * @return RentalOrder
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set deliveryAddress.
     *
     * @param string|null $deliveryAddress
     *
     * @return RentalOrder
     */
    public function setDeliveryAddress($deliveryAddress)
    {
        $this->delivery_address = $deliveryAddress;

        return $this;
    }

    /**
     * Get deliveryAddress.
     *
     * @return string|null
     */
    public function getDeliveryAddress()
    {
        return $this->delivery_address;
    }

    /**
     * Set deliveryPhone.
     *
     * @param string|null $deliveryPhone
     *
     * @return RentalOrder
     */
    public function setDeliveryPhone($deliveryPhone)
    {
        $this->delivery_phone = $deliveryPhone;

        return $this;
    }

    /**
     * Get deliveryPhone.
     *
     * @return string|null
     */
    public function getDeliveryPhone()
    {
        return $this->delivery_phone;
    }

    /**
     * Set note.
     *
     * @param string|null $note
     *
     * @return RentalOrder
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note.
     *
     * @return string|null
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set adminMemo.
     *
     * @param string|null $adminMemo
     *
     * @return RentalOrder
     */
    public function setAdminMemo($adminMemo)
    {
        $this->admin_memo = $adminMemo;

        return $this;
    }

    /**
     * Get adminMemo.
     *
     * @return string|null
     */
    public function getAdminMemo()
    {
        return $this->admin_memo;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return RentalOrder
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
     * @return RentalOrder
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
     * Set customer.
     *
     * @param Customer $customer
     *
     * @return RentalOrder
     */
    public function setCustomer(Customer $customer)
    {
        $this->Customer = $customer;

        return $this;
    }

    /**
     * Get customer.
     *
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * Set rentalProduct.
     *
     * @param RentalProduct $rentalProduct
     *
     * @return RentalOrder
     */
    public function setRentalProduct(RentalProduct $rentalProduct)
    {
        $this->RentalProduct = $rentalProduct;

        return $this;
    }

    /**
     * Get rentalProduct.
     *
     * @return RentalProduct
     */
    public function getRentalProduct()
    {
        return $this->RentalProduct;
    }

    /**
     * ステータス名を取得
     *
     * @return string
     */
    public function getStatusName()
    {
        return self::STATUS_NAMES[$this->status] ?? '不明';
    }

    /**
     * レンタル日数を取得
     *
     * @return int
     */
    public function getRentalDays()
    {
        return $this->rental_start_date->diff($this->rental_end_date)->days + 1;
    }

    /**
     * 延滞日数を取得
     *
     * @return int
     */
    public function getOverdueDays()
    {
        if (!$this->actual_return_date || $this->actual_return_date <= $this->rental_end_date) {
            return 0;
        }

        return $this->rental_end_date->diff($this->actual_return_date)->days;
    }

    /**
     * 現在延滞中かどうか
     *
     * @return bool
     */
    public function isOverdue()
    {
        if ($this->status === self::STATUS_RETURNED || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        return new \DateTime() > $this->rental_end_date;
    }

    /**
     * 返却可能かどうか
     *
     * @return bool
     */
    public function canReturn()
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_OVERDUE]);
    }

    /**
     * キャンセル可能かどうか
     *
     * @return bool
     */
    public function canCancel()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RESERVED]);
    }

    /**
     * 注文番号を生成
     *
     * @return string
     */
    public function generateOrderNo()
    {
        return 'R' . date('Ymd') . sprintf('%06d', $this->id);
    }

    /**
     * 合計支払額を取得（レンタル料金 + 保証金 + 延滞料金）
     *
     * @return string
     */
    public function getTotalPaymentAmount()
    {
        $total = $this->total_amount;

        if ($this->deposit_amount) {
            $total = bcadd($total, $this->deposit_amount, 2);
        }

        if ($this->overdue_fee) {
            $total = bcadd($total, $this->overdue_fee, 2);
        }

        return $total;
    }

    /**
     * エンティティ更新前処理
     * 
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->update_date = new \DateTime();
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
    }

    /**
     * 文字列表現
     *
     * @return string
     */
    public function __toString()
    {
        return $this->order_no ?: 'レンタル注文';
    }
}