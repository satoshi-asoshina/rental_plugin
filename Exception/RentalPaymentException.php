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
 * レンタル決済エラー例外クラス
 */
class RentalPaymentException extends RentalException
{
    /**
     * 決済エラーの種類
     */
    const PAYMENT_TYPE_CARD_DECLINED = 'card_declined';
    const PAYMENT_TYPE_INSUFFICIENT_FUNDS = 'insufficient_funds';
    const PAYMENT_TYPE_EXPIRED_CARD = 'expired_card';
    const PAYMENT_TYPE_INVALID_CARD = 'invalid_card';
    const PAYMENT_TYPE_NETWORK_ERROR = 'network_error';
    const PAYMENT_TYPE_GATEWAY_ERROR = 'gateway_error';
    const PAYMENT_TYPE_AUTHENTICATION_FAILED = 'authentication_failed';
    const PAYMENT_TYPE_LIMIT_EXCEEDED = 'limit_exceeded';
    const PAYMENT_TYPE_CURRENCY_NOT_SUPPORTED = 'currency_not_supported';
    const PAYMENT_TYPE_REFUND_FAILED = 'refund_failed';
    const PAYMENT_TYPE_DUPLICATE_TRANSACTION = 'duplicate_transaction';
    const PAYMENT_TYPE_TIMEOUT = 'timeout';

    /**
     * 決済方法
     *
     * @var string|null
     */
    private $paymentMethod;

    /**
     * 決済エラーの種類
     *
     * @var string
     */
    private $paymentType;

    /**
     * トランザクションID
     *
     * @var string|null
     */
    private $transactionId;

    /**
     * 決済ゲートウェイからのエラーコード
     *
     * @var string|null
     */
    private $gatewayErrorCode;

    /**
     * 決済ゲートウェイからのエラーメッセージ
     *
     * @var string|null
     */
    private $gatewayErrorMessage;

    /**
     * 決済金額
     *
     * @var float|null
     */
    private $amount;

    /**
     * 通貨コード
     *
     * @var string|null
     */
    private $currency;

    /**
     * リトライ可能かどうか
     *
     * @var bool
     */
    private $retryable;

    /**
     * コンストラクタ
     *
     * @param string $message エラーメッセージ
     * @param string $paymentType 決済エラー種類
     * @param string|null $paymentMethod 決済方法
     * @param string|null $transactionId トランザクションID
     * @param string|null $gatewayErrorCode ゲートウェイエラーコード
     * @param string|null $gatewayErrorMessage ゲートウェイエラーメッセージ
     * @param float|null $amount 決済金額
     * @param string|null $currency 通貨コード
     * @param bool $retryable リトライ可能フラグ
     * @param \Throwable|null $previous 前の例外
     */
    public function __construct(
        string $message,
        string $paymentType = self::PAYMENT_TYPE_GATEWAY_ERROR,
        string $paymentMethod = null,
        string $transactionId = null,
        string $gatewayErrorCode = null,
        string $gatewayErrorMessage = null,
        float $amount = null,
        string $currency = 'JPY',
        bool $retryable = false,
        \Throwable $previous = null
    ) {
        $this->paymentType = $paymentType;
        $this->paymentMethod = $paymentMethod;
        $this->transactionId = $transactionId;
        $this->gatewayErrorCode = $gatewayErrorCode;
        $this->gatewayErrorMessage = $gatewayErrorMessage;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->retryable = $retryable;

        $details = [
            'payment_type' => $paymentType,
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'gateway_error_code' => $gatewayErrorCode,
            'gateway_error_message' => $gatewayErrorMessage,
            'amount' => $amount,
            'currency' => $currency,
            'retryable' => $retryable,
        ];

        // ユーザーフレンドリーなメッセージを生成
        $userMessage = $this->generateUserMessage($paymentType, $paymentMethod);

        parent::__construct($message, self::ERROR_CODE_PAYMENT, $previous, $details, $userMessage);
    }

