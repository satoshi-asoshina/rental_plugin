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
 * レンタル検証エラー例外クラス
 */
class RentalValidationException extends RentalException
{
    /**
     * バリデーションエラーの種類
     */
    const VALIDATION_TYPE_REQUIRED = 'required';
    const VALIDATION_TYPE_FORMAT = 'format';
    const VALIDATION_TYPE_RANGE = 'range';
    const VALIDATION_TYPE_DATE = 'date';
    const VALIDATION_TYPE_BUSINESS_RULE = 'business_rule';
    const VALIDATION_TYPE_DUPLICATE = 'duplicate';
    const VALIDATION_TYPE_DEPENDENCY = 'dependency';

    /**
     * バリデーションエラーのフィールド
     *
     * @var string|null
     */
    private $field;

    /**
     * バリデーションエラーの種類
     *
     * @var string
     */
    private $validationType;

    /**
     * バリデーションエラーの詳細
     *
     * @var array
     */
    private $validationErrors = [];

    /**
     * 入力値
     *
     * @var mixed
     */
    private $inputValue;

    /**
     * 期待される値または条件
     *
     * @var mixed
     */
    private $expectedValue;

    /**
     * コンストラクタ
     *
     * @param string $message エラーメッセージ
     * @param string|null $field エラーフィールド
     * @param string $validationType バリデーション種類
     * @param mixed $inputValue 入力値
     * @param mixed $expectedValue 期待値
     * @param array $validationErrors バリデーションエラー詳細
     * @param \Throwable|null $previous 前の例外
     */
    public function __construct(
        string $message,
        string $field = null,
        string $validationType = self::VALIDATION_TYPE_BUSINESS_RULE,
        $inputValue = null,
        $expectedValue = null,
        array $validationErrors = [],
        \Throwable $previous = null
    ) {
        $this->field = $field;
        $this->validationType = $validationType;
        $this->inputValue = $inputValue;
        $this->expectedValue = $expectedValue;
        $this->validationErrors = $validationErrors;

        $details = [
            'field' => $field,
            'validation_type' => $validationType,
            'input_value' => $inputValue,
            'expected_value' => $expectedValue,
            'validation_errors' => $validationErrors,
        ];

        parent::__construct($message, self::ERROR_CODE_VALIDATION, $previous, $details, $message);
    }

    /**
     * フィールド名を取得
     *
     * @return string|null
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * バリデーション種類を取得
     *
     * @return string
     */
    public function getValidationType(): string
    {
        return $this->validationType;
    }

    /**
     * 入力値を取得
     *
     * @return mixed
     */
    public function getInputValue()
    {
        return $this->inputValue;
    }

    /**
     * 期待値を取得
     *
     * @return mixed
     */
    public function getExpectedValue()
    {
        return $this->expectedValue;
    }

    /**
     * バリデーションエラー詳細を取得
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * バリデーションエラーを追加
     *
     * @param string $field
     * @param string $message
     * @param string $type
     * @return self
     */
    public function addValidationError(string $field, string $message, string $type = self::VALIDATION_TYPE_BUSINESS_RULE): self
    {
        $this->validationErrors[] = [
            'field' => $field,
            'message' => $message,
            'type' => $type,
        ];
        return $this;
    }

    /**
     * 必須フィールドエラーを作成
     *
     * @param string $field
     * @param string|null $customMessage
     * @return static
     */
    public static function required(string $field, string $customMessage = null): self
    {
        $message = $customMessage ?: "{$field}は必須項目です。";
        
        return new static(
            $message,
            $field,
            self::VALIDATION_TYPE_REQUIRED
        );
    }

    /**
     * フォーマットエラーを作成
     *
     * @param string $field
     * @param mixed $inputValue
     * @param string $expectedFormat
     * @param string|null $customMessage
     * @return static
     */
    public static function format(string $field, $inputValue, string $expectedFormat, string $customMessage = null): self
    {
        $message = $customMessage ?: "{$field}の形式が正しくありません。期待される形式: {$expectedFormat}";
        
        return new static(
            $message,
            $field,
            self::VALIDATION_TYPE_FORMAT,
            $inputValue,
            $expectedFormat
        );
    }

    /**
     * 範囲エラーを作成
     *
     * @param string $field
     * @param mixed $inputValue
     * @param mixed $min
     * @param mixed $max
     * @param string|null $customMessage
     * @return static
     */
    public static function range(string $field, $inputValue, $min, $max, string $customMessage = null): self
    {
        $message = $customMessage ?: "{$field}は{$min}以上{$max}以下で入力してください。";
        
        return new static(
            $message,
            $field,
            self::VALIDATION_TYPE_RANGE,
            $inputValue,
            ['min' => $min, 'max' => $max]
        );
    }

