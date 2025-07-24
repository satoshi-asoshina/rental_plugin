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
use Eccube\Entity\Member;

/**
 * レンタルログエンティティ
 *
 * @ORM\Table(name="plg_rental_log")
 * @ORM\Entity(repositoryClass="Plugin\Rental\Repository\RentalLogRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RentalLog extends AbstractEntity
{
    // ログレベル定数
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';

    // ログタイプ定数
    const TYPE_ORDER_CREATE = 'order_create';
    const TYPE_ORDER_UPDATE = 'order_update';
    const TYPE_ORDER_CANCEL = 'order_cancel';
    const TYPE_ORDER_RETURN = 'order_return';
    const TYPE_INVENTORY_UPDATE = 'inventory_update';
    const TYPE_PAYMENT_PROCESS = 'payment_process';
    const TYPE_NOTIFICATION_SEND = 'notification_send';
    const TYPE_CONFIG_UPDATE = 'config_update';
    const TYPE_SYSTEM_ERROR = 'system_error';
    const TYPE_SECURITY_EVENT = 'security_event';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var RentalOrder|null
     *
     * @ORM\ManyToOne(targetEntity="Plugin\Rental\Entity\RentalOrder")
     * @ORM\JoinColumn(name="rental_order_id", referencedColumnName="id", nullable=true)
     */
    private $RentalOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="log_type", type="string", length=50)
     */
    private $log_type;

    /**
     * @var string
     *
     * @ORM\Column(name="log_level", type="string", length=20, options={"default":"INFO"})
     */
    private $log_level = self::LEVEL_INFO;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    /**
     * @var string|null
     *
     * @ORM\Column(name="context", type="text", nullable=true)
     */
    private $context;

    /**
     * @var Member|null
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private $User;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip_address", type="string", length=45, nullable=true)
     */
    private $ip_address;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_agent", type="text", nullable=true)
     */
    private $user_agent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $create_date;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->create_date = new \DateTime();
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
     * Set RentalOrder.
     *
     * @param RentalOrder|null $rentalOrder
     *
     * @return RentalLog
     */
    public function setRentalOrder(RentalOrder $rentalOrder = null)
    {
        $this->RentalOrder = $rentalOrder;

        return $this;
    }

    /**
     * Get RentalOrder.
     *
     * @return RentalOrder|null
     */
    public function getRentalOrder()
    {
        return $this->RentalOrder;
    }

    /**
     * Set log_type.
     *
     * @param string $logType
     *
     * @return RentalLog
     */
    public function setLogType($logType)
    {
        $this->log_type = $logType;

        return $this;
    }

    /**
     * Get log_type.
     *
     * @return string
     */
    public function getLogType()
    {
        return $this->log_type;
    }

    /**
     * Set log_level.
     *
     * @param string $logLevel
     *
     * @return RentalLog
     */
    public function setLogLevel($logLevel)
    {
        $this->log_level = $logLevel;

        return $this;
    }

    /**
     * Get log_level.
     *
     * @return string
     */
    public function getLogLevel()
    {
        return $this->log_level;
    }

    /**
     * Set message.
     *
     * @param string $message
     *
     * @return RentalLog
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set context.
     *
     * @param string|null $context
     *
     * @return RentalLog
     */
    public function setContext($context = null)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get context.
     *
     * @return string|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set User.
     *
     * @param Member|null $user
     *
     * @return RentalLog
     */
    public function setUser(Member $user = null)
    {
        $this->User = $user;

        return $this;
    }

    /**
     * Get User.
     *
     * @return Member|null
     */
    public function getUser()
    {
        return $this->User;
    }

    /**
     * Set ip_address.
     *
     * @param string|null $ipAddress
     *
     * @return RentalLog
     */
    public function setIpAddress($ipAddress = null)
    {
        $this->ip_address = $ipAddress;

        return $this;
    }

    /**
     * Get ip_address.
     *
     * @return string|null
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * Set user_agent.
     *
     * @param string|null $userAgent
     *
     * @return RentalLog
     */
    public function setUserAgent($userAgent = null)
    {
        $this->user_agent = $userAgent;

        return $this;
    }

    /**
     * Get user_agent.
     *
     * @return string|null
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * Set create_date.
     *
     * @param \DateTime $createDate
     *
     * @return RentalLog
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
     * コンテキスト情報を配列で取得
     *
     * @return array
     */
    public function getContextArray()
    {
        if (!$this->context) {
            return [];
        }
        
        $decoded = json_decode($this->context, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * コンテキスト情報を配列で設定
     *
     * @param array $context
     * @return RentalLog
     */
    public function setContextArray(array $context)
    {
        $this->context = json_encode($context, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    /**
     * 特定のコンテキスト値を取得
     *
     * @param string $key コンテキストキー
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public function getContextValue($key, $default = null)
    {
        $context = $this->getContextArray();
        return isset($context[$key]) ? $context[$key] : $default;
    }

    /**
     * 特定のコンテキスト値を設定
     *
     * @param string $key コンテキストキー
     * @param mixed $value 設定値
     * @return RentalLog
     */
    public function setContextValue($key, $value)
    {
        $context = $this->getContextArray();
        $context[$key] = $value;
        $this->setContextArray($context);
        
        return $this;
    }

    /**
     * ログレベルの重要度を数値で取得
     *
     * @return int
     */
    public function getLogLevelPriority()
    {
        $priorities = [
            self::LEVEL_DEBUG => 1,
            self::LEVEL_INFO => 2,
            self::LEVEL_WARNING => 3,
            self::LEVEL_ERROR => 4,
            self::LEVEL_CRITICAL => 5,
        ];
        
        return $priorities[$this->log_level] ?? 0;
    }

    /**
     * ログレベルが指定レベル以上かチェック
     *
     * @param string $level 比較するログレベル
     * @return bool
     */
    public function isLevelAbove($level)
    {
        $priorities = [
            self::LEVEL_DEBUG => 1,
            self::LEVEL_INFO => 2,
            self::LEVEL_WARNING => 3,
            self::LEVEL_ERROR => 4,
            self::LEVEL_CRITICAL => 5,
        ];
        
        $currentPriority = $priorities[$this->log_level] ?? 0;
        $comparePriority = $priorities[$level] ?? 0;
        
        return $currentPriority >= $comparePriority;
    }

    /**
     * ログタイプの表示名を取得
     *
     * @return string
     */
    public function getLogTypeName()
    {
        $typeNames = [
            self::TYPE_ORDER_CREATE => '注文作成',
            self::TYPE_ORDER_UPDATE => '注文更新',
            self::TYPE_ORDER_CANCEL => '注文キャンセル',
            self::TYPE_ORDER_RETURN => '返却処理',
            self::TYPE_INVENTORY_UPDATE => '在庫更新',
            self::TYPE_PAYMENT_PROCESS => '決済処理',
            self::TYPE_NOTIFICATION_SEND => '通知送信',
            self::TYPE_CONFIG_UPDATE => '設定更新',
            self::TYPE_SYSTEM_ERROR => 'システムエラー',
            self::TYPE_SECURITY_EVENT => 'セキュリティイベント',
        ];
        
        return $typeNames[$this->log_type] ?? $this->log_type;
    }

    /**
     * ログレベルの表示名を取得
     *
     * @return string
     */
    public function getLogLevelName()
    {
        $levelNames = [
            self::LEVEL_DEBUG => 'デバッグ',
            self::LEVEL_INFO => '情報',
            self::LEVEL_WARNING => '警告',
            self::LEVEL_ERROR => 'エラー',
            self::LEVEL_CRITICAL => '重大エラー',
        ];
        
        return $levelNames[$this->log_level] ?? $this->log_level;
    }

    /**
     * ログレベルに応じたCSSクラスを取得
     *
     * @return string
     */
    public function getLogLevelCssClass()
    {
        $cssClasses = [
            self::LEVEL_DEBUG => 'text-muted',
            self::LEVEL_INFO => 'text-info',
            self::LEVEL_WARNING => 'text-warning',
            self::LEVEL_ERROR => 'text-danger',
            self::LEVEL_CRITICAL => 'text-danger font-weight-bold',
        ];
        
        return $cssClasses[$this->log_level] ?? 'text-secondary';
    }

    /**
     * エラーレベルかどうかチェック
     *
     * @return bool
     */
    public function isError()
    {
        return in_array($this->log_level, [self::LEVEL_ERROR, self::LEVEL_CRITICAL]);
    }

    /**
     * 警告レベル以上かどうかチェック
     *
     * @return bool
     */
    public function isWarningOrAbove()
    {
        return $this->isLevelAbove(self::LEVEL_WARNING);
    }

    /**
     * ユーザー情報の表示名を取得
     *
     * @return string
     */
    public function getUserDisplayName()
    {
        if ($this->User) {
            return $this->User->getName() ?: $this->User->getLoginId();
        }
        
        return 'システム';
    }

    /**
     * ブラウザ情報を解析して取得
     *
     * @return array
     */
    public function getParsedUserAgent()
    {
        if (!$this->user_agent) {
            return ['browser' => '不明', 'os' => '不明'];
        }
        
        // 簡易的なユーザーエージェント解析
        $browser = '不明';
        $os = '不明';
        
        // ブラウザ判定
        if (strpos($this->user_agent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($this->user_agent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($this->user_agent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($this->user_agent, 'Edge') !== false) {
            $browser = 'Edge';
        }
        
        // OS判定
        if (strpos($this->user_agent, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (strpos($this->user_agent, 'Mac OS') !== false) {
            $os = 'macOS';
        } elseif (strpos($this->user_agent, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (strpos($this->user_agent, 'iOS') !== false) {
            $os = 'iOS';
        } elseif (strpos($this->user_agent, 'Android') !== false) {
            $os = 'Android';
        }
        
        return ['browser' => $browser, 'os' => $os];
    }

    /**
     * ログの詳細表示用メッセージを取得
     *
     * @return string
     */
    public function getDetailMessage()
    {
        $message = $this->message;
        
        // コンテキストがある場合は追加情報を含める
        $context = $this->getContextArray();
        if (!empty($context)) {
            $contextString = [];
            foreach ($context as $key => $value) {
                if (is_scalar($value)) {
                    $contextString[] = "{$key}: {$value}";
                }
            }
            
            if (!empty($contextString)) {
                $message .= ' [' . implode(', ', $contextString) . ']';
            }
        }
        
        return $message;
    }

    /**
     * 静的メソッド：ログレベル一覧を取得
     *
     * @return array
     */
    public static function getLogLevels()
    {
        return [
            self::LEVEL_DEBUG => 'デバッグ',
            self::LEVEL_INFO => '情報',
            self::LEVEL_WARNING => '警告',
            self::LEVEL_ERROR => 'エラー',
            self::LEVEL_CRITICAL => '重大エラー',
        ];
    }

    /**
     * 静的メソッド：ログタイプ一覧を取得
     *
     * @return array
     */
    public static function getLogTypes()
    {
        return [
            self::TYPE_ORDER_CREATE => '注文作成',
            self::TYPE_ORDER_UPDATE => '注文更新',
            self::TYPE_ORDER_CANCEL => '注文キャンセル',
            self::TYPE_ORDER_RETURN => '返却処理',
            self::TYPE_INVENTORY_UPDATE => '在庫更新',
            self::TYPE_PAYMENT_PROCESS => '決済処理',
            self::TYPE_NOTIFICATION_SEND => '通知送信',
            self::TYPE_CONFIG_UPDATE => '設定更新',
            self::TYPE_SYSTEM_ERROR => 'システムエラー',
            self::TYPE_SECURITY_EVENT => 'セキュリティイベント',
        ];
    }
}