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

namespace Plugin\Rental\Exception;

/**
 * レンタル関連の基底例外クラス
 */
class RentalException extends \Exception
{
    /**
     * エラーコード定数
     */
    const ERROR_UNKNOWN = 0;
    const ERROR_VALIDATION = 1000;
    const ERROR_INVENTORY = 2000;
    const ERROR_PAYMENT = 3000;
    const ERROR_ORDER = 4000;
    const ERROR_SECURITY = 5000;
    const ERROR_CONFIGURATION = 6000;
    const ERROR_BUSINESS_LOGIC = 7000;
    const ERROR_EXTERNAL_API = 8000;
    const ERROR_DATA_ACCESS = 9000;

    /**
     * エラータイプ
     */
    const TYPE_USER = 'user';
    const TYPE_SYSTEM = 'system';
    const TYPE_BUSINESS = 'business';
    const TYPE_SECURITY = 'security';

    /**
     * エラーレベル
     */
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    /**
     * @var string エラータイプ
     */
    protected $errorType;

    /**
     * @var string エラーレベル
     */
    protected $errorLevel;

    /**
     * @var array エラーコンテキスト
     */
    protected $context;

    /**
     * @var string ユーザー向けメッセージ
     */
    protected $userMessage;

    /**
     * @var bool ログ出力フラグ
     */
    protected $shouldLog;

    /**
     * @var bool ユーザー通知フラグ
     */
    protected $shouldNotifyUser;

    /**
     * コンストラクタ
     *
     * @param string $message 例外メッセージ
     * @param int $code エラーコード
     * @param \Throwable|null $previous 前の例外
     * @param array $context エラーコンテキスト
     */
    public function __construct(
        string $message = '',
        int $code = self::ERROR_UNKNOWN,
        \Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->context = $context;
        $this->errorType = self::TYPE_SYSTEM;
        $this->errorLevel = self::LEVEL_ERROR;
        $this->userMessage = $message;
        $this->shouldLog = true;
        $this->shouldNotifyUser = true;
    }

    /**
     * エラータイプを設定
     *
     * @param string $type
     * @return self
     */
    public function setErrorType(string $type): self
    {
        $this->errorType = $type;
        return $this;
    }

    /**
     * エラータイプを取得
     *
     * @return string
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * エラーレベルを設定
     *
     * @param string $level
     * @return self
     */
    public function setErrorLevel(string $level): self
    {
        $this->errorLevel = $level;
        return $this;
    }

    /**
     * エラーレベルを取得
     *
     * @return string
     */
    public function getErrorLevel(): string
    {
        return $this->errorLevel;
    }

    /**
     * コンテキストを設定
     *
     * @param array $context
     * @return self
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * コンテキストを取得
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * コンテキストに追加
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * ユーザー向けメッセージを設定
     *
     * @param string $message
     * @return self
     */
    public function setUserMessage(string $message): self
    {
        $this->userMessage = $message;
        return $this;
    }

    /**
     * ユーザー向けメッセージを取得
     *
     * @return string
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * ログ出力フラグを設定
     *
     * @param bool $shouldLog
     * @return self
     */
    public function setShouldLog(bool $shouldLog): self
    {
        $this->shouldLog = $shouldLog;
        return $this;
    }

    /**
     * ログ出力が必要かどうか
     *
     * @return bool
     */
    public function shouldLog(): bool
    {
        return $this->shouldLog;
    }

    /**
     * ユーザー通知フラグを設定
     *
     * @param bool $shouldNotify
     * @return self
     */
    public function setShouldNotifyUser(bool $shouldNotify): self
    {
        $this->shouldNotifyUser = $shouldNotify;
        return $this;
    }

    /**
     * ユーザー通知が必要かどうか
     *
     * @return bool
     */
    public function shouldNotifyUser(): bool
    {
        return $this->shouldNotifyUser;
    }

    /**
     * エラー情報を配列で取得
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'user_message' => $this->userMessage,
            'code' => $this->getCode(),
            'type' => $this->errorType,
            'level' => $this->errorLevel,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * JSON形式で取得
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * システムエラーとして作成
     *
     * @param string $message
     * @param int $code
     * @param array $context
     * @return self
     */
    public static function createSystemError(string $message, int $code = self::ERROR_UNKNOWN, array $context = []): self
    {
        return (new self($message, $code, null, $context))
            ->setErrorType(self::TYPE_SYSTEM)
            ->setErrorLevel(self::LEVEL_ERROR)
            ->setUserMessage('システムエラーが発生しました。しばらく時間をおいて再度お試しください。');
    }

