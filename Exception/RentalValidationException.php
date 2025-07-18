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
     * バリデーションエラーコード
     */
    const ERROR_REQUIRED_FIELD = 1001;
    const ERROR_INVALID_FORMAT = 1002;
    const ERROR_OUT_OF_RANGE = 1003;
    const ERROR_INVALID_DATE = 1004;
    const ERROR_INVALID_PERIOD = 1005;
    const ERROR_INVALID_QUANTITY = 1006;
    const ERROR_INVALID_AMOUNT = 1007;
    const ERROR_INVALID_EMAIL = 1008;
    const ERROR_INVALID_PHONE = 1009;
    const ERROR_INVALID_POSTAL_CODE = 1010;
    const ERROR_INVALID_JSON = 1011;
    const ERROR_DUPLICATE_VALUE = 1012;
    const ERROR_INVALID_CHOICE = 1013;
    const ERROR_FILE_TOO_LARGE = 1014;
    const ERROR_INVALID_FILE_TYPE = 1015;
    const ERROR_INVALID_BUSINESS_RULE = 1016;

    /**
     * @var string 対象フィールド名
     */
    protected $fieldName;

    /**
     * @var mixed 無効な値
     */
    protected $invalidValue;

    /**
     * @var array 許可される値
     */
    protected $allowedValues;

    /**
     * @var array バリデーションエラーのリスト
     */
    protected $validationErrors;

    /**
     * コンストラクタ
     *
     * @param string $message
     * @param int $code
     * @param string|null $fieldName
     * @param mixed $invalidValue
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = '',
        int $code = self::ERROR_REQUIRED_FIELD,
        string $fieldName = null,
        $invalidValue = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->fieldName = $fieldName;
        $this->invalidValue = $invalidValue;
        $this->allowedValues = [];
        $this->validationErrors = [];
        
        // バリデーションエラーは基本的にユーザーエラー
        $this->setErrorType(self::TYPE_USER)
             ->setErrorLevel(self::LEVEL_INFO)
             ->setUserMessage($message)
             ->setShouldLog(false);
    }

    /**
     * フィールド名を設定
     *
     * @param string $fieldName
     * @return self
     */
    public function setFieldName(string $fieldName): self
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * フィールド名を取得
     *
     * @return string|null
     */
    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    /**
     * 無効な値を設定
     *
     * @param mixed $value
     * @return self
     */
    public function setInvalidValue($value): self
    {
        $this->invalidValue = $value;
        return $this;
    }

    /**
     * 無効な値を取得
     *
     * @return mixed
     */
    public function getInvalidValue()
    {
        return $this->invalidValue;
    }

    /**
     * 許可される値を設定
     *
     * @param array $values
     * @return self
     */
    public function setAllowedValues(array $values): self
    {
        $this->allowedValues = $values;
        return $this;
    }

    /**
     * 許可される値を取得
     *
     * @return array
     */
    public function getAllowedValues(): array
    {
        return $this->allowedValues;
    }

    /**
     * バリデーションエラーを追加
     *
     * @param string $field
     * @param string $message
     * @param int $code
     * @return self
     */
    public function addValidationError(string $field, string $message, int $code = self::ERROR_INVALID_FORMAT): self
    {
        $this->validationErrors[] = [
            'field' => $field,
            'message' => $message,
            'code' => $code,
        ];
        return $this;
    }

    /**
     * バリデーションエラー一覧を取得
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * バリデーションエラーがあるかどうか
     *
     * @return bool
     */
    public function hasValidationErrors(): bool
    {
        return !empty($this->validationErrors);
    }

    /**
     * 必須フィールドエラーを作成
     *
     * @param string $fieldName
     * @return self
     */
    public static function createRequiredFieldError(string $fieldName): self
    {
        return new self(
            sprintf('%sは必須です。', $fieldName),
            self::ERROR_REQUIRED_FIELD,
            $fieldName
        );
    }

    /**
     * フォーマットエラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @param string $expectedFormat
     * @return self
     */
    public static function createInvalidFormatError(string $fieldName, $value, string $expectedFormat): self
    {
        return new self(
            sprintf('%sの形式が正しくありません。期待される形式: %s', $fieldName, $expectedFormat),
            self::ERROR_INVALID_FORMAT,
            $fieldName,
            $value
        );
    }

    /**
     * 範囲外エラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @param mixed $min
     * @param mixed $max
     * @return self
     */
    public static function createOutOfRangeError(string $fieldName, $value, $min = null, $max = null): self
    {
        $message = sprintf('%sが範囲外です。', $fieldName);
        
        if ($min !== null && $max !== null) {
            $message = sprintf('%sは%s以上%s以下で入力してください。', $fieldName, $min, $max);
        } elseif ($min !== null) {
            $message = sprintf('%sは%s以上で入力してください。', $fieldName, $min);
        } elseif ($max !== null) {
            $message = sprintf('%sは%s以下で入力してください。', $fieldName, $max);
        }
        
        return new self($message, self::ERROR_OUT_OF_RANGE, $fieldName, $value);
    }

    /**
     * 無効な日付エラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @return self
     */
    public static function createInvalidDateError(string $fieldName, $value): self
    {
        return new self(
            sprintf('%sが無効な日付です。', $fieldName),
            self::ERROR_INVALID_DATE,
            $fieldName,
            $value
        );
    }

    /**
     * 無効な期間エラーを作成
     *
     * @param string $message
     * @return self
     */
    public static function createInvalidPeriodError(string $message): self
    {
        return new self($message, self::ERROR_INVALID_PERIOD);
    }

    /**
     * 無効な数量エラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @param int $min
     * @param int $max
     * @return self
     */
    public static function createInvalidQuantityError(string $fieldName, $value, int $min = 1, int $max = null): self
    {
        $message = sprintf('%sは%d以上で入力してください。', $fieldName, $min);
        
        if ($max !== null) {
            $message = sprintf('%sは%d以上%d以下で入力してください。', $fieldName, $min, $max);
        }
        
        return new self($message, self::ERROR_INVALID_QUANTITY, $fieldName, $value);
    }

    /**
     * 無効な金額エラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @return self
     */
    public static function createInvalidAmountError(string $fieldName, $value): self
    {
        return new self(
            sprintf('%sは正しい金額を入力してください。', $fieldName),
            self::ERROR_INVALID_AMOUNT,
            $fieldName,
            $value
        );
    }

    /**
     * 無効なメールアドレスエラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @return self
     */
    public static function createInvalidEmailError(string $fieldName, $value): self
    {
        return new self(
            sprintf('%sは正しいメールアドレスを入力してください。', $fieldName),
            self::ERROR_INVALID_EMAIL,
            $fieldName,
            $value
        );
    }

    /**
     * 無効な電話番号エラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @return self
     */
    public static function createInvalidPhoneError(string $fieldName, $value): self
    {
        return new self(
            sprintf('%sは正しい電話番号を入力してください。', $fieldName),
            self::ERROR_INVALID_PHONE,
            $fieldName,
            $value
        );
    }

    /**
     * 無効な郵便番号エラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @return self
     */
    public static function createInvalidPostalCodeError(string $fieldName, $value): self
    {
        return new self(
            sprintf('%sは正しい郵便番号を入力してください（例: 123-4567）。', $fieldName),
            self::ERROR_INVALID_POSTAL_CODE,
            $fieldName,
            $value
        );
    }

    /**
     * 無効なJSONエラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @return self
     */
    public static function createInvalidJsonError(string $fieldName, $value): self
    {
        return new self(
            sprintf('%sのJSON形式が正しくありません。', $fieldName),
            self::ERROR_INVALID_JSON,
            $fieldName,
            $value
        );
    }

    /**
     * 重複値エラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @return self
     */
    public static function createDuplicateValueError(string $fieldName, $value): self
    {
        return new self(
            sprintf('%sは既に使用されています。', $fieldName),
            self::ERROR_DUPLICATE_VALUE,
            $fieldName,
            $value
        );
    }

    /**
     * 無効な選択肢エラーを作成
     *
     * @param string $fieldName
     * @param mixed $value
     * @param array $allowedValues
     * @return self
     */
    public static function createInvalidChoiceError(string $fieldName, $value, array $allowedValues): self
    {
        $exception = new self(
            sprintf('%sの値が無効です。許可されている値: %s', $fieldName, implode(', ', $allowedValues)),
            self::ERROR_INVALID_CHOICE,
            $fieldName,
            $value
        );
        
        return $exception->setAllowedValues($allowedValues);
    }

    /**
     * ファイルサイズエラーを作成
     *
     * @param string $fieldName
     * @param int $actualSize
     * @param int $maxSize
     * @return self
     */
    public static function createFileTooLargeError(string $fieldName, int $actualSize, int $maxSize): self
    {
        $maxSizeMB = round($maxSize / 1024 / 1024, 1);
        
        return new self(
            sprintf('%sのファイルサイズが大きすぎます。最大%sMBまでです。', $fieldName, $maxSizeMB),
            self::ERROR_FILE_TOO_LARGE,
            $fieldName,
            $actualSize
        );
    }

    /**
     * 無効なファイルタイプエラーを作成
     *
     * @param string $fieldName
     * @param string $actualType
     * @param array $allowedTypes
     * @return self
     */
    public static function createInvalidFileTypeError(string $fieldName, string $actualType, array $allowedTypes): self
    {
        $exception = new self(
            sprintf('%sのファイル形式が無効です。許可されている形式: %s', $fieldName, implode(', ', $allowedTypes)),
            self::ERROR_INVALID_FILE_TYPE,
            $fieldName,
            $actualType
        );
        
        return $exception->setAllowedValues($allowedTypes);
    }

    /**
     * ビジネスルールエラーを作成
     *
     * @param string $message
     * @param string|null $fieldName
     * @return self
     */
    public static function createBusinessRuleError(string $message, string $fieldName = null): self
    {
        return new self($message, self::ERROR_INVALID_BUSINESS_RULE, $fieldName);
    }

    /**
     * 複数のバリデーションエラーから作成
     *
     * @param array $errors
     * @return self
     */
    public static function createFromMultipleErrors(array $errors): self
    {
        $messages = [];
        $exception = new self('複数のバリデーションエラーがあります。', self::ERROR_INVALID_FORMAT);
        
        foreach ($errors as $error) {
            if (is_array($error) && isset($error['field'], $error['message'])) {
                $exception->addValidationError(
                    $error['field'], 
                    $error['message'], 
                    $error['code'] ?? self::ERROR_INVALID_FORMAT
                );
                $messages[] = $error['message'];
            } elseif (is_string($error)) {
                $messages[] = $error;
            }
        }
        
        if (!empty($messages)) {
            $exception->setUserMessage(implode("\n", $messages));
        }
        
        return $exception;
    }

    /**
     * エラー情報を配列で取得（拡張版）
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['field_name'] = $this->fieldName;
        $array['invalid_value'] = $this->invalidValue;
        $array['allowed_values'] = $this->allowedValues;
        $array['validation_errors'] = $this->validationErrors;
        
        return $array;
    }

    /**
     * フィールド別のエラーメッセージを取得
     *
     * @return array
     */
    public function getFieldErrors(): array
    {
        $errors = [];
        
        if ($this->fieldName) {
            $errors[$this->fieldName] = $this->getUserMessage();
        }
        
        foreach ($this->validationErrors as $error) {
            $errors[$error['field']] = $error['message'];
        }
        
        return $errors;
    }
}