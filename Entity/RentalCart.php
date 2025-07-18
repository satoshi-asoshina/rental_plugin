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
use Eccube\Entity\Customer;
use Eccube\Entity\Product;

/**
 * レンタルカートエンティティ
 * 
 * @ORM\Table(name="plg_rental_cart")
 * @ORM\Entity(repositoryClass="Plugin\Rental\Repository\RentalCartRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RentalCart
{
    /**
     * @var int
     * 
     * @ORM\Column(name="id", type="integer", options={"comment":"レンタルカートID"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="session_id", type="string", length=255, nullable=false, options={"comment":"セッションID"})
     */
    private $session_id;

    /**
     * @var int
     * 
     * @ORM\Column(name="quantity", type="integer", nullable=false, options={"default":1, "comment":"数量"})
     */
    private $quantity = 1;

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
     * @var string
     * 
     * @ORM\Column(name="calculated_price", type="decimal", nullable=false, options={"comment":"計算された価格"})
     */
    private $calculated_price;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="note", type="text", nullable=true, options={"comment":"備考"})
     */
    private $note;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="selected_options", type="text", nullable=true, options={"comment":"選択されたオプション"})
     */
    private $selected_options;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="delivery_date", type="datetime", nullable=true, options={"comment":"配送希望日"})
     */
    private $delivery_date;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="delivery_time", type="string", length=50, nullable=true, options={"comment":"配送希望時間"})
     */
    private $delivery_time;

    /**
     * @var bool
     * 
     * @ORM\Column(name="insurance_enabled", type="boolean", nullable=false, options={"default":false, "comment":"保険有効フラグ"})
     */
    private $insurance_enabled = false;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="insurance_fee", type="decimal", nullable=true, options={"comment":"保険料金"})
     */
    private $insurance_fee;

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
     * @var Customer|null
     * 
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", nullable=true)
     */
    private $Customer;

    /**
     * @var Product
     * 
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false)
     */
    private $Product;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->quantity = 1;
        $this->insurance_enabled = false;
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
     * Set sessionId.
     *
     * @param string $sessionId
     *
     * @return RentalCart
     */
    public function setSessionId($sessionId)
    {
        $this->session_id = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * Set quantity.
     *
     * @param int $quantity
     *
     * @return RentalCart
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
     * Set rentalStartDate.
     *
     * @param \DateTime $rentalStartDate
     *
     * @return RentalCart
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
     * @return RentalCart
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
     * Set calculatedPrice.
     *
     * @param string $calculatedPrice
     *
     * @return RentalCart
     */
    public function setCalculatedPrice($calculatedPrice)
    {
        $this->calculated_price = $calculatedPrice;

        return $this;
    }

    /**
     * Get calculatedPrice.
     *
     * @return string
     */
    public function getCalculatedPrice()
    {
        return $this->calculated_price;
    }

    /**
     * Set note.
     *
     * @param string|null $note
     *
     * @return RentalCart
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
     * Set selectedOptions.
     *
     * @param string|null $selectedOptions
     *
     * @return RentalCart
     */
    public function setSelectedOptions($selectedOptions)
    {
        $this->selected_options = $selectedOptions;

        return $this;
    }

    /**
     * Get selectedOptions.
     *
     * @return string|null
     */
    public function getSelectedOptions()
    {
        return $this->selected_options;
    }

    /**
     * Set deliveryDate.
     *
     * @param \DateTime|null $deliveryDate
     *
     * @return RentalCart
     */
    public function setDeliveryDate($deliveryDate)
    {
        $this->delivery_date = $deliveryDate;

        return $this;
    }

    /**
     * Get deliveryDate.
     *
     * @return \DateTime|null
     */
    public function getDeliveryDate()
    {
        return $this->delivery_date;
    }

    /**
     * Set deliveryTime.
     *
     * @param string|null $deliveryTime
     *
     * @return RentalCart
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->delivery_time = $deliveryTime;

        return $this;
    }

    /**
     * Get deliveryTime.
     *
     * @return string|null
     */
    public function getDeliveryTime()
    {
        return $this->delivery_time;
    }

    /**
     * Set insuranceEnabled.
     *
     * @param bool $insuranceEnabled
     *
     * @return RentalCart
     */
    public function setInsuranceEnabled($insuranceEnabled)
    {
        $this->insurance_enabled = $insuranceEnabled;

        return $this;
    }

    /**
     * Get insuranceEnabled.
     *
     * @return bool
     */
    public function getInsuranceEnabled()
    {
        return $this->insurance_enabled;
    }

    /**
     * Set insuranceFee.
     *
     * @param string|null $insuranceFee
     *
     * @return RentalCart
     */
    public function setInsuranceFee($insuranceFee)
    {
        $this->insurance_fee = $insuranceFee;

        return $this;
    }

    /**
     * Get insuranceFee.
     *
     * @return string|null
     */
    public function getInsuranceFee()
    {
        return $this->insurance_fee;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return RentalCart
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
     * @return RentalCart
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
     * @param Customer|null $customer
     *
     * @return RentalCart
     */
    public function setCustomer(Customer $customer = null)
    {
        $this->Customer = $customer;

        return $this;
    }

    /**
     * Get customer.
     *
     * @return Customer|null
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * Set product.
     *
     * @param Product $product
     *
     * @return RentalCart
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
     * レンタル日数を取得
     *
     * @return int
     */
    public function getRentalDays()
    {
        return $this->rental_start_date->diff($this->rental_end_date)->days + 1;
    }

    /**
     * 合計金額を取得（基本料金+保険料）
     *
     * @return string
     */
    public function getTotalAmount()
    {
        $total = $this->calculated_price;
        
        if ($this->insurance_enabled && $this->insurance_fee) {
            $total = bcadd($total, $this->insurance_fee, 2);
        }
        
        return $total;
    }

    /**
     * 選択されたオプションを配列で取得
     *
     * @return array
     */
    public function getSelectedOptionsArray()
    {
        if (empty($this->selected_options)) {
            return [];
        }
        
        $decoded = json_decode($this->selected_options, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 選択されたオプションを配列で設定
     *
     * @param array $options
     * @return RentalCart
     */
    public function setSelectedOptionsArray(array $options)
    {
        $this->selected_options = json_encode($options);
        return $this;
    }

    /**
     * カート商品が有効かチェック
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->Product &&
               $this->rental_start_date &&
               $this->rental_end_date &&
               $this->rental_start_date < $this->rental_end_date &&
               $this->quantity > 0 &&
               $this->calculated_price !== null;
    }

    /**
     * レンタル期間が重複しているかチェック
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return bool
     */
    public function hasConflictingPeriod(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->rental_start_date < $endDate && $this->rental_end_date > $startDate;
    }

    /**
     * カート商品が期限切れかチェック
     *
     * @param int $expireDays 期限日数（デフォルト7日）
     * @return bool
     */
    public function isExpired($expireDays = 7)
    {
        $expireDate = clone $this->create_date;
        $expireDate->add(new \DateInterval('P' . $expireDays . 'D'));
        
        return new \DateTime() > $expireDate;
    }

    /**
     * カート商品をクローン
     *
     * @return RentalCart
     */
    public function createClone()
    {
        $clone = new self();
        $clone->setProduct($this->Product);
        $clone->setCustomer($this->Customer);
        $clone->setSessionId($this->session_id);
        $clone->setQuantity($this->quantity);
        $clone->setRentalStartDate(clone $this->rental_start_date);
        $clone->setRentalEndDate(clone $this->rental_end_date);
        $clone->setCalculatedPrice($this->calculated_price);
        $clone->setNote($this->note);
        $clone->setSelectedOptions($this->selected_options);
        $clone->setDeliveryDate($this->delivery_date ? clone $this->delivery_date : null);
        $clone->setDeliveryTime($this->delivery_time);
        $clone->setInsuranceEnabled($this->insurance_enabled);
        $clone->setInsuranceFee($this->insurance_fee);
        
        return $clone;
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
        $productName = $this->Product ? $this->Product->getName() : '商品名不明';
        return sprintf('%s (%d個) %s～%s', 
            $productName, 
            $this->quantity,
            $this->rental_start_date ? $this->rental_start_date->format('Y/m/d') : '',
            $this->rental_end_date ? $this->rental_end_date->format('Y/m/d') : ''
        );
    }
}