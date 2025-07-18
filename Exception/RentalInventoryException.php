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
 * レンタル在庫エラー例外クラス
 */
class RentalInventoryException extends RentalException
{
    /**
     * 在庫エラーコード
     */
    const ERROR_OUT_OF_STOCK = 2001;
    const ERROR_INSUFFICIENT_STOCK = 2002;
    const ERROR_INVALID_QUANTITY = 2003;
    const ERROR_STOCK_CONFLICT = 2004;
    const ERROR_RESERVATION_FAILED = 2005;
    const ERROR_STOCK_UPDATE_FAILED = 2006;
    const ERROR_INVENTORY_LOCKED = 2007;
    const ERROR_MAINTENANCE_MODE = 2008;
    const ERROR_INVALID_PERIOD = 2009;
    const ERROR_STOCK_CALCULATION_ERROR = 2010;
    const ERROR_INVENTORY_SYNC_FAILED = 2011;
    const ERROR_OVERCOMMITTED = 2012;

    /**
     * @var string 商品名
     */
    protected $productName;

    /**
     * @var int 商品ID
     */
    protected $productId;

    /**
     * @var int 要求数量
     */
    protected $requestedQuantity;

    /**
     * @var int 利用可能数量
     */
    protected $availableQuantity;

    /**
     * @var string 期間情報
     */
    protected $periodInfo;

    /**
     * @var array 在庫詳細情報
     */
    protected $inventoryDetails;

    /**
     * コンストラクタ
     *
     * @param string $message
     * @param int $code
     * @param string|null $productName
     * @param int|null $productId
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = '',
        int $code = self::ERROR_OUT_OF_STOCK,
        string $productName = null,
        int $productId = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->productName = $productName;
        $this->productId = $productId;
        $this->requestedQuantity = 0;
        $this->availableQuantity = 0;
        $this->periodInfo = '';
        $this->inventoryDetails = [];
        
        // 在庫エラーはビジネスエラー
        $this->setErrorType(self::TYPE_BUSINESS)
             ->setErrorLevel(self::LEVEL_WARNING)
             ->setUserMessage($message);
    }

    /**
     * 商品名を設定
     *
     * @param string $productName
     * @return self
     */
    public function setProductName(string $productName): self
    {
        $this->productName = $productName;
        return $this;
    }

    /**
     * 商品名を取得
     *
     * @return string|null
     */
    public function getProductName(): ?string
    {
        return $this->productName;
    }

