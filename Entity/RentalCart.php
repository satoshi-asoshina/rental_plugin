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
use Eccube\Entity\Customer;

/**
 * レンタルカートエンティティ
 *
 * @ORM\Table(name="plg_rental_cart")
 * @ORM\Entity(repositoryClass="Plugin\Rental\Repository\RentalCartRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RentalCart extends AbstractEntity
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
     * @var string|null
     *
     * @ORM\Column(name="session_id", type="string", length=128, nullable=true)
     */
    private $session_id;

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
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    private $Product;

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
     * @ORM\Column(name="options", type="text", nullable=true)
     */
    private $options;

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
     * Set session_id.
     *
     * @param string|null $sessionId
     *
     * @return RentalCart
     */
    public function setSessionId($sessionId = null)
    {
        $this->session_id = $sessionId;

        return $this;
    }

    /**
     * Get session_id.
     *
     * @return string|null
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * Set Customer.
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
     * Get Customer.
     *
     * @return Customer|null
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * Set Product.
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
     * Get Product.
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * Set quantity.
     *
     * @param integer $quantity
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
     * @return RentalCart
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
     * @return RentalCart
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
     * Set rental_fee.
     *
     * @param string $rentalFee
     *
     * @return RentalCart
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
     * @return RentalCart
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
     * Set options.
     *
     * @param string|null $options
     *
     * @return RentalCart
     */
    public function setOptions($options = null)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get options.
     *
     * @return string|null
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set create_date.
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
     * @return RentalCart
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
     * 小計を取得
     *
     * @return string
     */
    public function getSubtotal()
    {
        $rentalFee = bcmul($this->rental_fee ?: '0', $this->quantity, 2);
        $depositFee = bcmul($this->deposit_fee ?: '0', $this->quantity, 2);
        
        return bcadd($rentalFee, $depositFee, 2);
    }

    /**
     * オプション情報を配列で取得
     *
     * @return array
     */
    public function getOptionsArray()
    {
        if (!$this->options) {
            return [];
        }
        
        $decoded = json_decode($this->options, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * オプション情報を配列で設定
     *
     * @param array $options
     * @return RentalCart
     */
    public function setOptionsArray(array $options)
    {
        $this->options = json_encode($options, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    /**
     * 特定のオプション値を取得
     *
     * @param string $key オプションキー
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        $options = $this->getOptionsArray();
        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * 特定のオプション値を設定
     *
     * @param string $key オプションキー
     * @param mixed $value 設定値
     * @return RentalCart
     */
    public function setOption($key, $value)
    {
        $options = $this->getOptionsArray();
        $options[$key] = $value;
        $this->setOptionsArray($options);
        
        return $this;
    }

    /**
     * カートアイテムの有効性をチェック
     *
     * @return bool
     */
    public function isValid()
    {
        // 必須項目チェック
        if (!$this->Product || !$this->rental_start_date || !$this->rental_end_date) {
            return false;
        }
        
        // 期間チェック
        if ($this->rental_start_date >= $this->rental_end_date) {
            return false;
        }
        
        // 数量チェック
        if ($this->quantity <= 0) {
            return false;
        }
        
        return true;
    }

    /**
     * レンタル開始日が過去かチェック
     *
     * @return bool
     */
    public function isStartDateExpired()
    {
        if (!$this->rental_start_date) {
            return false;
        }
        
        $today = new \DateTime();
        $today->setTime(0, 0, 0); // 時間をリセット
        
        return $this->rental_start_date < $today;
    }

    /**
     * カートアイテムのユニークキーを生成
     *
     * @return string
     */
    public function generateUniqueKey()
    {
        $parts = [
            $this->Product ? $this->Product->getId() : 'no-product',
            $this->rental_start_date ? $this->rental_start_date->format('Y-m-d') : 'no-start',
            $this->rental_end_date ? $this->rental_end_date->format('Y-m-d') : 'no-end',
        ];
        
        // オプションがある場合は含める
        $options = $this->getOptionsArray();
        if (!empty($options)) {
            ksort($options); // キーでソートして一貫性を保つ
            $parts[] = md5(json_encode($options));
        }
        
        return implode('-', $parts);
    }

    /**
     * 同じカート商品かどうかチェック
     *
     * @param RentalCart $other
     * @return bool
     */
    public function isSameItem(RentalCart $other)
    {
        return $this->generateUniqueKey() === $other->generateUniqueKey();
    }

    /**
     * セッションまたは顧客に属するかチェック
     *
     * @param string|null $sessionId
     * @param Customer|null $customer
     * @return bool
     */
    public function belongsTo($sessionId = null, Customer $customer = null)
    {
        // 顧客ログイン済みの場合
        if ($customer) {
            return $this->Customer && $this->Customer->getId() === $customer->getId();
        }
        
        // ゲストセッションの場合
        if ($sessionId) {
            return $this->session_id === $sessionId && !$this->Customer;
        }
        
        return false;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->update_date = new \DateTime();
    }
}