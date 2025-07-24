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

/**
 * レンタル設定エンティティ
 *
 * @ORM\Table(name="plg_rental_config")
 * @ORM\Entity(repositoryClass="Plugin\Rental\Repository\RentalConfigRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RentalConfig extends AbstractEntity
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
     * @var string
     *
     * @ORM\Column(name="config_key", type="string", length=255)
     */
    private $config_key;

    /**
     * @var string|null
     *
     * @ORM\Column(name="config_value", type="text", nullable=true)
     */
    private $config_value;

    /**
     * @var string|null
     *
     * @ORM\Column(name="config_description", type="text", nullable=true)
     */
    private $config_description;

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
     * Set config_key.
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
     * Get config_key.
     *
     * @return string
     */
    public function getConfigKey()
    {
        return $this->config_key;
    }

    /**
     * Set config_value.
     *
     * @param string|null $configValue
     *
     * @return RentalConfig
     */
    public function setConfigValue($configValue = null)
    {
        $this->config_value = $configValue;

        return $this;
    }

    /**
     * Get config_value.
     *
     * @return string|null
     */
    public function getConfigValue()
    {
        return $this->config_value;
    }

    /**
     * Set config_description.
     *
     * @param string|null $configDescription
     *
     * @return RentalConfig
     */
    public function setConfigDescription($configDescription = null)
    {
        $this->config_description = $configDescription;

        return $this;
    }

    /**
     * Get config_description.
     *
     * @return string|null
     */
    public function getConfigDescription()
    {
        return $this->config_description;
    }

    /**
     * Set create_date.
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
     * @return RentalConfig
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
     * 設定値をarray型で取得（JSON形式）
     *
     * @return array
     */
    public function getArrayValue()
    {
        $decoded = json_decode($this->config_value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 配列を設定値として保存
     *
     * @param array $value
     * @return RentalConfig
     */
    public function setArrayValue(array $value)
    {
        $this->config_value = json_encode($value, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->update_date = new \DateTime();
    }
}