    /**
     * ビジネスエラーとして作成
     *
     * @param string $message
     * @param int $code
     * @param array $context
     * @return self
     */
    public static function createBusinessError(string $message, int $code = self::ERROR_BUSINESS_LOGIC, array $context = []): self
    {
        return (new self($message, $code, null, $context))
            ->setErrorType(self::TYPE_BUSINESS)
            ->setErrorLevel(self::LEVEL_WARNING)
            ->setUserMessage($message);
    }

    /**
     * セキュリティエラーとして作成
     *
     * @param string $message
     * @param int $code
     * @param array $context
     * @return self
     */
    public static function createSecurityError(string $message, int $code = self::ERROR_SECURITY, array $context = []): self
    {
        return (new self($message, $code, null, $context))
            ->setErrorType(self::TYPE_SECURITY)
            ->setErrorLevel(self::LEVEL_CRITICAL)
            ->setUserMessage('セキュリティエラーが発生しました。管理者にお問い合わせください。');
    }

    /**
     * ユーザーエラーとして作成
     *
     * @param string $message
     * @param int $code
     * @param array $context
     * @return self
     */
    public static function createUserError(string $message, int $code = self::ERROR_VALIDATION, array $context = []): self
    {
        return (new self($message, $code, null, $context))
            ->setErrorType(self::TYPE_USER)
            ->setErrorLevel(self::LEVEL_INFO)
            ->setUserMessage($message)
            ->setShouldLog(false);
    }

    /**
     * 警告として作成
     *
     * @param string $message
     * @param int $code
     * @param array $context
     * @return self
     */
    public static function createWarning(string $message, int $code = self::ERROR_UNKNOWN, array $context = []): self
    {
        return (new self($message, $code, null, $context))
            ->setErrorType(self::TYPE_SYSTEM)
            ->setErrorLevel(self::LEVEL_WARNING)
            ->setUserMessage($message);
    }

    /**
     * 重要なエラーとして作成
     *
     * @param string $message
     * @param int $code
     * @param array $context
     * @return self
     */
    public static function createCriticalError(string $message, int $code = self::ERROR_UNKNOWN, array $context = []): self
    {
        return (new self($message, $code, null, $context))
            ->setErrorType(self::TYPE_SYSTEM)
            ->setErrorLevel(self::LEVEL_CRITICAL)
            ->setUserMessage('重大なエラーが発生しました。管理者にお問い合わせください。');
    }

    /**
     * 例外から作成
     *
     * @param \Throwable $exception
     * @param string|null $userMessage
     * @param array $context
     * @return self
     */
    public static function createFromException(\Throwable $exception, string $userMessage = null, array $context = []): self
    {
        $message = $exception->getMessage();
        $code = $exception->getCode();
        
        $rentalException = new self($message, $code, $exception, $context);
        
        if ($userMessage) {
            $rentalException->setUserMessage($userMessage);
        }
        
        return $rentalException;
    }

    /**
     * エラーコードからカテゴリを取得
     *
     * @return string
     */
    public function getErrorCategory(): string
    {
        $code = $this->getCode();
        
        if ($code >= 1000 && $code < 2000) return 'validation';
        if ($code >= 2000 && $code < 3000) return 'inventory';
        if ($code >= 3000 && $code < 4000) return 'payment';
        if ($code >= 4000 && $code < 5000) return 'order';
        if ($code >= 5000 && $code < 6000) return 'security';
        if ($code >= 6000 && $code < 7000) return 'configuration';
        if ($code >= 7000 && $code < 8000) return 'business_logic';
        if ($code >= 8000 && $code < 9000) return 'external_api';
        if ($code >= 9000 && $code < 10000) return 'data_access';
        
        return 'unknown';
    }

    /**
     * リカバリ可能かどうか
     *
     * @return bool
     */
    public function isRecoverable(): bool
    {
        return in_array($this->errorType, [self::TYPE_USER, self::TYPE_BUSINESS]) &&
               $this->errorLevel !== self::LEVEL_CRITICAL;
    }

    /**
     * 自動リトライ可能かどうか
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        $retryableCategories = ['external_api', 'data_access'];
        return in_array($this->getErrorCategory(), $retryableCategories) &&
               $this->errorLevel !== self::LEVEL_CRITICAL;
    }

    /**
     * デバッグ情報を取得
     *
     * @return array
     */
    public function getDebugInfo(): array
    {
        return [
            'exception_class' => get_class($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'category' => $this->getErrorCategory(),
            'type' => $this->errorType,
            'level' => $this->errorLevel,
            'recoverable' => $this->isRecoverable(),
            'retryable' => $this->isRetryable(),
            'context' => $this->context,
            'stack_trace' => $this->getTrace(),
        ];
    }
}