    /**
     * 決済方法を取得
     *
     * @return string|null
     */
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * 決済エラー種類を取得
     *
     * @return string
     */
    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * トランザクションIDを取得
     *
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * ゲートウェイエラーコードを取得
     *
     * @return string|null
     */
    public function getGatewayErrorCode(): ?string
    {
        return $this->gatewayErrorCode;
    }

    /**
     * ゲートウェイエラーメッセージを取得
     *
     * @return string|null
     */
    public function getGatewayErrorMessage(): ?string
    {
        return $this->gatewayErrorMessage;
    }

    /**
     * 決済金額を取得
     *
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * 通貨コードを取得
     *
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * リトライ可能かどうかを取得
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * ユーザーフレンドリーなメッセージを生成
     *
     * @param string $paymentType
     * @param string|null $paymentMethod
     * @return string
     */
    private function generateUserMessage(string $paymentType, ?string $paymentMethod): string
    {
        $methodName = $this->getPaymentMethodName($paymentMethod);

        switch ($paymentType) {
            case self::PAYMENT_TYPE_CARD_DECLINED:
                return "カードが承認されませんでした。カード会社にお問い合わせいただくか、別のカードをお試しください。";
            
            case self::PAYMENT_TYPE_INSUFFICIENT_FUNDS:
                return "残高不足のため決済できませんでした。残高をご確認の上、再度お試しください。";
            
            case self::PAYMENT_TYPE_EXPIRED_CARD:
                return "カードの有効期限が切れています。有効期限をご確認いただくか、別のカードをお試しください。";
            
            case self::PAYMENT_TYPE_INVALID_CARD:
                return "カード情報が正しくありません。カード番号、有効期限、セキュリティコードをご確認ください。";
            
            case self::PAYMENT_TYPE_NETWORK_ERROR:
                return "通信エラーが発生しました。しばらく時間をおいて再度お試しください。";
            
            case self::PAYMENT_TYPE_AUTHENTICATION_FAILED:
                return "認証に失敗しました。入力内容をご確認の上、再度お試しください。";
            
            case self::PAYMENT_TYPE_LIMIT_EXCEEDED:
                return "利用限度額を超えています。カード会社にお問い合わせいただくか、別の決済方法をお試しください。";
            
            case self::PAYMENT_TYPE_TIMEOUT:
                return "決済処理がタイムアウトしました。再度お試しください。";
            
            case self::PAYMENT_TYPE_DUPLICATE_TRANSACTION:
                return "同じ取引が既に処理されています。重複した決済の可能性があります。";
            
            case self::PAYMENT_TYPE_REFUND_FAILED:
                return "返金処理に失敗しました。お手数ですが、カスタマーサポートまでお問い合わせください。";
            
            default:
                return "{$methodName}での決済処理に失敗しました。しばらく時間をおいて再度お試しいただくか、別の決済方法をご利用ください。";
        }
    }

    /**
     * 決済方法名を取得
     *
     * @param string|null $paymentMethod
     * @return string
     */
    private function getPaymentMethodName(?string $paymentMethod): string
    {
        $methodNames = [
            'credit_card' => 'クレジットカード',
            'convenience' => 'コンビニ決済',
            'bank_transfer' => '銀行振込',
            'paypal' => 'PayPal',
            'amazon_pay' => 'Amazon Pay',
            'line_pay' => 'LINE Pay',
            'paypay' => 'PayPay',
        ];

        return $methodNames[$paymentMethod] ?? '決済';
    }

    /**
     * カード拒否エラーを作成
     *
     * @param string|null $transactionId
     * @param string|null $gatewayErrorCode
     * @param string|null $gatewayErrorMessage
     * @return static
     */
    public static function cardDeclined(
        string $transactionId = null,
        string $gatewayErrorCode = null,
        string $gatewayErrorMessage = null
    ): self {
        return new static(
            'Credit card was declined',
            self::PAYMENT_TYPE_CARD_DECLINED,
            'credit_card',
            $transactionId,
            $gatewayErrorCode,
            $gatewayErrorMessage,
            null,
            'JPY',
            true
        );
    }