    /**
     * 日付エラーを作成
     *
     * @param string $field
     * @param mixed $inputValue
     * @param string $constraint
     * @param string|null $customMessage
     * @return static
     */
    public static function date(string $field, $inputValue, string $constraint, string $customMessage = null): self
    {
        $message = $customMessage ?: "{$field}の日付が無効です。{$constraint}";
        
        return new static(
            $message,
            $field,
            self::VALIDATION_TYPE_DATE,
            $inputValue,
            $constraint
        );
    }

    /**
     * ビジネスルールエラーを作成
     *
     * @param string $message
     * @param string|null $field
     * @param array $context
     * @return static
     */
    public static function businessRule(string $message, string $field = null, array $context = []): self
    {
        return new static(
            $message,
            $field,
            self::VALIDATION_TYPE_BUSINESS_RULE,
            $context['input_value'] ?? null,
            $context['expected_value'] ?? null,
            $context['validation_errors'] ?? []
        );
    }

    /**
     * 重複エラーを作成
     *
     * @param string $field
     * @param mixed $inputValue
     * @param string|null $customMessage
     * @return static
     */
    public static function duplicate(string $field, $inputValue, string $customMessage = null): self
    {
        $message = $customMessage ?: "{$field}は既に使用されています。";
        
        return new static(
            $message,
            $field,
            self::VALIDATION_TYPE_DUPLICATE,
            $inputValue
        );
    }

    /**
     * 依存関係エラーを作成
     *
     * @param string $field
     * @param string $dependentField
     * @param string|null $customMessage
     * @return static
     */
    public static function dependency(string $field, string $dependentField, string $customMessage = null): self
    {
        $message = $customMessage ?: "{$field}は{$dependentField}の値によって制限されています。";
        
        return new static(
            $message,
            $field,
            self::VALIDATION_TYPE_DEPENDENCY,
            null,
            $dependentField
        );
    }

    /**
     * レンタル期間エラーを作成
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int|null $minDays
     * @param int|null $maxDays
     * @return static
     */
    public static function rentalPeriod(\DateTime $startDate, \DateTime $endDate, int $minDays = null, int $maxDays = null): self
    {
        $days = $startDate->diff($endDate)->days + 1;
        
        if ($startDate >= $endDate) {
            return new static(
                'レンタル開始日は終了日より前である必要があります。',
                'rental_period',
                self::VALIDATION_TYPE_DATE,
                ['start' => $startDate, 'end' => $endDate]
            );
        }
        
        if ($minDays && $days < $minDays) {
            return new static(
                "レンタル期間は最低{$minDays}日必要です。",
                'rental_period',
                self::VALIDATION_TYPE_RANGE,
                $days,
                $minDays
            );
        }
        
        if ($maxDays && $days > $maxDays) {
            return new static(
                "レンタル期間は最大{$maxDays}日までです。",
                'rental_period',
                self::VALIDATION_TYPE_RANGE,
                $days,
                $maxDays
            );
        }
        
        return new static('レンタル期間が無効です。', 'rental_period');
    }

    /**
     * 在庫不足エラーを作成
     *
     * @param string $productName
     * @param int $requestedQuantity
     * @param int $availableQuantity
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return static
     */
    public static function insufficientStock(
        string $productName, 
        int $requestedQuantity, 
        int $availableQuantity, 
        \DateTime $startDate, 
        \DateTime $endDate
    ): self {
        $message = "商品「{$productName}」の在庫が不足しています。"
                 . "要求数量: {$requestedQuantity}個、利用可能: {$availableQuantity}個"
                 . "（期間: {$startDate->format('Y/m/d')} ～ {$endDate->format('Y/m/d')}）";
        
        return new static(
            $message,
            'quantity',
            self::VALIDATION_TYPE_BUSINESS_RULE,
            $requestedQuantity,
            $availableQuantity,
            [
                [
                    'field' => 'quantity',
                    'message' => $message,
                    'type' => self::VALIDATION_TYPE_BUSINESS_RULE,
                    'context' => [
                        'product_name' => $productName,
                        'requested_quantity' => $requestedQuantity,
                        'available_quantity' => $availableQuantity,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ]
                ]
            ]
        );
    }

    /**
     * 複数のバリデーションエラーから例外を作成
     *
     * @param array $errors
     * @return static
     */
    public static function multiple(array $errors): self
    {
        $messages = [];
        $validationErrors = [];
        
        foreach ($errors as $error) {
            $messages[] = $error['message'] ?? '不明なエラー';
            $validationErrors[] = $error;
        }
        
        $message = '入力内容に問題があります: ' . implode(', ', $messages);
        
        return new static(
            $message,
            null,
            self::VALIDATION_TYPE_BUSINESS_RULE,
            null,
            null,
            $validationErrors
        );
    }
}