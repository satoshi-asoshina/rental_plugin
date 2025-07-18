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
 * レンタル商品設定エンティティ
 * 
 * @ORM\Table(name="plg_rental_product")
 * @ORM\Entity(repositoryClass="Plugin\Rental\Repository\RentalProductRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RentalProduct
{
    /**
     * @var int
     * 
     * @ORM\Column(name="id", type="integer", options={"comment":"レンタル商品設定ID"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="daily_price", type="decimal", nullable=true, options={"comment":"日額料金"})
     */
    private $daily_price;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="weekly_price", type="decimal", nullable=true, options={"comment":"週額料金"})
     */
    private $weekly_price;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="monthly_price", type="decimal", nullable=true, options={"comment":"月額料金"})
     */
    private $monthly_price;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="deposit_amount", type="decimal", nullable=true, options={"comment":"保証金額"})
     */
    private $deposit_amount;

    /**
     * @var int|null
     * 
     * @ORM\Column(name="max_rental_days", type="integer", nullable=true, options={"comment":"最大レンタル日数"})
     */
    private $max_rental_days;

    /**
     * @var int
     * 
     * @ORM\Column(name="min_rental_days", type="integer", nullable=false, options={"default":1, "comment":"最小レンタル日数"})
     */
    private $min_rental_days = 1;

    /**
     * @var bool
     * 
     * @ORM\Column(name="is_rental_enabled", type="boolean", nullable=false, options={"default":true, "comment":"レンタル有効フラグ"})
     */
    private $is_rental_enabled = true;

    /**
     * @var bool
     * 
     * @ORM\Column(name="auto_approval", type="boolean", nullable=false, options={"default":false, "comment":"自動承認フラグ"})
     */
    private $auto_approval = false;

    /**
     * @var int|null
     * 
     * @ORM\Column(name="preparation_days", type="integer", nullable=true, options={"comment":"準備日数"})
     */
    private $preparation_days;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="rental_note", type="text", nullable=true, options={"comment":"レンタル注意事項"})
     */
    private $rental_note;

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

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->min_rental_days = 1;
        $this->is_rental_enabled = true;
        $this->auto_approval = false;
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
     * Set dailyPrice.
     *
     * @param string|null $dailyPrice
     *
     * @return RentalProduct
     */
    public function setDailyPrice($dailyPrice)
    {
        $this->daily_price = $dailyPrice;

        return $this;
    }

    /**
     * Get dailyPrice.
     *
     * @return string|null
     */
    public function getDailyPrice()
    {
        return $this->daily_price;
    }

    /**
     * Set weeklyPrice.
     *
     * @param string|null $weeklyPrice
     *
     * @return RentalProduct
     */
    public function setWeeklyPrice($weeklyPrice)
    {
        $this->weekly_price = $weeklyPrice;

        return $this;
    }

    /**
     * Get weeklyPrice.
     *
     * @return string|null
     */
    public function getWeeklyPrice()
    {
        return $this->weekly_price;
    }

    /**
     * Set monthlyPrice.
     *
     * @param string|null $monthlyPrice
     *
     * @return RentalProduct
     */
    public function setMonthlyPrice($monthlyPrice)
    {
        $this->monthly_price = $monthlyPrice;

        return $this;
    }

    /**
     * Get monthlyPrice.
     *
     * @return string|null
     */
    public function getMonthlyPrice()
    {
        return $this->monthly_price;
    }

    /**
     * Set depositAmount.
     *
     * @param string|null $depositAmount
     *
     * @return RentalProduct
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
     * Set maxRentalDays.
     *
     * @param int|null $maxRentalDays
     *
     * @return RentalProduct
     */
    public function setMaxRentalDays($maxRentalDays)
    {
        $this->max_rental_days = $maxRentalDays;

        return $this;
    }

    /**
     * Get maxRentalDays.
     *
     * @return int|null
     */
    public function getMaxRentalDays()
    {
        return $this->max_rental_days;
    }

    /**
     * Set minRentalDays.
     *
     * @param int $minRentalDays
     *
     * @return RentalProduct
     */
    public function setMinRentalDays($minRentalDays)
    {
        $this->min_rental_days = $minRentalDays;

        return $this;
    }

    /**
     * Get minRentalDays.
     *
     * @return int
     */
    public function getMinRentalDays()
    {
        return $this->min_rental_days;
    }

    /**
     * Set isRentalEnabled.
     *
     * @param bool $isRentalEnabled
     *
     * @return RentalProduct
     */
    public function setIsRentalEnabled($isRentalEnabled)
    {
        $this->is_rental_enabled = $isRentalEnabled;

        return $this;
    }

    /**
     * Get isRentalEnabled.
     *
     * @return bool
     */
    public function getIsRentalEnabled()
    {
        return $this->is_rental_enabled;
    }

    /**
     * Set autoApproval.
     *
     * @param bool $autoApproval
     *
     * @return RentalProduct
     */
    public function setAutoApproval($autoApproval)
    {
        $this->auto_approval = $autoApproval;

        return $this;
    }

    /**
     * Get autoApproval.
     *
     * @return bool
     */
    public function getAutoApproval()
    {
        return $this->auto_approval;
    }

    /**
     * Set preparationDays.
     *
     * @param int|null $preparationDays
     *
     * @return RentalProduct
     */
    public function setPreparationDays($preparationDays)
    {
        $this->preparation_days = $preparationDays;

        return $this;
    }

    /**
     * Get preparationDays.
     *
     * @return int|null
     */
    public function getPreparationDays()
    {
        return $this->preparation_days;
    }

    /**
     * Set rentalNote.
     *
     * @param string|null $rentalNote
     *
     * @return RentalProduct
     */
    public function setRentalNote($rentalNote)
    {
        $this->rental_note = $rentalNote;

        return $this;
    }

    /**
     * Get rentalNote.
     *
     * @return string|null
     */
    public function getRentalNote()
    {
        return $this->rental_note;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return RentalProduct
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
     * @return RentalProduct
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
     * @return RentalProduct
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
     * 指定日数でのレンタル料金を計算
     *
     * @param int $days
     * @return string|null
     */
    public function calculateRentalPrice($days)
    {
        if ($days <= 0) {
            return null;
        }

        $prices = [];

        // 日額計算
        if ($this->daily_price) {
            $prices[] = bcmul($this->daily_price, $days, 2);
        }

        // 週額計算
        if ($this->weekly_price) {
            $weeks = ceil($days / 7);
            $prices[] = bcmul($this->weekly_price, $weeks, 2);
        }

        // 月額計算
        if ($this->monthly_price) {
            $months = ceil($days / 30);
            $prices[] = bcmul($this->monthly_price, $months, 2);
        }

        // 最安値を返す
        return !empty($prices) ? min($prices) : null;
    }

    /**
     * レンタル可能期間内かチェック
     *
     * @param int $days
     * @return bool
     */
    public function isValidRentalPeriod($days)
    {
        if ($days < $this->min_rental_days) {
            return false;
        }

        if ($this->max_rental_days && $days > $this->max_rental_days) {
            return false;
        }

        return true;
    }

    /**
     * レンタル開始可能日を取得
     *
     * @param \DateTime $requestDate
     * @return \DateTime
     */
    public function getAvailableStartDate(\DateTime $requestDate = null)
    {
        $startDate = $requestDate ?: new \DateTime();
        
        // 準備日数を加算
        if ($this->preparation_days > 0) {
            $startDate->add(new \DateInterval('P' . $this->preparation_days . 'D'));
        }

        return $startDate;
    }

    /**
     * 料金設定が有効かチェック
     *
     * @return bool
     */
    public function hasPricingSetting()
    {
        return $this->daily_price || $this->weekly_price || $this->monthly_price;
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
        return $this->Product ? $this->Product->getName() . ' (レンタル設定)' : 'レンタル商品設定';
    }
}