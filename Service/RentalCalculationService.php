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

namespace Plugin\Rental\Service;

use Plugin\Rental\Entity\RentalProduct;
use Plugin\Rental\Entity\RentalOrder;
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Exception\RentalValidationException;

/**
 * レンタル料金計算 Service (完全版)
 */
class RentalCalculationService
{
    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * 料金タイプ定数
     */
    const PRICE_TYPE_DAILY = 'daily';
    const PRICE_TYPE_WEEKLY = 'weekly';
    const PRICE_TYPE_MONTHLY = 'monthly';

    /**
     * コンストラクタ
     *
     * @param RentalConfigRepository $configRepository
     */
    public function __construct(RentalConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * レンタル料金を計算
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param int $quantity 数量
     * @return string
     * @throws RentalValidationException
     */
    public function calculateRentalPrice(RentalProduct $rentalProduct, \DateTime $startDate, \DateTime $endDate, $quantity = 1)
    {
        if ($startDate >= $endDate) {
            throw new RentalValidationException('開始日は終了日より前である必要があります');
        }

        if ($quantity <= 0) {
            throw new RentalValidationException('数量は1以上である必要があります');
        }

        $days = $this->calculateRentalDays($startDate, $endDate);

        // 期間チェック
        if (!$rentalProduct->isValidRentalPeriod($days)) {
            throw new RentalValidationException(
                sprintf(
                    'レンタル期間は%d日以上%d日以下である必要があります',
                    $rentalProduct->getMinRentalDays(),
                    $rentalProduct->getMaxRentalDays() ?? 999
                )
            );
        }

        // 基本料金計算
        $basePrice = $this->calculateBasePrice($rentalProduct, $days);

        // 数量を適用
        $totalPrice = bcmul($basePrice, (string)$quantity, 2);

        // 割引適用
        $discountAmount = $this->calculateDiscount($rentalProduct, $totalPrice, $days);
        $totalPrice = bcsub($totalPrice, $discountAmount, 2);

        // 保険料追加
        if ($rentalProduct->getInsuranceFee()) {
            $insuranceFee = bcmul($rentalProduct->getInsuranceFee(), (string)$quantity, 2);
            $totalPrice = bcadd($totalPrice, $insuranceFee, 2);
        }

        // 税計算（将来的な拡張用）
        $tax = $this->calculateTax($totalPrice);
        $totalPrice = bcadd($totalPrice, $tax, 2);

        return $totalPrice;
    }

    /**
     * レンタル日数を計算
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return int
     */
    public function calculateRentalDays(\DateTime $startDate, \DateTime $endDate)
    {
        return $startDate->diff($endDate)->days + 1;
    }

    /**
     * 基本料金を計算
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param int $days 日数
     * @return string
     */
    private function calculateBasePrice(RentalProduct $rentalProduct, $days)
    {
        $priceType = $rentalProduct->getPriceType();
        $basePrice = $rentalProduct->getRentalPrice();

        switch ($priceType) {
            case self::PRICE_TYPE_DAILY:
                return bcmul($basePrice, (string)$days, 2);

            case self::PRICE_TYPE_WEEKLY:
                $weeks = ceil($days / 7);
                return bcmul($basePrice, (string)$weeks, 2);

            case self::PRICE_TYPE_MONTHLY:
                $months = ceil($days / 30);
                return bcmul($basePrice, (string)$months, 2);

            default:
                // デフォルトは日割り計算
                return bcmul($basePrice, (string)$days, 2);
        }
    }

    /**
     * 割引額を計算
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param string $totalPrice 合計金額
     * @param int $days 日数
     * @return string
     */
    private function calculateDiscount(RentalProduct $rentalProduct, $totalPrice, $days)
    {
        $discountAmount = '0';

        // 長期割引
        if ($days >= 30) {
            $longTermDiscountRate = $this->configRepository->getFloat('long_term_discount_rate', 0.1);
            $longTermDiscount = bcmul($totalPrice, (string)$longTermDiscountRate, 2);
            $discountAmount = bcadd($discountAmount, $longTermDiscount, 2);
        } elseif ($days >= 14) {
            $mediumTermDiscountRate = $this->configRepository->getFloat('medium_term_discount_rate', 0.05);
            $mediumTermDiscount = bcmul($totalPrice, (string)$mediumTermDiscountRate, 2);
            $discountAmount = bcadd($discountAmount, $mediumTermDiscount, 2);
        }

        // 商品固有の割引
        if ($rentalProduct->getDiscountRate()) {
            $productDiscount = bcmul($totalPrice, $rentalProduct->getDiscountRate(), 2);
            $discountAmount = bcadd($discountAmount, $productDiscount, 2);
        }

        return $discountAmount;
    }

    /**
     * 税額を計算
     *
     * @param string $price 金額
     * @return string
     */
    private function calculateTax($price)
    {
        $taxRate = $this->configRepository->getFloat('tax_rate', 0.1); // 10%
        return bcmul($price, (string)$taxRate, 2);
    }

    /**
     * 保証金を計算
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param int $quantity 数量
     * @return string
     */
    public function calculateDepositAmount(RentalProduct $rentalProduct, $quantity = 1)
    {
        if (!$this->configRepository->isDepositRequired()) {
            return '0';
        }

        $depositRate = $this->configRepository->getFloat('deposit_rate', 0.3); // 30%
        $productPrice = $rentalProduct->getProduct()->getPrice02() ?? '0';
        
        $depositPerItem = bcmul($productPrice, (string)$depositRate, 2);
        return bcmul($depositPerItem, (string)$quantity, 2);
    }

    /**
     * 延滞料金を計算
     *
     * @param RentalOrder $order レンタル注文
     * @param int $overdueDays 延滞日数
     * @return string
     */
    public function calculateOverdueFee(RentalOrder $order, $overdueDays)
    {
        if ($overdueDays <= 0) {
            return '0';
        }

        $feeRate = $this->configRepository->getOverdueFeeRate();
        $baseAmount = $order->getTotalAmount();
        
        // 日割りで延滞料金を計算
        $dailyFee = bcmul($baseAmount, (string)$feeRate, 2);
        return bcmul($dailyFee, (string)$overdueDays, 2);
    }

    /**
     * 延長料金を計算
     *
     * @param RentalOrder $order レンタル注文
     * @param \DateTime $newEndDate 新しい終了日
     * @return string
     */
    public function calculateExtensionFee(RentalOrder $order, \DateTime $newEndDate)
    {
        $originalEndDate = $order->getRentalEndDate();
        
        if ($newEndDate <= $originalEndDate) {
            return '0';
        }

        $extensionDays = $originalEndDate->diff($newEndDate)->days;
        $rentalProduct = $order->getRentalProduct();

        // 延長料金率を適用
        $extensionRate = $rentalProduct->getExtensionFeeRate() ?? 
                        $this->configRepository->getFloat('default_extension_rate', 1.0);

        $baseDailyRate = bcdiv($order->getTotalAmount(), (string)$this->calculateRentalDays(
            $order->getRentalStartDate(),
            $originalEndDate
        ), 2);

        $extensionFee = bcmul($baseDailyRate, (string)$extensionDays, 2);
        return bcmul($extensionFee, (string)$extensionRate, 2);
    }

    /**
     * 早期返却割引を計算
     *
     * @param RentalOrder $order レンタル注文
     * @param \DateTime $returnDate 実際の返却日
     * @return string
     */
    public function calculateEarlyReturnDiscount(RentalOrder $order, \DateTime $returnDate)
    {
        $originalEndDate = $order->getRentalEndDate();
        
        if ($returnDate >= $originalEndDate) {
            return '0';
        }

        $rentalProduct = $order->getRentalProduct();
        $earlyReturnDiscountRate = $rentalProduct->getEarlyReturnDiscount() ?? 
                                  $this->configRepository->getFloat('early_return_discount_rate', 0.1);

        if ($earlyReturnDiscountRate <= 0) {
            return '0';
        }

        $savedDays = $returnDate->diff($originalEndDate)->days;
        $totalDays = $this->calculateRentalDays(
            $order->getRentalStartDate(),
            $originalEndDate
        );

        // 節約できた日数分の割引
        $dailyRate = bcdiv($order->getTotalAmount(), (string)$totalDays, 2);
        $savedAmount = bcmul($dailyRate, (string)$savedDays, 2);
        
        return bcmul($savedAmount, (string)$earlyReturnDiscountRate, 2);
    }

    /**
     * 交換料金を計算
     *
     * @param RentalProduct $fromProduct 元商品
     * @param RentalProduct $toProduct 交換先商品
     * @param int $remainingDays 残り日数
     * @return string
     */
    public function calculateReplacementFee(RentalProduct $fromProduct, RentalProduct $toProduct, $remainingDays)
    {
        $fromDailyRate = bcdiv($fromProduct->getRentalPrice(), '1', 2);
        $toDailyRate = bcdiv($toProduct->getRentalPrice(), '1', 2);

        if ($fromProduct->getPriceType() === self::PRICE_TYPE_WEEKLY) {
            $fromDailyRate = bcdiv($fromProduct->getRentalPrice(), '7', 2);
        } elseif ($fromProduct->getPriceType() === self::PRICE_TYPE_MONTHLY) {
            $fromDailyRate = bcdiv($fromProduct->getRentalPrice(), '30', 2);
        }

        if ($toProduct->getPriceType() === self::PRICE_TYPE_WEEKLY) {
            $toDailyRate = bcdiv($toProduct->getRentalPrice(), '7', 2);
        } elseif ($toProduct->getPriceType() === self::PRICE_TYPE_MONTHLY) {
            $toDailyRate = bcdiv($toProduct->getRentalPrice(), '30', 2);
        }

        $priceDifference = bcsub($toDailyRate, $fromDailyRate, 2);
        
        if (bccomp($priceDifference, '0', 2) <= 0) {
            return '0'; // 安い商品への交換の場合は追加料金なし
        }

        $additionalFee = bcmul($priceDifference, (string)$remainingDays, 2);

        // 交換手数料を追加
        $replacementFee = $toProduct->getReplacementFee() ?? 
                         $this->configRepository->getFloat('default_replacement_fee', 0);

        return bcadd($additionalFee, (string)$replacementFee, 2);
    }

    /**
     * 料金詳細を取得
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param int $quantity 数量
     * @return array
     */
    public function getDetailedPricing(RentalProduct $rentalProduct, \DateTime $startDate, \DateTime $endDate, $quantity = 1)
    {
        $days = $this->calculateRentalDays($startDate, $endDate);
        $basePrice = $this->calculateBasePrice($rentalProduct, $days);
        $basePriceWithQuantity = bcmul($basePrice, (string)$quantity, 2);
        
        $discountAmount = $this->calculateDiscount($rentalProduct, $basePriceWithQuantity, $days);
        $priceAfterDiscount = bcsub($basePriceWithQuantity, $discountAmount, 2);
        
        $insuranceFee = '0';
        if ($rentalProduct->getInsuranceFee()) {
            $insuranceFee = bcmul($rentalProduct->getInsuranceFee(), (string)$quantity, 2);
        }
        
        $subtotal = bcadd($priceAfterDiscount, $insuranceFee, 2);
        $tax = $this->calculateTax($subtotal);
        $total = bcadd($subtotal, $tax, 2);
        
        $depositAmount = $this->calculateDepositAmount($rentalProduct, $quantity);

        return [
            'days' => $days,
            'base_price' => $basePrice,
            'quantity' => $quantity,
            'base_price_with_quantity' => $basePriceWithQuantity,
            'discount_amount' => $discountAmount,
            'price_after_discount' => $priceAfterDiscount,
            'insurance_fee' => $insuranceFee,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'deposit_amount' => $depositAmount,
            'grand_total' => bcadd($total, $depositAmount, 2)
        ];
    }

    /**
     * 複数商品の料金を計算
     *
     * @param array $items 商品配列 [['product' => RentalProduct, 'start' => DateTime, 'end' => DateTime, 'quantity' => int], ...]
     * @return array
     */
    public function calculateMultipleItems(array $items)
    {
        $totalAmount = '0';
        $totalDeposit = '0';
        $itemDetails = [];

        foreach ($items as $item) {
            $pricing = $this->getDetailedPricing(
                $item['product'],
                $item['start'],
                $item['end'],
                $item['quantity']
            );

            $itemDetails[] = array_merge($pricing, [
                'product_name' => $item['product']->getProduct()->getName(),
                'product_id' => $item['product']->getId()
            ]);

            $totalAmount = bcadd($totalAmount, $pricing['total'], 2);
            $totalDeposit = bcadd($totalDeposit, $pricing['deposit_amount'], 2);
        }

        return [
            'items' => $itemDetails,
            'total_amount' => $totalAmount,
            'total_deposit' => $totalDeposit,
            'grand_total' => bcadd($totalAmount, $totalDeposit, 2)
        ];
    }

    /**
     * 利益率を計算
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param string $rentalPrice レンタル料金
     * @return float
     */
    public function calculateProfitMargin(RentalProduct $rentalProduct, $rentalPrice)
    {
        $productCost = $rentalProduct->getProduct()->getPrice01() ?? '0'; // 原価
        
        if (bccomp($rentalPrice, '0', 2) <= 0) {
            return 0.0;
        }

        $profit = bcsub($rentalPrice, $productCost, 2);
        $margin = bcdiv($profit, $rentalPrice, 4);
        
        return (float)bcmul($margin, '100', 2); // パーセンテージで返す
    }
}