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

/**
 * レンタル設定エンティティ
 * 
 * @ORM\Table(name="plg_rental_config")
 * @ORM\Entity(repositoryClass="Plugin\Rental\Repository\RentalConfigRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RentalConfig
{
    /**
     * @var int
     * 
     * @ORM\Column(name="id", type="integer", options={"comment":"設定ID"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="config_key", type="string", length=255, nullable=false, unique=true, options={"comment":"設定キー"})
     */
    private $config_key;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="config_value", type="text", nullable=true, options={"comment":"設定値"})
     */
    private $config_value;

    /**
     * @var string|null
     * 
     * @ORM\Column(name="config_description", type="text", nullable=true, options={"comment":"設定説明"})
     */
    private $config_description;

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

    // 設定キー定数
    const AUTO_APPROVAL = 'auto_approval';
    const MAX_RENTAL_DAYS = 'max_rental_days';
    const MIN_RENTAL_DAYS = 'min_rental_days';
    const REMINDER_DAYS = 'reminder_days';
    const OVERDUE_FEE_RATE = 'overdue_fee_rate';
    const DEPOSIT_REQUIRED = 'deposit_required';
    const BUSINESS_DAYS = 'business_days';
    const HOLIDAY_RENTAL = 'holiday_rental';
    const NOTIFICATION_EMAIL = 'notification_email';
    const TERMS_OF_SERVICE = 'terms_of_service';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
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
     * Set configKey.
     *
     * @param string $configKey
     *
     * @return RentalConfig
     */
    public function setConfigKey($configKey)
    {
        $this->config_key = $configKey;

        return $this;
    }

    /**
     * Get configKey.
     *
     * @return string
     */
    public function getConfigKey()
    {
        return $this->config_key;
    }

    /**
     * Set configValue.
     *
     * @param string|null $configValue
     *
     * @return RentalConfig
     */
    public function setConfigValue($configValue)
    {
        $this->config_value = $configValue;

        return $this;
    }

    /**
     * Get configValue.
     *
     * @return string|null
     */
    public function getConfigValue()
    {
        return $this->config_value;
    }

    /**
     * Set configDescription.
     *
     * @param string|null $configDescription
     *
     * @return RentalConfig
     */
    public function setConfigDescription($configDescription)
    {
        $this->config_description = $configDescription;

        return $this;
    }

    /**
     * Get configDescription.
     *
     * @return string|null
     */
    public function getConfigDescription()
    {
        return $this->config_description;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return RentalConfig
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
     * @return RentalConfig
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
     * 設定値をboolean型で取得
     *
     * @return bool
     */
    public function getBooleanValue()
    {
        return filter_var($this->config_value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * 設定値をint型で取得
     *
     * @return int
     */
    public function getIntValue()
    {
        return (int) $this->config_value;
    }

    /**
     * 設定値をfloat型で取得
     *
     * @return float
     */
    public function getFloatValue()
    {
        return (float) $this->config_value;
    }

    /**
     * 設定値をarray型で取得（JSON文字列の場合）
     *
     * @return array
     */
    public function getArrayValue()
    {
        $decoded = json_decode($this->config_value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 自動承認設定かどうか
     *
     * @return bool
     */
    public function isAutoApproval()
    {
        return $this->config_key === self::AUTO_APPROVAL && $this->getBooleanValue();
    }

    /**
     * 保証金必須設定かどうか
     *
     * @return bool
     */
    public function isDepositRequired()
    {
        return $this->config_key === self::DEPOSIT_REQUIRED && $this->getBooleanValue();
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
        return sprintf('%s: %s', $this->config_key, $this->config_value);
    }
}