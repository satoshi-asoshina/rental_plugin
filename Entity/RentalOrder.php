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
use Eccube\Entity\Product;

/**
 * レンタル商品設定エンティティ
 *
 * @ORM\Table(name="plg_rental_product")
 * @ORM\Entity(repositoryClass="Plugin\Rental\Repository\RentalProductRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RentalProduct extends AbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    private $Product;

    /**
     * @var boolean
     *
     * @ORM\Column(name="rental_enabled", type="boolean", options={"default":false})
     */
    private $rental_enabled = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="daily_rate", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $daily_rate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="weekly_rate", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $weekly_rate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="monthly_rate", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $monthly_rate;

    /**
     * @var integer|null
     *
     * @ORM\Column(name="max_rental_days", type="integer", options={"unsigned":true, "default":30}, nullable=true)
     */
    private $max_rental_days = 30;

    /**
     * @var integer|null
     *
     * @ORM\Column(name="min_rental_days", type="integer", options={"unsigned":true, "default":1}, nullable=true)
     */
    private $min_rental_days = 1;

    /**
     * @var integer|null
     *
     * @ORM\Column(name="stock_quantity", type="integer", options={"unsigned":true, "default":0}, nullable=true)
     */
    private $stock_quantity = 0;

    /**
     * @var string|null
     *
     * @ORM\Column(name="deposit_amount", type="decimal", precision=12, scale=2, options={"default":"0.00"}, nullable=true)
     */
    private $deposit_amount = '0.00';

    /**
     * @var string|null
     *
     * @ORM\Column(name="terms_of_service", type="text", nullable=true)
     */
    private $terms_of_service;

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
     * Set Product.
     *
     * @param Product|null $product
     *
     * @return RentalProduct
     */
    public function setProduct(Product $product = null)
    {
        $this->Product = $product;

        return $this;
    }

    /**
     * Get Product.
     *
     * @return Product|null
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * Set rental_enabled.
     *
     * @param boolean $rentalEnabled
     *
     * @return RentalProduct
     */
    public function setRentalEnabled($rentalEnabled)
    {
        $this->rental_enabled = $rentalEnabled;

        return $this;
    }

    /**
     * Get rental_enabled.
     *
     * @return boolean
     */
    public function isRentalEnabled()
    {
        return $this->rental_enabled;
    }

    /**
     * Get rental_enabled.
     *
     * @return boolean
     */
    public function getRentalEnabled()
    {
        return $this->rental_enabled;
    }

    /**
     * Set daily_rate.
     *
     * @param string|null $dailyRate
     *
     * @return RentalProduct
     */
    public function setDailyRate($dailyRate = null)
    {
        $this->daily_rate = $dailyRate;

        return $this;
    }

    /**
     * Get daily_rate.
     *
     * @return string|null
     */
    public function getDailyRate()
    {
        return $this->daily_rate;
    }

    /**
     * Set weekly_rate.
     *
     * @param string|null $weeklyRate
     *
     * @return RentalProduct
     */
    public function setWeeklyRate($weeklyRate = null)
    {
        $this->weekly_rate = $weeklyRate;

        return $this;
    }

    /**
     * Get weekly_rate.
     *
     * @return string|null
     */
    public function getWeeklyRate()
    {
        return $this->weekly_rate;
    }

    /**
     * Set monthly_rate.
     *
     * @param string|null $monthlyRate
     *
     * @return RentalProduct
     */
    public function setMonthlyRate($monthlyRate = null)
    {
        $this->monthly_rate = $monthlyRate;

        return $this;
    }

    /**
     * Get monthly_rate.
     *
     * @return string|null
     */
    public function getMonthlyRate()
    {
        return $this->monthly_rate;
    }

    /**
     * Set max_rental_days.
     *
     * @param integer|null $maxRentalDays
     *
     * @return RentalProduct
     */
    public function setMaxRentalDays($maxRentalDays = null)
    {
        $this->max_rental_days = $maxRentalDays;

        return $this;
    }

    /**
     * Get max_rental_days.
     *
     * @return integer|null
     */
    public function getMaxRentalDays()
    {
        return $this->max_rental_days;
    }

    /**
     * Set min_rental_days.
     *
     * @param integer|null $minRentalDays
     *
     * @return RentalProduct
     */
    public function setMinRentalDays($minRentalDays = null)
    {
        $this->min_rental_days = $minRentalDays;

        return $this;
    }

    /**
     * Get min_rental_days.
     *
     * @return integer|null
     */
    public function getMinRentalDays()
    {
        return $this->min_rental_days;
    }

    /**
     * Set stock_quantity.
     *
     * @param integer|null $stockQuantity
     *
     * @return RentalProduct
     */
    public function setStockQuantity($stockQuantity = null)
    {
        $this->stock_quantity = $stockQuantity;

        return $this;
    }

    /**
     * Get stock_quantity.
     *
     * @return integer|null
     */
    public function getStockQuantity()
    {
        return $this->stock_quantity;
    }

    /**
     * Set deposit_amount.
     *
     * @param string|null $depositAmount
     *
     * @return RentalProduct
     */
    public function setDepositAmount($depositAmount = null)
    {
        $this->deposit_amount = $depositAmount;

        return $this;
    }

    /**
     * Get deposit_amount.
     *
     * @return string|null
     */
    public function getDepositAmount()
    {
        return $this->deposit_amount;
    }

    /**
     * Set terms_of_service.
     *
     * @param string|null $termsOfService
     *
     * @return RentalProduct
     */
    public function setTermsOfService($termsOfService = null)
    {
        $this->terms_of_service = $termsOfService;

        return $this;
    }

    /**
     * Get terms_of_service.
     *
     * @return string|null
     */
    public function getTermsOfService()
    {
        return $this->terms_of_service;
    }

    /**
     * Set create_date.
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
     * @return RentalProduct
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
     * レンタル料金を期間に基づいて計算
     *
     * @param int $days レンタル日数
     * @return string 計算された料金
     */
    public function calculateRentalFee($days)
    {
        // 月額料金が設定されていて、30日以上の場合
        if ($this->monthly_rate && $days >= 30) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            
            $monthlyFee = bcmul($this->monthly_rate, $months, 2);
            $dailyFee = bcmul($this->daily_rate ?: '0', $remainingDays, 2);
            
            return bcadd($monthlyFee, $dailyFee, 2);
        }
        
        // 週額料金が設定されていて、7日以上の場合
        if ($this->weekly_rate && $days >= 7) {
            $weeks = floor($days / 7);
            $remainingDays = $days % 7;
            
            $weeklyFee = bcmul($this->weekly_rate, $weeks, 2);
            $dailyFee = bcmul($this->daily_rate ?: '0', $remainingDays, 2);
            
            return bcadd($weeklyFee, $dailyFee, 2);
        }
        
        // 日額料金での計算
        return bcmul($this->daily_rate ?: '0', $days, 2);
    }

    /**
     * 在庫が利用可能かチェック
     *
     * @param int $requestedQuantity 要求数量
     * @return bool
     */
    public function isStockAvailable($requestedQuantity = 1)
    {
        if ($this->stock_quantity === null) {
            return true; // 在庫管理しない場合
        }
        
        return $this->stock_quantity >= $requestedQuantity;
    }

    /**
     * レンタル期間が有効かチェック
     *
     * @param int $days レンタル日数
     * @return bool
     */
    public function isValidRentalPeriod($days)
    {
        if ($this->min_rental_days && $days < $this->min_rental_days) {
            return false;
        }
        
        if ($this->max_rental_days && $days > $this->max_rental_days) {
            return false;
        }
        
        return true;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->update_date = new \DateTime();
    }
}