    /**
     * 商品IDを設定
     *
     * @param int $productId
     * @return self
     */
    public function setProductId(int $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    /**
     * 商品IDを取得
     *
     * @return int|null
     */
    public function getProductId(): ?int
    {
        return $this->productId;
    }

    /**
     * 要求数量を設定
     *
     * @param int $quantity
     * @return self
     */
    public function setRequestedQuantity(int $quantity): self
    {
        $this->requestedQuantity = $quantity;
        return $this;
    }

    /**
     * 要求数量を取得
     *
     * @return int
     */
    public function getRequestedQuantity(): int
    {
        return $this->requestedQuantity;
    }

    /**
     * 利用可能数量を設定
     *
     * @param int $quantity
     * @return self
     */
    public function setAvailableQuantity(int $quantity): self
    {
        $this->availableQuantity = $quantity;
        return $this;
    }

    /**
     * 利用可能数量を取得
     *
     * @return int
     */
    public function getAvailableQuantity(): int
    {
        return $this->availableQuantity;
    }

    /**
     * 期間情報を設定
     *
     * @param string $periodInfo
     * @return self
     */
    public function setPeriodInfo(string $periodInfo): self
    {
        $this->periodInfo = $periodInfo;
        return $this;
    }

    /**
     * 期間情報を取得
     *
     * @return string
     */
    public function getPeriodInfo(): string
    {
        return $this->periodInfo;
    }

    /**
     * 在庫詳細情報を設定
     *
     * @param array $details
     * @return self
     */
    public function setInventoryDetails(array $details): self
    {
        $this->inventoryDetails = $details;
        return $this;
    }

    /**
     * 在庫詳細情報を取得
     *
     * @return array
     */
    public function getInventoryDetails(): array
    {
        return $this->inventoryDetails;
    }

    /**
     * 在庫切れエラーを作成
     *
     * @param string $productName
     * @param int|null $productId
     * @return self
     */
    public static function createOutOfStockError(string $productName, int $productId = null): self
    {
        return new self(
            sprintf('「%s」は在庫切れです。', $productName),
            self::ERROR_OUT_OF_STOCK,
            $productName,
            $productId
        );
    }

    /**
     * 在庫不足エラーを作成
     *
     * @param string $productName
     * @param int $requestedQuantity
     * @param int $availableQuantity
     * @param int|null $productId
     * @return self
     */
    public static function createInsufficientStockError(
        string $productName, 
        int $requestedQuantity, 
        int $availableQuantity,
        int $productId = null
    ): self {
        $exception = new self(
            sprintf(
                '「%s」の在庫が不足しています。要求数量: %d個、利用可能数量: %d個',
                $productName,
                $requestedQuantity,
                $availableQuantity
            ),
            self::ERROR_INSUFFICIENT_STOCK,
            $productName,
            $productId
        );
        
        return $exception->setRequestedQuantity($requestedQuantity)
                         ->setAvailableQuantity($availableQuantity);
    }

    /**
     * 期間重複エラーを作成
     *
     * @param string $productName
     * @param string $period
     * @param int|null $productId
     * @return self
     */
    public static function createPeriodConflictError(string $productName, string $period, int $productId = null): self
    {
        $exception = new self(
            sprintf('「%s」は指定期間（%s）で既に予約されています。', $productName, $period),
            self::ERROR_STOCK_CONFLICT,
            $productName,
            $productId
        );
        
        return $exception->setPeriodInfo($period);
    }

    /**
     * 予約失敗エラーを作成
     *
     * @param string $productName
     * @param string $reason
     * @param int|null $productId
     * @return self
     */
    public static function createReservationFailedError(string $productName, string $reason = '', int $productId = null): self
    {
        $message = sprintf('「%s」の予約に失敗しました。', $productName);
        if ($reason) {
            $message .= ' 理由: ' . $reason;
        }
        
        return new self($message, self::ERROR_RESERVATION_FAILED, $productName, $productId);
    }

    /**
     * 在庫更新失敗エラーを作成
     *
     * @param string $productName
     * @param string $operation
     * @param int|null $productId
     * @return self
     */
    public static function createStockUpdateFailedError(string $productName, string $operation, int $productId = null): self
    {
        return new self(
            sprintf('「%s」の在庫更新（%s）に失敗しました。', $productName, $operation),
            self::ERROR_STOCK_UPDATE_FAILED,
            $productName,
            $productId
        );
    }

    /**
     * 在庫ロックエラーを作成
     *
     * @param string $productName
     * @param int|null $productId
     * @return self
     */
    public static function createInventoryLockedError(string $productName, int $productId = null): self
    {
        return new self(
            sprintf('「%s」の在庫は現在ロックされています。しばらく時間をおいて再度お試しください。', $productName),
            self::ERROR_INVENTORY_LOCKED,
            $productName,
            $productId
        );
    }

    /**
     * メンテナンスモードエラーを作成
     *
     * @param string $productName
     * @param int|null $productId
     * @return self
     */
    public static function createMaintenanceModeError(string $productName, int $productId = null): self
    {
        return new self(
            sprintf('「%s」は現在メンテナンス中のため、レンタルできません。', $productName),
            self::ERROR_MAINTENANCE_MODE,
            $productName,
            $productId
        );
    }

    /**
     * 無効な期間エラーを作成
     *
     * @param string $productName
     * @param string $period
     * @param string $reason
     * @param int|null $productId
     * @return self
     */
    public static function createInvalidPeriodError(
        string $productName, 
        string $period, 
        string $reason,
        int $productId = null
    ): self {
        $exception = new self(
            sprintf('「%s」は指定期間（%s）でレンタルできません。理由: %s', $productName, $period, $reason),
            self::ERROR_INVALID_PERIOD,
            $productName,
            $productId
        );
        
        return $exception->setPeriodInfo($period);
    }

    /**
     * 在庫計算エラーを作成
     *
     * @param string $productName
     * @param string $details
     * @param int|null $productId
     * @return self
     */
    public static function createCalculationError(string $productName, string $details, int $productId = null): self
    {
        $exception = new self(
            sprintf('「%s」の在庫計算でエラーが発生しました。', $productName),
            self::ERROR_STOCK_CALCULATION_ERROR,
            $productName,
            $productId
        );
        
        return $exception->setErrorType(self::TYPE_SYSTEM)
                         ->setErrorLevel(self::LEVEL_ERROR)
                         ->setUserMessage('在庫情報の処理中にエラーが発生しました。管理者にお問い合わせください。')
                         ->addContext('calculation_details', $details);
    }

    /**
     * 在庫同期失敗エラーを作成
     *
     * @param string $productName
     * @param string $reason
     * @param int|null $productId
     * @return self
     */
    public static function createSyncFailedError(string $productName, string $reason, int $productId = null): self
    {
        $exception = new self(
            sprintf('「%s」の在庫同期に失敗しました。', $productName),
            self::ERROR_INVENTORY_SYNC_FAILED,
            $productName,
            $productId
        );
        
        return $exception->setErrorType(self::TYPE_SYSTEM)
                         ->setErrorLevel(self::LEVEL_ERROR)
                         ->setUserMessage('在庫情報の同期中にエラーが発生しました。管理者にお問い合わせください。')
                         ->addContext('sync_failure_reason', $reason);
    }

    /**
     * オーバーコミットエラーを作成
     *
     * @param string $productName
     * @param int $totalCommitted
     * @param int $totalStock
     * @param int|null $productId
     * @return self
     */
    public static function createOvercommittedError(
        string $productName, 
        int $totalCommitted, 
        int $totalStock,
        int $productId = null
    ): self {
        $exception = new self(
            sprintf(
                '「%s」で在庫のオーバーコミットが発生しています。予約済み: %d個、総在庫: %d個',
                $productName,
                $totalCommitted,
                $totalStock
            ),
            self::ERROR_OVERCOMMITTED,
            $productName,
            $productId
        );
        
        return $exception->setErrorType(self::TYPE_SYSTEM)
                         ->setErrorLevel(self::LEVEL_CRITICAL)
                         ->setUserMessage('在庫管理システムでエラーが発生しました。管理者に緊急で報告してください。')
                         ->addContext('total_committed', $totalCommitted)
                         ->addContext('total_stock', $totalStock);
    }

    /**
     * 詳細な在庫情報付きエラーを作成
     *
     * @param string $productName
     * @param int $requestedQuantity
     * @param array $inventoryDetails
     * @param int|null $productId
     * @return self
     */
    public static function createDetailedStockError(
        string $productName,
        int $requestedQuantity,
        array $inventoryDetails,
        int $productId = null
    ): self {
        $available = $inventoryDetails['actual_available'] ?? 0;
        
        $exception = new self(
            sprintf(
                '「%s」の在庫が不足しています。要求: %d個、利用可能: %d個',
                $productName,
                $requestedQuantity,
                $available
            ),
            self::ERROR_INSUFFICIENT_STOCK,
            $productName,
            $productId
        );
        
        return $exception->setRequestedQuantity($requestedQuantity)
                         ->setAvailableQuantity($available)
                         ->setInventoryDetails($inventoryDetails);
    }

    /**
     * エラー情報を配列で取得（拡張版）
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['product_name'] = $this->productName;
        $array['product_id'] = $this->productId;
        $array['requested_quantity'] = $this->requestedQuantity;
        $array['available_quantity'] = $this->availableQuantity;
        $array['period_info'] = $this->periodInfo;
        $array['inventory_details'] = $this->inventoryDetails;
        
        return $array;
    }

    /**
     * 在庫回復可能かどうか
     *
     * @return bool
     */
    public function isStockRecoverable(): bool
    {
        $recoverableCodes = [
            self::ERROR_INSUFFICIENT_STOCK,
            self::ERROR_STOCK_CONFLICT,
            self::ERROR_INVALID_PERIOD,
        ];
        
        return in_array($this->getCode(), $recoverableCodes);
    }

    /**
     * 代替商品提案が有効かどうか
     *
     * @return bool
     */
    public function shouldSuggestAlternatives(): bool
    {
        $suggestCodes = [
            self::ERROR_OUT_OF_STOCK,
            self::ERROR_INSUFFICIENT_STOCK,
            self::ERROR_MAINTENANCE_MODE,
        ];
        
        return in_array($this->getCode(), $suggestCodes);
    }

    /**
     * 管理者通知が必要かどうか
     *
     * @return bool
     */
    public function requiresAdminNotification(): bool
    {
        $criticalCodes = [
            self::ERROR_OVERCOMMITTED,
            self::ERROR_STOCK_CALCULATION_ERROR,
            self::ERROR_INVENTORY_SYNC_FAILED,
        ];
        
        return in_array($this->getCode(), $criticalCodes);
    }
}