    /**
     * 残高不足エラーを作成
     *
     * @param float $amount
     * @param string $currency
     * @param string|null $transactionId
     * @return static
     */
    public static function insufficientFunds(
        float $amount,
        string $currency = 'JPY',
        string $transactionId = null
    ): self {
        return new static(
            'Insufficient funds',
            self::PAYMENT_TYPE_INSUFFICIENT_FUNDS,
            'credit_card',
            $transactionId,
            null,
            null,
            $amount,
            $currency,
            true
        );
    }

    /**
     * カード期限切れエラーを作成
     *
     * @param string|null $transactionId
     * @return static
     */
    public static function expiredCard(string $transactionId = null): self
    {
        return new static(
            'Credit card has expired',
            self::PAYMENT_TYPE_EXPIRED_CARD,
            'credit_card',
            $transactionId,
            null,
            null,
            null,
            'JPY',
            true
        );
    }

    /**
     * 無効なカードエラーを作成
     *
     * @param string|null $transactionId
     * @return static
     */
    public static function invalidCard(string $transactionId = null): self
    {
        return new static(
            'Invalid credit card information',
            self::PAYMENT_TYPE_INVALID_CARD,
            'credit_card',
            $transactionId,
            null,
            null,
            null,
            'JPY',
            true
        );
    }

    /**
     * ネットワークエラーを作成
     *
     * @param string $paymentMethod
     * @param \Throwable|null $previous
     * @return static
     */
    public static function networkError(string $paymentMethod, \Throwable $previous = null): self
    {
        return new static(
            'Payment network error',
            self::PAYMENT_TYPE_NETWORK_ERROR,
            $paymentMethod,
            null,
            null,
            null,
            null,
            'JPY',
            true,
            $previous
        );
    }

    /**
     * ゲートウェイエラーを作成
     *
     * @param string $paymentMethod
     * @param string|null $gatewayErrorCode
     * @param string|null $gatewayErrorMessage
     * @param string|null $transactionId
     * @return static
     */
    public static function gatewayError(
        string $paymentMethod,
        string $gatewayErrorCode = null,
        string $gatewayErrorMessage = null,
        string $transactionId = null
    ): self {
        return new static(
            'Payment gateway error',
            self::PAYMENT_TYPE_GATEWAY_ERROR,
            $paymentMethod,
            $transactionId,
            $gatewayErrorCode,
            $gatewayErrorMessage,
            null,
            'JPY',
            false
        );
    }

    /**
     * 認証失敗エラーを作成
     *
     * @param string $paymentMethod
     * @param string|null $transactionId
     * @return static
     */
    public static function authenticationFailed(string $paymentMethod, string $transactionId = null): self
    {
        return new static(
            'Payment authentication failed',
            self::PAYMENT_TYPE_AUTHENTICATION_FAILED,
            $paymentMethod,
            $transactionId,
            null,
            null,
            null,
            'JPY',
            true
        );
    }

    /**
     * 利用限度額超過エラーを作成
     *
     * @param float $amount
     * @param string $currency
     * @param string|null $transactionId
     * @return static
     */
    public static function limitExceeded(
        float $amount,
        string $currency = 'JPY',
        string $transactionId = null
    ): self {
        return new static(
            'Payment limit exceeded',
            self::PAYMENT_TYPE_LIMIT_EXCEEDED,
            'credit_card',
            $transactionId,
            null,
            null,
            $amount,
            $currency,
            true
        );
    }

    /**
     * 返金失敗エラーを作成
     *
     * @param float $amount
     * @param string $currency
     * @param string|null $transactionId
     * @param string|null $originalTransactionId
     * @return static
     */
    public static function refundFailed(
        float $amount,
        string $currency = 'JPY',
        string $transactionId = null,
        string $originalTransactionId = null
    ): self {
        $details = [];
        if ($originalTransactionId) {
            $details['original_transaction_id'] = $originalTransactionId;
        }

        $exception = new static(
            'Refund failed',
            self::PAYMENT_TYPE_REFUND_FAILED,
            null,
            $transactionId,
            null,
            null,
            $amount,
            $currency,
            false
        );

        if (!empty($details)) {
            $exception->setDetails(array_merge($exception->getDetails(), $details));
        }

        return $exception;
    }
}