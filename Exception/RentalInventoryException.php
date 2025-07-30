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
     * 在庫エラーの種類
     */
    const INVENTORY_TYPE_OUT_OF_STOCK = 'out_of_stock';
    const INVENTORY_TYPE_INSUFFICIENT_STOCK = 'insufficient_stock';
    const INVENTORY_TYPE_RESERVED = 'reserved';
    const INVENTORY_TYPE_MAINTENANCE = 'maintenance';
    const INVENTORY_TYPE_DAMAGED = 'damaged';
    const INVENTORY_TYPE_UNAVAILABLE_PERIOD = 'unavailable_period';
    const INVENTORY_TYPE_CONFLICT = 'conflict';
    const INVENTORY_TYPE_EXPIRED = 'expired';
    const INVENTORY_TYPE_NOT_FOUND = 'not_found';

    /**
     * 商品名
     *
     * @var string|null
     */
    private $productName;

    /**
     * 商品ID
     *
     * @var int|null
     */
    private $productId;

    /**
     * 在庫エラーの種類
     *
     * @var string
     */
    private $inventoryType;

    /**
     * 要求数量
     *
     * @var int|null
     */
    private $requestedQuantity;

    /**
     * 利用可能数量
     *
     * @var int|null
     */
    private $availableQuantity;

    /**
     * レンタル開始日
     *
     * @var \DateTime|null
     */
    private $startDate;

    /**
     * レンタル終了日
     *
     * @var \DateTime|null
     */
    private $endDate;

    /**
     * 代替案
     *
     * @var array
     */
    private $alternatives = [];

    /**
     * 次回利用可能日
     *
     * @var \DateTime|null
     */
    private $nextAvailableDate;

    /**
     * コンストラクタ
     *
     * @param string $message エラーメッセージ
     * @param string $inventoryType 在庫エラー種類
     * @param string|null $productName 商品名
     * @param int|null $productId 商品ID
     * @param int|null $requestedQuantity 要求数量
     * @param int|null $availableQuantity 利用可能数量
     * @param \DateTime|null $startDate レンタル開始日
     * @param \DateTime|null $endDate レンタル終了日
     * @param array $alternatives 代替案
     * @param \DateTime|null $nextAvailableDate 次回利用可能日
     * @param \Throwable|null $previous 前の例外
     */
    public function __construct(
        string $message,
        string $inventoryType = self::INVENTORY_TYPE_OUT_OF_STOCK,
        string $productName = null,
        int $productId = null,
        int $requestedQuantity = null,
        int $availableQuantity = null,
        \DateTime $startDate = null,
        \DateTime $endDate = null,
        array $alternatives = [],
        \DateTime $nextAvailableDate = null,
        \Throwable $previous = null
    ) {
        $this->inventoryType = $inventoryType;
        $this->productName = $productName;
        $this->productId = $productId;
        $this->requestedQuantity = $requestedQuantity;
        $this->availableQuantity = $availableQuantity;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->alternatives = $alternatives;
        $this->nextAvailableDate = $nextAvailableDate;

        $details = [
            'inventory_type' => $inventoryType,
            'product_name' => $productName,
            'product_id' => $productId,
            'requested_quantity' => $requestedQuantity,
            'available_quantity' => $availableQuantity,
            'start_date' => $startDate?->format('Y-m-d'),
            'end_date' => $endDate?->format('Y-m-d'),
            'alternatives' => $alternatives,
            'next_available_date' => $nextAvailableDate?->format('Y-m-d'),
        ];

        // ユーザーフレンドリーなメッセージを生成
        $userMessage = $this->generateUserMessage($inventoryType, $productName, $requestedQuantity, $availableQuantity);

        parent::__construct($message, self::ERROR_CODE_INVENTORY, $previous, $details, $userMessage);
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
     * 商品IDを取得
     *
     * @return int|null
     */
    public function getProductId(): ?int
    {
        return $this->productId;
    }

    /**
     * 在庫エラー種類を取得
     *
     * @return string
     */
    public function getInventoryType(): string
    {
        return $this->inventoryType;
    }

    /**
     * 要求数量を取得
     *
     * @return int|null
     */
    public function getRequestedQuantity(): ?int
    {
        return $this->requestedQuantity;
    }

    /**
     * 利用可能数量を取得
     *
     * @return int|null
     */
    public function getAvailableQuantity(): ?int
    {
        return $this->availableQuantity;
    }

    /**
     * レンタル開始日を取得
     *
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * レンタル終了日を取得
     *
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * 代替案を取得
     *
     * @return array
     */
    public function getAlternatives(): array
    {
        return $this->alternatives;
    }

    /**
     * 代替案を追加
     *
     * @param array $alternative
     * @return self
     */
    public function addAlternative(array $alternative): self
    {
        $this->alternatives[] = $alternative;
        return $this;
    }

    /**
     * 次回利用可能日を取得
     *
     * @return \DateTime|null
     */
    public function getNextAvailableDate(): ?\DateTime
    {
        return $this->nextAvailableDate;
    }

    /**
     * 代替案があるかどうか
     *
     * @return bool
     */
    public function hasAlternatives(): bool
    {
        return !empty($this->alternatives);
    }

    /**
     * ユーザーフレンドリーなメッセージを生成
     *
     * @param string $inventoryType
     * @param string|null $productName
     * @param int|null $requestedQuantity
     * @param int|null $availableQuantity
     * @return string
     */
    private function generateUserMessage(
        string $inventoryType,
        ?string $productName,
        ?int $requestedQuantity,
        ?int $availableQuantity
    ): string {
        $product = $productName ?: '商品';

        switch ($inventoryType) {
            case self::INVENTORY_TYPE_OUT_OF_STOCK:
                return "{$product}の在庫がありません。";
            
            case self::INVENTORY_TYPE_INSUFFICIENT_STOCK:
                if ($requestedQuantity && $availableQuantity !== null) {
                    return "{$product}の在庫が不足しています。要求数量: {$requestedQuantity}個、利用可能: {$availableQuantity}個";
                }
                return "{$product}の在庫が不足しています。";
            
            case self::INVENTORY_TYPE_RESERVED:
                return "{$product}は既に予約済みです。";
            
            case self::INVENTORY_TYPE_MAINTENANCE:
                return "{$product}はメンテナンス中のため利用できません。";
            
            case self::INVENTORY_TYPE_DAMAGED:
                return "{$product}は破損のため利用できません。";
            
            case self::INVENTORY_TYPE_UNAVAILABLE_PERIOD:
                return "指定された期間は{$product}をご利用いただけません。";
            
            case self::INVENTORY_TYPE_CONFLICT:
                return "{$product}の予約が重複しています。";
            
            case self::INVENTORY_TYPE_EXPIRED:
                return "{$product}の在庫予約が期限切れです。";
            
            case self::INVENTORY_TYPE_NOT_FOUND:
                return "{$product}が見つかりません。";
            
            default:
                return "{$product}をご利用いただけません。";
        }
    }

    /**
     * 在庫切れエラーを作成
     *
     * @param string $productName
     * @param int $productId
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return static
     */
    public static function outOfStock(
        string $productName,
        int $productId,
        \DateTime $startDate = null,
        \DateTime $endDate = null
    ): self {
        return new static(
            "Product '{$productName}' is out of stock",
            self::INVENTORY_TYPE_OUT_OF_STOCK,
            $productName,
            $productId,
            null,
            0,
            $startDate,
            $endDate
        );
    }

    /**
     * 在庫不足エラーを作成
     *
     * @param string $productName
     * @param int $productId
     * @param int $requestedQuantity
     * @param int $availableQuantity
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return static
     */
    public static function insufficientStock(
        string $productName,
        int $productId,
        int $requestedQuantity,
        int $availableQuantity,
        \DateTime $startDate = null,
        \DateTime $endDate = null
    ): self {
        return new static(
            "Insufficient stock for product '{$productName}'. Requested: {$requestedQuantity}, Available: {$availableQuantity}",
            self::INVENTORY_TYPE_INSUFFICIENT_STOCK,
            $productName,
            $productId,
            $requestedQuantity,
            $availableQuantity,
            $startDate,
            $endDate
        );
    }

    /**
     * 予約済みエラーを作成
     *
     * @param string $productName
     * @param int $productId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param \DateTime|null $nextAvailableDate
     * @return static
     */
    public static function reserved(
        string $productName,
        int $productId,
        \DateTime $startDate,
        \DateTime $endDate,
        \DateTime $nextAvailableDate = null
    ): self {
        return new static(
            "Product '{$productName}' is already reserved for the requested period",
            self::INVENTORY_TYPE_RESERVED,
            $productName,
            $productId,
            null,
            null,
            $startDate,
            $endDate,
            [],
            $nextAvailableDate
        );
    }

    /**
     * メンテナンス中エラーを作成
     *
     * @param string $productName
     * @param int $productId
     * @param \DateTime|null $maintenanceEndDate
     * @return static
     */
    public static function maintenance(
        string $productName,
        int $productId,
        \DateTime $maintenanceEndDate = null
    ): self {
        return new static(
            "Product '{$productName}' is under maintenance",
            self::INVENTORY_TYPE_MAINTENANCE,
            $productName,
            $productId,
            null,
            null,
            null,
            null,
            [],
            $maintenanceEndDate
        );
    }

    /**
     * 破損エラーを作成
     *
     * @param string $productName
     * @param int $productId
     * @param \DateTime|null $repairEstimateDate
     * @return static
     */
    public static function damaged(
        string $productName,
        int $productId,
        \DateTime $repairEstimateDate = null
    ): self {
        return new static(
            "Product '{$productName}' is damaged and unavailable",
            self::INVENTORY_TYPE_DAMAGED,
            $productName,
            $productId,
            null,
            null,
            null,
            null,
            [],
            $repairEstimateDate
        );
    }

    /**
     * 利用不可期間エラーを作成
     *
     * @param string $productName
     * @param int $productId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param string $reason
     * @return static
     */
    public static function unavailablePeriod(
        string $productName,
        int $productId,
        \DateTime $startDate,
        \DateTime $endDate,
        string $reason = ''
    ): self {
        $exception = new static(
            "Product '{$productName}' is unavailable for the requested period",
            self::INVENTORY_TYPE_UNAVAILABLE_PERIOD,
            $productName,
            $productId,
            null,
            null,
            $startDate,
            $endDate
        );

        if ($reason) {
            $exception->addDetail('reason', $reason);
        }

        return $exception;
    }

    /**
     * 競合エラーを作成
     *
     * @param string $productName
     * @param int $productId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $conflictingReservations
     * @return static
     */
    public static function conflict(
        string $productName,
        int $productId,
        \DateTime $startDate,
        \DateTime $endDate,
        array $conflictingReservations = []
    ): self {
        $exception = new static(
            "Rental period conflicts with existing reservations for product '{$productName}'",
            self::INVENTORY_TYPE_CONFLICT,
            $productName,
            $productId,
            null,
            null,
            $startDate,
            $endDate
        );

        if (!empty($conflictingReservations)) {
            $exception->addDetail('conflicting_reservations', $conflictingReservations);
        }

        return $exception;
    }

    /**
     * 代替案付きの在庫不足エラーを作成
     *
     * @param string $productName
     * @param int $productId
     * @param int $requestedQuantity
     * @param int $availableQuantity
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $alternatives
     * @return static
     */
    public static function insufficientStockWithAlternatives(
        string $productName,
        int $productId,
        int $requestedQuantity,
        int $availableQuantity,
        \DateTime $startDate,
        \DateTime $endDate,
        array $alternatives = []
    ): self {
        return new static(
            "Insufficient stock for product '{$productName}' but alternatives are available",
            self::INVENTORY_TYPE_INSUFFICIENT_STOCK,
            $productName,
            $productId,
            $requestedQuantity,
            $availableQuantity,
            $startDate,
            $endDate,
            $alternatives
        );
    }
}