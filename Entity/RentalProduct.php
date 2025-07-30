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
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Order;
use Eccube\Entity\Customer;

/**
 * レンタル注文エンティティ
 *
 * @ORM\Table(name="plg_rental_order")
 * @ORM\Entity(repositoryClass="Plugin\Rental\Repository\RentalOrderRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RentalOrder extends AbstractEntity
{
    // ステータス定数
    const STATUS_RESERVED = 'reserved';      // 予約中
    const STATUS_RENTING = 'renting';        // レンタル中
    const STATUS_RETURNED = 'returned';      // 返却済み
    const STATUS_OVERDUE = 'overdue';        // 返却遅延
    const STATUS_CANCELLED = 'cancelled';    // キャンセル

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Order|null
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=true)
     */
    private $Order;

    /**
     * @var Customer|null
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", nullable=true)
     */
    private $Customer;

    /**
     * @var RentalProduct
     *
     * @ORM\ManyToOne(targetEntity="Plugin\Rental\Entity\RentalProduct")
     * @ORM\JoinColumn(name="rental_product_id", referencedColumnName="id")
     */
    private $RentalProduct;

    /**
     * @var string
     *
     * @ORM\Column(name="rental_code", type="string", length=50)
     */
    private $rental_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer", options={"unsigned":true, "default":1})
     */
    private $quantity = 1;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="rental_start_date", type="date")
     */
    private $rental_start_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="rental_end_date", type="date")
     */
    private $rental_end_date;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="actual_return_date", type="date", nullable=true)
     */
    private $actual_return_date;

    /**
     * @var string
     *
     * @ORM\Column(name="rental_fee", type="decimal", precision=12, scale=2)
     */
    private $rental_fee;

    /**
     * @var string|null
     *
     * @ORM\Column(name="deposit_fee", type="decimal", precision=12, scale=2, options={"default":"0.00"}, nullable=true)
     */
    private $deposit_fee = '0.00';

    /**
     * @var string|null
     *
     * @ORM\Column(name="overdue_fee", type="decimal", precision=12, scale=2, options={"default":"0.00"}, nullable=true)
     */
    private $overdue_fee = '0.00';

    /**
     * @var string
     *
     * @ORM\Column(name="total_amount", type="decimal", precision=12, scale=2)
     */
    private $total_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, options={"default":"reserved"})
     */
    private $status = self::STATUS_RESERVED;

    /**
     * @var string|null
     *
     * @ORM\Column(name="customer_name", type="string", length=255, nullable=true)
     */
    private $customer_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="customer_email", type="string", length=255, nullable=true)
     */
    private $customer_email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="customer_phone", type="string", length=20, nullable=true)
     */
    private $customer_phone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="delivery_address", type="text", nullable=true)
     */
    private $delivery_address;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;

    /**
     * @var boolean
     *
     * @ORM\Column(name="reminder_sent", type="boolean", options={"default":false})
     */
    private $reminder_sent = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetime")
     */
    private $update_date;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->create_date = new \DateTime();
        $this->update_date = new \DateTime();
        $this->generateRentalCode();
    }

    /**
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Order.
     *
     * @param Order|null $order
     *
     * @return RentalOrder
     */
    public function setOrder(Order $order = null)
    {
        $this->Order = $order;

        return $this;
    }

    /**
     * Get Order.
     *
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * Set Customer.
     *
     * @param Customer|null $customer
     *
     * @return RentalOrder
     */
    public function setCustomer(Customer $customer = null)
    {
        $this->Customer = $customer;

        return $this;
    }

    /**
     * Get Customer.
     *
     * @return Customer|null
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * Set RentalProduct.
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
     * Get RentalProduct.
     *
     * @return RentalProduct
     */
    public function getRentalProduct()
    {
        return $this->RentalProduct;
    }

    /**
     * Set rental_code.
     *
     * @param string $rentalCode
     *
     * @return RentalOrder
     */
    public function setRentalCode($rentalCode)
    {
        $this->rental_code = $rentalCode;

        return $this;
    }

    /**
     * Get rental_code.
     *
     * @return string
     */
    public function getRentalCode()
    {
        return $this->rental_code;
    }

    /**
     * Set quantity.
     *
     * @param integer $quantity
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
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set rental_start_date.
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
     * Get rental_start_date.
     *
     * @return \DateTime
     */
    public function getRentalStartDate()
    {
        return $this->rental_start_date;
    }

    /**
     * Set rental_end_date.
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
     * Get rental_end_date.
     *
     * @return \DateTime
     */
    public function getRentalEndDate()
    {
        return $this->rental_end_date;
    }

    /**
     * Set actual_return_date.
     *
     * @param \DateTime|null $actualReturnDate
     *
     * @return RentalOrder
     */
    public function setActualReturnDate($actualReturnDate = null)
    {
        $this->actual_return_date = $actualReturnDate;

        return $this;
    }

    /**
     * Get actual_return_date.
     *
     * @return \DateTime|null
     */
    public function getActualReturnDate()
    {
        return $this->actual_return_date;
    }

    /**
     * Set rental_fee.
     *
     * @param string $rentalFee
     *
     * @return RentalOrder
     */
    public function setRentalFee($rentalFee)
    {
        $this->rental_fee = $rentalFee;

        return $this;
    }

    /**
     * Get rental_fee.
     *
     * @return string
     */
    public function getRentalFee()
    {
        return $this->rental_fee;
    }

    /**
     * Set deposit_fee.
     *
     * @param string|null $depositFee
     *
     * @return RentalOrder
     */
    public function setDepositFee($depositFee = null)
    {
        $this->deposit_fee = $depositFee;

        return $this;
    }

    /**
     * Get deposit_fee.
     *
     * @return string|null
     */
    public function getDepositFee()
    {
        return $this->deposit_fee;
    }

    /**
     * Set overdue_fee.
     *
     * @param string|null $overdueFee
     *
     * @return RentalOrder
     */
    public function setOverdueFee($overdueFee = null)
    {
        $this->overdue_fee = $overdueFee;

        return $this;
    }

    /**
     * Get overdue_fee.
     *
     * @return string|null
     */
    public function getOverdueFee()
    {
        return $this->overdue_fee;
    }

    /**
     * Set total_amount.
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
     * Get total_amount.
     *
     * @return string
     */
    public function getTotalAmount()
    {
        return $this->total_amount;
    }

    /**
     * Set status.
     *
     * @param string $status
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
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set customer_name.
     *
     * @param string|null $customerName
     *
     * @return RentalOrder
     */
    public function setCustomerName($customerName = null)
    {
        $this->customer_name = $customerName;

        return $this;
    }

    /**
     * Get customer_name.
     *
     * @return string|null
     */
    public function getCustomerName()
    {
        return $this->customer_name;
    }

    /**
     * Set customer_email.
     *
     * @param string|null $customerEmail
     *
     * @return RentalOrder
     */
    public function setCustomerEmail($customerEmail = null)
    {
        $this->customer_email = $customerEmail;

        return $this;
    }

    /**
     * Get customer_email.
     *
     * @return string|null
     */
    public function getCustomerEmail()
    {
        return $this->customer_email;
    }

    /**
     * Set customer_phone.
     *
     * @param string|null $customerPhone
     *
     * @return RentalOrder
     */
    public function setCustomerPhone($customerPhone = null)
    {
        $this->customer_phone = $customerPhone;

        return $this;
    }

    /**
     * Get customer_phone.
     *
     * @return string|null
     */
    public function getCustomerPhone()
    {
        return $this->customer_phone;
    }

    /**
     * Set delivery_address.
     *
     * @param string|null $deliveryAddress
     *
     * @return RentalOrder
     */
    public function setDeliveryAddress($deliveryAddress = null)
    {
        $this->delivery_address = $deliveryAddress;

        return $this;
    }

    /**
     * Get delivery_address.
     *
     * @return string|null
     */
    public function getDeliveryAddress()
    {
        return $this->delivery_address;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return RentalOrder
     */
    public function setNotes($notes = null)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set reminder_sent.
     *
     * @param boolean $reminderSent
     *
     * @return RentalOrder
     */
    public function setReminderSent($reminderSent)
    {
        $this->reminder_sent = $reminderSent;

        return $this;
    }

    /**
     * Is reminder_sent.
     *
     * @return boolean
     */
    public function isReminderSent()
    {
        return $this->reminder_sent;
    }

    /**
     * Get reminder_sent.
     *
     * @return boolean
     */
    public function getReminderSent()
    {
        return $this->reminder_sent;
    }

    /**
     * Set create_date.
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
     * Get create_date.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date.
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
     * Get update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * レンタル期間（日数）を取得
     *
     * @return int
     */
    public function getRentalDays()
    {
        if (!$this->rental_start_date || !$this->rental_end_date) {
            return 0;
        }
        
        return $this->rental_start_date->diff($this->rental_end_date)->days;
    }

    /**
     * 返却遅延かどうかチェック
     *
     * @return bool
     */
    public function isOverdue()
    {
        if ($this->actual_return_date) {
            return false; // 既に返却済み
        }
        
        $today = new \DateTime();
        return $today > $this->rental_end_date;
    }

    /**
     * 返却遅延日数を取得
     *
     * @return int
     */
    public function getOverdueDays()
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        
        $today = new \DateTime();
        return $this->rental_end_date->diff($today)->days;
    }

    /**
     * ステータス名を日本語で取得
     *
     * @return string
     */
    public function getStatusName()
    {
        $statusNames = [
            self::STATUS_RESERVED => '予約中',
            self::STATUS_RENTING => 'レンタル中',
            self::STATUS_RETURNED => '返却済み',
            self::STATUS_OVERDUE => '返却遅延',
            self::STATUS_CANCELLED => 'キャンセル',
        ];
        
        return $statusNames[$this->status] ?? $this->status;
    }

    /**
     * レンタルコード生成
     */
    private function generateRentalCode()
    {
        $this->rental_code = 'R' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * 合計金額を再計算
     */
    public function recalculateTotalAmount()
    {
        $total = bcadd($this->rental_fee ?: '0', $this->deposit_fee ?: '0', 2);
        $total = bcadd($total, $this->overdue_fee ?: '0', 2);
        
        $this->total_amount = $total;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->update_date = new \DateTime();
    }
}