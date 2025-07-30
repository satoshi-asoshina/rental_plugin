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
use Plugin\Rental\Entity\RentalConfig;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * レンタル設定データアクセス Repository
 */
class RentalConfigRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RentalConfig::class);
    }

    /**
     * 設定キーで設定を取得
     *
     * @param string $key 設定キー
     * @return RentalConfig|null
     */
    public function findByKey($key)
    {
        return $this->findOneBy(['config_key' => $key]);
    }

    /**
     * 設定値を取得（設定が存在しない場合はデフォルト値を返す）
     *
     * @param string $key 設定キー
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $config = $this->findByKey($key);
        return $config ? $config->getConfigValue() : $default;
    }

    /**
     * 設定値をboolean型で取得
     *
     * @param string $key 設定キー
     * @param bool $default デフォルト値
     * @return bool
     */
    public function getBoolean($key, $default = false)
    {
        $config = $this->findByKey($key);
        return $config ? $config->getBooleanValue() : $default;
    }

    /**
     * 設定値をint型で取得
     *
     * @param string $key 設定キー
     * @param int $default デフォルト値
     * @return int
     */
    public function getInt($key, $default = 0)
    {
        $config = $this->findByKey($key);
        return $config ? $config->getIntValue() : $default;
    }

    /**
     * 設定値をfloat型で取得
     *
     * @param string $key 設定キー
     * @param float $default デフォルト値
     * @return float
     */
    public function getFloat($key, $default = 0.0)
    {
        $config = $this->findByKey($key);
        return $config ? $config->getFloatValue() : $default;
    }

    /**
     * 設定値をarray型で取得
     *
     * @param string $key 設定キー
     * @param array $default デフォルト値
     * @return array
     */
    public function getArray($key, $default = [])
    {
        $config = $this->findByKey($key);
        return $config ? $config->getArrayValue() : $default;
    }

    /**
     * 設定を保存または更新
     *
     * @param string $key 設定キー
     * @param mixed $value 設定値
     * @param string|null $description 説明
     * @return RentalConfig
     */
    public function set($key, $value, $description = null)
    {
        $config = $this->findByKey($key);
        
        if (!$config) {
            $config = new RentalConfig();
            $config->setConfigKey($key);
        }
        
        $config->setConfigValue($value);
        
        if ($description !== null) {
            $config->setConfigDescription($description);
        }
        
        $this->getEntityManager()->persist($config);
        $this->getEntityManager()->flush();
        
        return $config;
    }

    /**
     * 複数の設定を一括保存
     *
     * @param array $configs 設定配列 [key => value, ...]
     * @return void
     */
    public function setMultiple(array $configs)
    {
        foreach ($configs as $key => $value) {
            $config = $this->findByKey($key);
            
            if (!$config) {
                $config = new RentalConfig();
                $config->setConfigKey($key);
            }
            
            $config->setConfigValue($value);
            $this->getEntityManager()->persist($config);
        }
        
        $this->getEntityManager()->flush();
    }

    /**
     * 設定を削除
     *
     * @param string $key 設定キー
     * @return bool
     */
    public function remove($key)
    {
        $config = $this->findByKey($key);
        
        if ($config) {
            $this->getEntityManager()->remove($config);
            $this->getEntityManager()->flush();
            return true;
        }
        
        return false;
    }

    /**
     * 全設定を配列で取得
     *
     * @return array
     */
    public function getAllAsArray()
    {
        $configs = $this->findAll();
        $result = [];
        
        foreach ($configs as $config) {
            $result[$config->getConfigKey()] = $config->getConfigValue();
        }
        
        return $result;
    }

    /**
     * 指定したキーの設定群を取得
     *
     * @param string $prefix キーのプレフィックス
     * @return array
     */
    public function findByKeyPrefix($prefix)
    {
        return $this->createQueryBuilder('c')
            ->where('c.config_key LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * 自動承認設定を取得
     *
     * @return bool
     */
    public function isAutoApprovalEnabled()
    {
        return $this->getBoolean('auto_approval', false);
    }

    /**
     * 最大レンタル日数を取得
     *
     * @return int
     */
    public function getMaxRentalDays()
    {
        return $this->getInt('max_rental_days', 30);
    }

    /**
     * 最小レンタル日数を取得
     *
     * @return int
     */
    public function getMinRentalDays()
    {
        return $this->getInt('min_rental_days', 1);
    }

    /**
     * リマインダー日数を取得
     *
     * @return int
     */
    public function getReminderDays()
    {
        return $this->getInt('reminder_days', 3);
    }

    /**
     * 延滞料金率を取得
     *
     * @return float
     */
    public function getOverdueFeeRate()
    {
        return $this->getFloat('overdue_fee_rate', 0.1);
    }

    /**
     * 保証金必須設定を取得
     *
     * @return bool
     */
    public function isDepositRequired()
    {
        return $this->getBoolean('deposit_required', false);
    }

    /**
     * 営業日設定を取得
     *
     * @return array
     */
    public function getBusinessDays()
    {
        $businessDays = $this->get('business_days', '1,2,3,4,5');
        return explode(',', $businessDays);
    }

    /**
     * 休日レンタル許可設定を取得
     *
     * @return bool
     */
    public function isHolidayRentalEnabled()
    {
        return $this->getBoolean('holiday_rental', true);
    }

    /**
     * 通知メールアドレスを取得
     *
     * @return string
     */
    public function getNotificationEmail()
    {
        return $this->get('notification_email', '');
    }

    /**
     * 利用規約を取得
     *
     * @return string
     */
    public function getTermsOfService()
    {
        return $this->get('terms_of_service', '');
    }

    /**
     * 設定が存在するかチェック
     *
     * @param string $key 設定キー
     * @return bool
     */
    public function exists($key)
    {
        return $this->findByKey($key) !== null;
    }

    /**
     * 設定のバックアップを作成
     *
     * @return array
     */
    public function backup()
    {
        $configs = $this->findAll();
        $backup = [];
        
        foreach ($configs as $config) {
            $backup[] = [
                'config_key' => $config->getConfigKey(),
                'config_value' => $config->getConfigValue(),
                'config_description' => $config->getConfigDescription(),
                'create_date' => $config->getCreateDate()->format('Y-m-d H:i:s'),
                'update_date' => $config->getUpdateDate()->format('Y-m-d H:i:s'),
            ];
        }
        
        return $backup;
    }

    /**
     * バックアップから設定を復元
     *
     * @param array $backup バックアップデータ
     * @return void
     */
    public function restore(array $backup)
    {
        foreach ($backup as $configData) {
            $config = $this->findByKey($configData['config_key']);
            
            if (!$config) {
                $config = new RentalConfig();
                $config->setConfigKey($configData['config_key']);
            }
            
            $config->setConfigValue($configData['config_value']);
            $config->setConfigDescription($configData['config_description']);
            
            $this->getEntityManager()->persist($config);
        }
        
        $this->getEntityManager()->flush();
    }

    /**
     * 設定のキャッシュをクリア
     *
     * @return void
     */
    public function clearCache()
    {
        $this->getEntityManager()->clear();
    }
}