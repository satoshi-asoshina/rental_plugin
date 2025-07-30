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
    const ERROR_CODE_GENERAL = 1000;
    const ERROR_CODE_VALIDATION = 1001;
    const ERROR_CODE_INVENTORY = 1002;
    const ERROR_CODE_PAYMENT = 1003;
    const ERROR_CODE_PERMISSION = 1004;
    const ERROR_CODE_CONFIG = 1005;
    const ERROR_CODE_NETWORK = 1006;
    const ERROR_CODE_DATABASE = 1007;

    /**
     * エラー詳細情報
     *
     * @var array
     */
    private $details = [];

    /**
     * ユーザーフレンドリーメッセージ
     *
     * @var string|null
     */
    private $userMessage;

    /**
     * エラー発生箇所の詳細情報
     *
     * @var array
     */
    private $context = [];

    /**
     * コンストラクタ
     *
     * @param string $message エラーメッセージ
     * @param int $code エラーコード
     * @param \Throwable|null $previous 前の例外
     * @param array $details エラー詳細情報
     * @param string|null $userMessage ユーザー向けメッセージ
     * @param array $context コンテキスト情報
     */
    public function __construct(
        string $message = '',
        int $code = self::ERROR_CODE_GENERAL,
        \Throwable $previous = null,
        array $details = [],
        string $userMessage = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->details = $details;
        $this->userMessage = $userMessage;
        $this->context = $context;
    }

    /**
     * エラー詳細情報を取得
     *
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * エラー詳細情報を設定
     *
     * @param array $details
     * @return self
     */
    public function setDetails(array $details): self
    {
        $this->details = $details;
        return $this;
    }

    /**
     * 詳細情報を追加
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addDetail(string $key, $value): self
    {
        $this->details[$key] = $value;
        return $this;
    }

    /**
     * ユーザー向けメッセージを取得
     *
     * @return string
     */
    public function getUserMessage(): string
    {
        return $this->userMessage ?: $this->getMessage();
    }

    /**
     * ユーザー向けメッセージを設定
     *
     * @param string $userMessage
     * @return self
     */
    public function setUserMessage(string $userMessage): self
    {
        $this->userMessage = $userMessage;
        return $this;
    }

    /**
     * コンテキスト情報を取得
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * コンテキスト情報を設定
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
     * コンテキスト情報を追加
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
     * エラー情報を配列で取得
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'user_message' => $this->getUserMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'details' => $this->getDetails(),
            'context' => $this->getContext(),
            'trace' => $this->getTraceAsString(),
        ];
    }

    /**
     * ログ出力用の情報を取得
     *
     * @return array
     */
    public function getLogContext(): array
    {
        return [
            'exception_class' => get_class($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'details' => $this->getDetails(),
            'context' => $this->getContext(),
        ];
    }

    /**
     * エラーコードが特定の種類かどうかチェック
     *
     * @param int $errorType
     * @return bool
     */
    public function isErrorType(int $errorType): bool
    {
        return $this->getCode() === $errorType;
    }

    /**
     * 重要度の高いエラーかどうか判定
     *
     * @return bool
     */
    public function isCritical(): bool
    {
        $criticalCodes = [
            self::ERROR_CODE_DATABASE,
            self::ERROR_CODE_NETWORK,
            self::ERROR_CODE_PAYMENT,
        ];

        return in_array($this->getCode(), $criticalCodes);
    }

    /**
     * ユーザーに表示すべきエラーかどうか判定
     *
     * @return bool
     */
    public function isUserFriendly(): bool
    {
        $userFriendlyCodes = [
            self::ERROR_CODE_VALIDATION,
            self::ERROR_CODE_INVENTORY,
            self::ERROR_CODE_PERMISSION,
        ];

        return in_array($this->getCode(), $userFriendlyCodes);
    }

    /**
     * 一般的なエラー作成
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function general(string $message, array $context = []): self
    {
        return new static($message, self::ERROR_CODE_GENERAL, null, [], null, $context);
    }

    /**
     * 設定エラー作成
     *
     * @param string $message
     * @param array $details
     * @return static
     */
    public static function config(string $message, array $details = []): self
    {
        return new static(
            $message, 
            self::ERROR_CODE_CONFIG, 
            null, 
            $details, 
            '設定に問題があります。管理者にお問い合わせください。'
        );
    }

    /**
     * 権限エラー作成
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function permission(string $message, array $context = []): self
    {
        return new static(
            $message, 
            self::ERROR_CODE_PERMISSION, 
            null, 
            [], 
            'この操作を実行する権限がありません。',
            $context
        );
    }

    /**
     * ネットワークエラー作成
     *
     * @param string $message
     * @param \Throwable|null $previous
     * @return static
     */
    public static function network(string $message, \Throwable $previous = null): self
    {
        return new static(
            $message, 
            self::ERROR_CODE_NETWORK, 
            $previous, 
            [], 
            '通信エラーが発生しました。しばらく時間をおいて再度お試しください。'
        );
    }

    /**
     * データベースエラー作成
     *
     * @param string $message
     * @param \Throwable|null $previous
     * @return static
     */
    public static function database(string $message, \Throwable $previous = null): self
    {
        return new static(
            $message, 
            self::ERROR_CODE_DATABASE, 
            $previous, 
            [], 
            'システムエラーが発生しました。管理者にお問い合わせください。'
        );
    }
}