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
 * レンタル料金計算 Service
 */
class RentalCalculationService
{
    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

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
                    $rentalProduct->getMaxRentalDays() ?: '制限なし'
                )
            );
        }

        $unitPrice = $this->calculateOptimalPrice($rentalProduct, $days);
        
        if ($unitPrice === null) {
            throw new RentalValidationException('料金設定が見つかりません');
        }

        return bcmul($unitPrice, $quantity, 2);
    }

    /**
     * 最適な料金を計算（日額、週額、月額の中から最安値を選択）
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param int $days 日数
     * @return string|null
     */
    public function calculateOptimalPrice(RentalProduct $rentalProduct, $days)
    {
        $prices = [];

        // 日額計算
        if ($rentalProduct->getDailyPrice()) {
            $prices['daily'] = bcmul($rentalProduct->getDailyPrice(), $days, 2);
        }

        // 週額計算
        if ($rentalProduct->getWeeklyPrice()) {
            $weeks = ceil($days / 7);
            $prices['weekly'] = bcmul($rentalProduct->getWeeklyPrice(), $weeks, 2);
        }

        // 月額計算
        if ($rentalProduct->getMonthlyPrice()) {
            $months = ceil($days / 30);
            $prices['monthly'] = bcmul($rentalProduct->getMonthlyPrice(), $months, 2);
        }

        if (empty($prices)) {
            return null;
        }

        // 最安値を返す
        return min($prices);
    }

    /**
     * 料金詳細を計算
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param int $days 日数
     * @return array
     */
    public function calculatePriceDetails(RentalProduct $rentalProduct, $days)
    {
        $details = [
            'daily' => null,
            'weekly' => null,
            'monthly' => null,
            'optimal' => null,
            'optimal_type' => null,
        ];

        $prices = [];

        // 日額計算
        if ($rentalProduct->getDailyPrice()) {
            $dailyTotal = bcmul($rentalProduct->getDailyPrice(), $days, 2);
            $details['daily'] = [
                'unit_price' => $rentalProduct->getDailyPrice(),
                'days' => $days,
                'total' => $dailyTotal,
            ];
            $prices['daily'] = $dailyTotal;
        }

        // 週額計算
        if ($rentalProduct->getWeeklyPrice()) {
            $weeks = ceil($days / 7);
            $weeklyTotal = bcmul($rentalProduct->getWeeklyPrice(), $weeks, 2);
            $details['weekly'] = [
                'unit_price' => $rentalProduct->getWeeklyPrice(),
                'weeks' => $weeks,
                'total' => $weeklyTotal,
            ];
            $prices['weekly'] = $weeklyTotal;
        }

        // 月額計算
        if ($rentalProduct->getMonthlyPrice()) {
            $months = ceil($days / 30);
            $monthlyTotal = bcmul($rentalProduct->getMonthlyPrice(), $months, 2);
            $details['monthly'] = [
                'unit_price' => $rentalProduct->getMonthlyPrice(),
                'months' => $months,
                'total' => $monthlyTotal,
            ];
            $prices['monthly'] = $monthlyTotal;
        }

        // 最適料金を決定
        if (!empty($prices)) {
            $minPrice = min($prices);
            $optimalType = array_search($minPrice, $prices);
            $details['optimal'] = $minPrice;
            $details['optimal_type'] = $optimalType;
        }

        return $details;
    }

    /**
     * 保証金額を計算
     *
     * @param RentalOrder $order レンタル注文
     * @return string
     */
    public function calculateDepositAmount(RentalOrder $order)
    {
        $rentalProduct = $order->getRentalProduct();
        
        if ($rentalProduct->getDepositAmount()) {
            return bcmul($rentalProduct->getDepositAmount(), $order->getQuantity(), 2);
        }

        // 設定で保証金率が設定されている場合
        $depositRate = $this->configRepository->getFloat('deposit_rate', 0.0);
        if ($depositRate > 0) {
            return bcmul($order->getTotalAmount(), $depositRate, 2);
        }

        return '0';
    }

    /**
     * 延滞料金を計算
     *
     * @param RentalOrder $order レンタル注文
     * @param \DateTime $actualReturnDate 実際の返却日
     * @return string
     */
    public function calculateOverdueFee(RentalOrder $order, \DateTime $actualReturnDate)
    {
        if ($actualReturnDate <= $order->getRentalEndDate()) {
            return '0';
        }

        $overdueDays = $order->getRentalEndDate()->diff($actualReturnDate)->days;
        $overdueRate = $this->configRepository->getOverdueFeeRate();

        // 基本レンタル料金の延滞率 × 延滞日数
        $dailyOverdueFee = bcmul($order->getTotalAmount(), $overdueRate, 2);
        return bcmul($dailyOverdueFee, $overdueDays, 2);
    }

    /**
     * 延長料金を計算
     *
     * @param RentalOrder $order レンタル注文
     * @param int $extensionDays 延長日数
     * @return string
     */
    public function calculateExtensionFee(RentalOrder $order, $extensionDays)
    {
        if ($extensionDays <= 0) {
            return '0';
        }

        $rentalProduct = $order->getRentalProduct();
        
        // 延長料金率が設定されている場合
        if ($rentalProduct->getExtensionFeeRate()) {
            $originalDays = $this->calculateRentalDays($order->getRentalStartDate(), $order->getRentalEndDate());
            $dailyRate = bcdiv($order->getTotalAmount(), $originalDays, 4);
            $extensionRate = $rentalProduct->getExtensionFeeRate();
            $dailyExtensionFee = bcmul($dailyRate, $extensionRate, 2);
            return bcmul($dailyExtensionFee, $extensionDays, 2);
        }

        // 通常の日額料金で計算
        if ($rentalProduct->getDailyPrice()) {
            return bcmul($rentalProduct->getDailyPrice(), $extensionDays, 2);
        }

        // フォールバック：元の料金から日割り計算
        $originalDays = $this->calculateRentalDays($order->getRentalStartDate(), $order->getRentalEndDate());
        $dailyRate = bcdiv($order->getTotalAmount(), $originalDays, 4);
        return bcmul($dailyRate, $extensionDays, 2);
    }

    /**
     * 早期返却割引を計算
     *
     * @param RentalOrder $order レンタル注文
     * @param \DateTime $actualReturnDate 実際の返却日
     * @return string
     */
    public function calculateEarlyReturnDiscount(RentalOrder $order, \DateTime $actualReturnDate)
    {
        if ($actualReturnDate >= $order->getRentalEndDate()) {
            return '0';
        }

        $rentalProduct = $order->getRentalProduct();
        $earlyReturnDiscount = $rentalProduct->getEarlyReturnDiscount();
        
        if (!$earlyReturnDiscount) {
            return '0';
        }

        $earlyDays = $actualReturnDate->diff($order->getRentalEndDate())->days;
        $originalDays = $this->calculateRentalDays($order->getRentalStartDate(), $order->getRentalEndDate());
        
        $dailyRate = bcdiv($order->getTotalAmount(), $originalDays, 4);
        $savedAmount = bcmul($dailyRate, $earlyDays, 2);
        
        return bcmul($savedAmount, $earlyReturnDiscount, 2);
    }

    /**
     * 配送料を計算
     *
     * @param RentalOrder $order レンタル注文
     * @param string $deliveryMethod 配送方法
     * @return string
     */
    public function calculateDeliveryFee(RentalOrder $order, $deliveryMethod = 'standard')
    {
        $deliveryFee = $this->configRepository->get('default_delivery_fee', '500');
        $freeDeliveryAmount = $this->configRepository->get('free_delivery_amount', '5000');

        // 送料無料条件チェック
        if (bccomp($order->getTotalAmount(), $freeDeliveryAmount, 2) >= 0) {
            return '0';
        }

        // 配送方法別料金（今後の拡張用）
        switch ($deliveryMethod) {
            case 'express':
                return bcmul($deliveryFee, '1.5', 2);
            case 'mail':
                return bcdiv($deliveryFee, '2', 2);
            default:
                return $deliveryFee;
        }
    }

    /**
     * 保険料を計算
     *
     * @param RentalOrder $order レンタル注文
     * @return string
     */
    public function calculateInsuranceFee(RentalOrder $order)
    {
        $rentalProduct = $order->getRentalProduct();
        
        if (!$rentalProduct->getInsuranceFee()) {
            return '0';
        }

        $days = $this->calculateRentalDays($order->getRentalStartDate(), $order->getRentalEndDate());
        return bcmul($rentalProduct->getInsuranceFee(), $days, 2);
    }

    /**
     * 総額を計算（全ての料金を含む）
     *
     * @param RentalOrder $order レンタル注文
     * @param array $options オプション
     * @return array
     */
    public function calculateTotalAmount(RentalOrder $order, array $options = [])
    {
        $breakdown = [
            'rental_fee' => $order->getTotalAmount(),
            'deposit_amount' => $order->getDepositAmount() ?: '0',
            'delivery_fee' => '0',
            'insurance_fee' => '0',
            'extension_fee' => $order->getExtensionFee() ?: '0',
            'overdue_fee' => $order->getOverdueFee() ?: '0',
            'damage_fee' => $order->getDamageFee() ?: '0',
            'cleaning_fee' => $order->getCleaningFee() ?: '0',
            'early_return_discount' => $order->getEarlyReturnDiscount() ?: '0',
        ];

        // 配送料計算
        if (isset($options['delivery_method'])) {
            $breakdown['delivery_fee'] = $this->calculateDeliveryFee($order, $options['delivery_method']);
        }

        // 保険料計算
        if (isset($options['insurance']) && $options['insurance']) {
            $breakdown['insurance_fee'] = $this->calculateInsuranceFee($order);
        }

        // 総額計算
        $total = '0';
        foreach ($breakdown as $key => $amount) {
            if ($key === 'early_return_discount') {
                $total = bcsub($total, $amount, 2);
            } else {
                $total = bcadd($total, $amount, 2);
            }
        }

        $breakdown['total'] = $total;

        return $breakdown;
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
     * 料金の妥当性をチェック
     *
     * @param string $amount 金額
     * @return bool
     */
    public function validateAmount($amount)
    {
        return is_numeric($amount) && bccomp($amount, '0', 2) >= 0;
    }

    /**
     * 割引率を適用
     *
     * @param string $amount 元の金額
     * @param float $discountRate 割引率（0.1 = 10%割引）
     * @return string
     */
    public function applyDiscount($amount, $discountRate)
    {
        if ($discountRate <= 0 || $discountRate >= 1) {
            return $amount;
        }

        $discountAmount = bcmul($amount, $discountRate, 2);
        return bcsub($amount, $discountAmount, 2);
    }

    /**
     * 税込み価格を計算
     *
     * @param string $amount 税抜き金額
     * @param float $taxRate 税率（0.1 = 10%）
     * @return string
     */
    public function calculateTaxIncluded($amount, $taxRate = 0.1)
    {
        $taxAmount = bcmul($amount, $taxRate, 2);
        return bcadd($amount, $taxAmount, 2);
    }

    /**
     * 単価を計算
     *
     * @param string $totalAmount 総額
     * @param int $quantity 数量
     * @return string
     */
    public function calculateUnitPrice($totalAmount, $quantity)
    {
        if ($quantity <= 0) {
            return '0';
        }

        return bcdiv($totalAmount, $quantity, 2);
    }

    /**
     * 期間別の料金比較データを取得
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param int $maxDays 最大日数
     * @return array
     */
    public function getPriceComparison(RentalProduct $rentalProduct, $maxDays = 30)
    {
        $comparison = [];
        
        for ($days = 1; $days <= $maxDays; $days++) {
            if ($rentalProduct->isValidRentalPeriod($days)) {
                $comparison[$days] = $this->calculatePriceDetails($rentalProduct, $days);
            }
        }

        return $comparison;
    }
}