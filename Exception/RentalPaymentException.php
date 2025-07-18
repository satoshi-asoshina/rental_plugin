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
     * 決済エラーコード
     */
    const ERROR_PAYMENT_DECLINED = 3001;
    const ERROR_INSUFFICIENT_FUNDS = 3002;
    const ERROR_INVALID_CARD = 3003;
    const ERROR_EXPIRED_CARD = 3004;
    const ERROR_INVALID_CVV = 3005;
    const ERROR_PAYMENT_TIMEOUT = 3006;
    const ERROR_PAYMENT_GATEWAY_ERROR = 3007;
    const ERROR_INVALID_AMOUNT = 3008;
    const ERROR_CURRENCY_NOT_SUPPORTED = 3009;
    const ERROR_PAYMENT_METHOD_NOT_AVAILABLE = 3010;
    const ERROR_REFUND_FAILED = 3011;
    const ERROR_PARTIAL_REFUND_NOT_ALLOWED = 3012;
    const ERROR_REFUND_DEADLINE_EXPIRED = 3013;
    const ERROR_DUPLICATE_TRANSACTION = 3014;
    const ERROR_CHARGEBACK = 3015;
    const ERROR_FRAUD_DETECTED = 3016;
    const ERROR_DEPOSIT_FAILED = 3017;
    const ERROR_DEPOSIT_RELEASE_FAILED = 3018;

    /**
     * @var string 決済方法
     */
    protected $paymentMethod;

    /**
     * @var string 取引ID
     */
    protected $transactionId;

    /**
     * @var string 決済金額
     */
    protected $amount;

    /**
     * @var string 通貨
     */
    protected $currency;

    /**
     * @var string 決済ゲートウェイからのエラーコード
     */
    protected $gatewayErrorCode;

    /**
     * @var string 決済ゲートウェイからのエラーメッセージ
     */
    protected $gatewayErrorMessage;

    /**
     * @var array 決済詳細情報
     */
    protected $paymentDetails;

    /**
     * @var bool 再試行可能フラグ
     */
    protected $retryable;

    /**
     * コンストラクタ
     *
     * @param string $message
     * @param int $code
     * @param string|null $paymentMethod
     * @param string|null $transactionId
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = '',
        int $code = self::ERROR_PAYMENT_DECLINED,
        string $paymentMethod = null,
        string $transactionId = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->paymentMethod = $paymentMethod;
        $this->transactionId = $transactionId;
        $this->amount = '0';
        $this->currency = 'JPY';
        $this->gatewayErrorCode = '';
        $this->gatewayErrorMessage = '';
        $this->paymentDetails = [];
        $this->retryable = false;
        
        // 決済エラーはビジネスエラー
        $this->setErrorType(self::TYPE_BUSINESS)
             ->setErrorLevel(self::LEVEL_WARNING)
             ->setUserMessage($message);
    }

    /**
     * 決済方法を設定
     *
     * @param string $method
     * @return self
     */
    public function setPaymentMethod(string $method): self
    {
        $this->paymentMethod = $method;
        return $this;
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
     * 取引IDを設定
     *
     * @param string $transactionId
     * @return self
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * 取引IDを取得
     *
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * 決済金額を設定
     *
     * @param string $amount
     * @return self
     */
    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * 決済金額を取得
     *
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * 通貨を設定
     *
     * @param string $currency
     * @return self
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * 通貨を取得
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * ゲートウェイエラーコードを設定
     *
     * @param string $code
     * @return self
     */
    public function setGatewayErrorCode(string $code): self
    {
        $this->gatewayErrorCode = $code;
        return $this;
    }

    /**
     * ゲートウェイエラーコードを取得
     *
     * @return string
     */
    public function getGatewayErrorCode(): string
    {
        return $this->gatewayErrorCode;
    }

    /**
     * ゲートウェイエラーメッセージを設定
     *
     * @param string $message
     * @return self
     */
    public function setGatewayErrorMessage(string $message): self
    {
        $this->gatewayErrorMessage = $message;
        return $this;
    }

    /**
     * ゲートウェイエラーメッセージを取得
     *
     * @return string
     */
    public function getGatewayErrorMessage(): string
    {
        return $this->gatewayErrorMessage;
    }

    /**
     * 決済詳細情報を設定
     *
     * @param array $details
     * @return self
     */
    public function setPaymentDetails(array $details): self
    {
        $this->paymentDetails = $details;
        return $this;
    }

    /**
     * 決済詳細情報を取得
     *
     * @return array
     */
    public function getPaymentDetails(): array
    {
        return $this->paymentDetails;
    }

    /**
     * 再試行可能フラグを設定
     *
     * @param bool $retryable
     * @return self
     */
    public function setRetryable(bool $retryable): self
    {
        $this->retryable = $retryable;
        return $this;
    }

    /**
     * 再試行可能かどうか
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * 決済拒否エラーを作成
     *
     * @param string $paymentMethod
     * @param string $reason
     * @param string|null $transactionId
     * @return self
     */
    public static function createPaymentDeclinedError(
        string $paymentMethod, 
        string $reason = '',
        string $transactionId = null
    ): self {
        $message = '決済が拒否されました。';
        if ($reason) {
            $message .= ' 理由: ' . $reason;
        }
        
        return new self($message, self::ERROR_PAYMENT_DECLINED, $paymentMethod, $transactionId);
    }

    /**
     * 残高不足エラーを作成
     *
     * @param string $paymentMethod
     * @param string|null $transactionId
     * @return self
     */
    public static function createInsufficientFundsError(string $paymentMethod, string $transactionId = null): self
    {
        return new self(
            '残高不足のため決済できません。',
            self::ERROR_INSUFFICIENT_FUNDS,
            $paymentMethod,
            $transactionId
        );
    }

    /**
     * 無効なカードエラーを作成
     *
     * @param string|null $transactionId
     * @return self
     */
    public static function createInvalidCardError(string $transactionId = null): self
    {
        return (new self(
            'カード情報が無効です。カード番号をご確認ください。',
            self::ERROR_INVALID_CARD,
            'credit_card',
            $transactionId
        ))->setRetryable(true);
    }

    /**
     * カード期限切れエラーを作成
     *
     * @param string|null $transactionId
     * @return self
     */
    public static function createExpiredCardError(string $transactionId = null): self
    {
        return (new self(
            'カードの有効期限が切れています。',
            self::ERROR_EXPIRED_CARD,
            'credit_card',
            $transactionId
        ))->setRetryable(true);
    }

    /**
     * CVVエラーを作成
     *
     * @param string|null $transactionId
     * @return self
     */
    public static function createInvalidCvvError(string $transactionId = null): self
    {
        return (new self(
            'セキュリティコード（CVV）が正しくありません。',
            self::ERROR_INVALID_CVV,
            'credit_card',
            $transactionId
        ))->setRetryable(true);
    }

    /**
     * 決済タイムアウトエラーを作成
     *
     * @param string $paymentMethod
     * @param string|null $transactionId
     * @return self
     */
    public static function createPaymentTimeoutError(string $paymentMethod, string $transactionId = null): self
    {
        return (new self(
            '決済処理がタイムアウトしました。再度お試しください。',
            self::ERROR_PAYMENT_TIMEOUT,
            $paymentMethod,
            $transactionId
        ))->setRetryable(true);
    }

    /**
     * ゲートウェイエラーを作成
     *
     * @param string $paymentMethod
     * @param string $gatewayCode
     * @param string $gatewayMessage
     * @param string|null $transactionId
     * @return self
     */
    public static function createGatewayError(
        string $paymentMethod,
        string $gatewayCode,
        string $gatewayMessage,
        string $transactionId = null
    ): self {
        $exception = new self(
            '決済システムでエラーが発生しました。しばらく時間をおいて再度お試しください。',
            self::ERROR_PAYMENT_GATEWAY_ERROR,
            $paymentMethod,
            $transactionId
        );
        
        return $exception->setGatewayErrorCode($gatewayCode)
                         ->setGatewayErrorMessage($gatewayMessage)
                         ->setRetryable(true);
    }

    /**
     * 無効な金額エラーを作成
     *
     * @param string $amount
     * @param string $paymentMethod
     * @param string|null $transactionId
     * @return self
     */
    public static function createInvalidAmountError(
        string $amount,
        string $paymentMethod = '',
        string $transactionId = null
    ): self {
        return (new self(
            sprintf('無効な金額です: %s', $amount),
            self::ERROR_INVALID_AMOUNT,
            $paymentMethod,
            $transactionId
        ))->setAmount($amount);
    }

    /**
     * 対応していない通貨エラーを作成
     *
     * @param string $currency
     * @param string $paymentMethod
     * @param string|null $transactionId
     * @return self
     */
    public static function createCurrencyNotSupportedError(
        string $currency,
        string $paymentMethod = '',
        string $transactionId = null
    ): self {
        return (new self(
            sprintf('対応していない通貨です: %s', $currency),
            self::ERROR_CURRENCY_NOT_SUPPORTED,
            $paymentMethod,
            $transactionId
        ))->setCurrency($currency);
    }

    /**
     * 決済方法が利用できないエラーを作成
     *
     * @param string $paymentMethod
     * @param string|null $transactionId
     * @return self
     */
    public static function createPaymentMethodNotAvailableError(
        string $paymentMethod,
        string $transactionId = null
    ): self {
        return new self(
            sprintf('決済方法「%s」は現在利用できません。', $paymentMethod),
            self::ERROR_PAYMENT_METHOD_NOT_AVAILABLE,
            $paymentMethod,
            $transactionId
        );
    }

    /**
     * 返金失敗エラーを作成
     *
     * @param string $paymentMethod
     * @param string $amount
     * @param string $reason
     * @param string|null $transactionId
     * @return self
     */
    public static function createRefundFailedError(
        string $paymentMethod,
        string $amount,
        string $reason = '',
        string $transactionId = null
    ): self {
        $message = sprintf('返金処理に失敗しました。金額: %s', $amount);
        if ($reason) {
            $message .= ' 理由: ' . $reason;
        }

        return (new self(
            $message,
            self::ERROR_REFUND_FAILED,
            $paymentMethod,
            $transactionId
        ))->setAmount($amount);
    }

    /**
     * 部分返金不可エラーを作成
     *
     * @param string $paymentMethod
     * @param string $requestedAmount
     * @param string $totalAmount
     * @param string|null $transactionId
     * @return self
     */
    public static function createPartialRefundNotAllowedError(
        string $paymentMethod,
        string $requestedAmount,
        string $totalAmount,
        string $transactionId = null
    ): self {
        return (new self(
            sprintf(
                'この決済方法では部分返金はできません。要求額: %s, 総額: %s',
                $requestedAmount,
                $totalAmount
            ),
            self::ERROR_PARTIAL_REFUND_NOT_ALLOWED,
            $paymentMethod,
            $transactionId
        ))->setAmount($requestedAmount);
    }

    /**
     * 返金期限切れエラーを作成
     *
     * @param string $paymentMethod
     * @param \DateTime $deadline
     * @param string|null $transactionId
     * @return self
     */
    public static function createRefundDeadlineExpiredError(
        string $paymentMethod,
        \DateTime $deadline,
        string $transactionId = null
    ): self {
        return (new self(
            sprintf('返金期限を過ぎています。期限: %s', $deadline->format('Y-m-d H:i:s')),
            self::ERROR_REFUND_DEADLINE_EXPIRED,
            $paymentMethod,
            $transactionId
        ))->addContext('deadline', $deadline->format('Y-m-d H:i:s'));
    }

    /**
     * 重複取引エラーを作成
     *
     * @param string $paymentMethod
     * @param string $duplicateTransactionId
     * @param string|null $transactionId
     * @return self
     */
    public static function createDuplicateTransactionError(
        string $paymentMethod,
        string $duplicateTransactionId,
        string $transactionId = null
    ): self {
        return (new self(
            '重複する取引が検出されました。',
            self::ERROR_DUPLICATE_TRANSACTION,
            $paymentMethod,
            $transactionId
        ))->addContext('duplicate_transaction_id', $duplicateTransactionId);
    }

    /**
     * チャージバックエラーを作成
     *
     * @param string $paymentMethod
     * @param string $amount
     * @param string $reason
     * @param string|null $transactionId
     * @return self
     */
    public static function createChargebackError(
        string $paymentMethod,
        string $amount,
        string $reason = '',
        string $transactionId = null
    ): self {
        $message = sprintf('チャージバックが発生しました。金額: %s', $amount);
        if ($reason) {
            $message .= ' 理由: ' . $reason;
        }

        return (new self(
            $message,
            self::ERROR_CHARGEBACK,
            $paymentMethod,
            $transactionId
        ))->setAmount($amount)
           ->setErrorLevel(self::LEVEL_ERROR);
    }

    /**
     * 不正検知エラーを作成
     *
     * @param string $paymentMethod
     * @param string $riskScore
     * @param string|null $transactionId
     * @return self
     */
    public static function createFraudDetectedError(
        string $paymentMethod,
        string $riskScore = '',
        string $transactionId = null
    ): self {
        $message = '不正な取引の可能性が検出されました。';
        if ($riskScore) {
            $message .= sprintf(' リスクスコア: %s', $riskScore);
        }

        return (new self(
            $message,
            self::ERROR_FRAUD_DETECTED,
            $paymentMethod,
            $transactionId
        ))->setErrorLevel(self::LEVEL_ERROR)
           ->addContext('risk_score', $riskScore);
    }

    /**
     * 保証金決済失敗エラーを作成
     *
     * @param string $paymentMethod
     * @param string $depositAmount
     * @param string $reason
     * @param string|null $transactionId
     * @return self
     */
    public static function createDepositFailedError(
        string $paymentMethod,
        string $depositAmount,
        string $reason = '',
        string $transactionId = null
    ): self {
        $message = sprintf('保証金の決済に失敗しました。金額: %s', $depositAmount);
        if ($reason) {
            $message .= ' 理由: ' . $reason;
        }

        return (new self(
            $message,
            self::ERROR_DEPOSIT_FAILED,
            $paymentMethod,
            $transactionId
        ))->setAmount($depositAmount);
    }

    /**
     * 保証金返却失敗エラーを作成
     *
     * @param string $paymentMethod
     * @param string $depositAmount
     * @param string $reason
     * @param string|null $transactionId
     * @return self
     */
    public static function createDepositReleaseFailedError(
        string $paymentMethod,
        string $depositAmount,
        string $reason = '',
        string $transactionId = null
    ): self {
        $message = sprintf('保証金の返却に失敗しました。金額: %s', $depositAmount);
        if ($reason) {
            $message .= ' 理由: ' . $reason;
        }

        return (new self(
            $message,
            self::ERROR_DEPOSIT_RELEASE_FAILED,
            $paymentMethod,
            $transactionId
        ))->setAmount($depositAmount);
    }

    /**
     * エラー情報を配列で取得（拡張版）
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['payment_method'] = $this->paymentMethod;
        $array['transaction_id'] = $this->transactionId;
        $array['amount'] = $this->amount;
        $array['currency'] = $this->currency;
        $array['gateway_error_code'] = $this->gatewayErrorCode;
        $array['gateway_error_message'] = $this->gatewayErrorMessage;
        $array['payment_details'] = $this->paymentDetails;
        $array['retryable'] = $this->retryable;
        
        return $array;
    }

    /**
     * 決済復旧可能かどうか
     *
     * @return bool
     */
    public function isRecoverable(): bool
    {
        $recoverableCodes = [
            self::ERROR_PAYMENT_TIMEOUT,
            self::ERROR_PAYMENT_GATEWAY_ERROR,
            self::ERROR_INVALID_CARD,
            self::ERROR_EXPIRED_CARD,
            self::ERROR_INVALID_CVV,
        ];
        
        return in_array($this->getCode(), $recoverableCodes);
    }

    /**
     * 緊急対応が必要かどうか
     *
     * @return bool
     */
    public function requiresUrgentAction(): bool
    {
        $urgentCodes = [
            self::ERROR_CHARGEBACK,
            self::ERROR_FRAUD_DETECTED,
        ];
        
        return in_array($this->getCode(), $urgentCodes);
    }

    /**
     * 顧客に表示可能なエラーかどうか
     *
     * @return bool
     */
    public function isCustomerDisplayable(): bool
    {
        $hiddenCodes = [
            self::ERROR_FRAUD_DETECTED,
            self::ERROR_CHARGEBACK,
        ];
        
        return !in_array($this->getCode(), $hiddenCodes);
    }
}