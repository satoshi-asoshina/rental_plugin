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

use Plugin\Rental\Repository\RentalOrderRepository;
use Plugin\Rental\Repository\RentalProductRepository;
use Plugin\Rental\Repository\RentalInventoryRepository;
use Plugin\Rental\Repository\RentalPaymentRepository;
use Plugin\Rental\Repository\RentalCartRepository;
use Plugin\Rental\Repository\RentalLogRepository;
use Plugin\Rental\Repository\RentalConfigRepository;

/**
 * レンタル分析サービス
 */
class RentalAnalyticsService
{
    /**
     * @var RentalOrderRepository
     */
    private $orderRepository;

    /**
     * @var RentalProductRepository
     */
    private $productRepository;

    /**
     * @var RentalInventoryRepository
     */
    private $inventoryRepository;

    /**
     * @var RentalPaymentRepository
     */
    private $paymentRepository;

    /**
     * @var RentalCartRepository
     */
    private $cartRepository;

    /**
     * @var RentalLogRepository
     */
    private $logRepository;

    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * コンストラクタ
     */
    public function __construct(
        RentalOrderRepository $orderRepository,
        RentalProductRepository $productRepository,
        RentalInventoryRepository $inventoryRepository,
        RentalPaymentRepository $paymentRepository,
        RentalCartRepository $cartRepository,
        RentalLogRepository $logRepository,
        RentalConfigRepository $configRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->inventoryRepository = $inventoryRepository;
        $this->paymentRepository = $paymentRepository;
        $this->cartRepository = $cartRepository;
        $this->logRepository = $logRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * ダッシュボード用の概要データを取得
     *
     * @return array
     */
    public function getDashboardSummary()
    {
        $today = new \DateTime('today');
        $thisMonth = new \DateTime('first day of this month');
        $lastMonth = new \DateTime('first day of last month');
        $lastMonthEnd = new \DateTime('last day of last month');

        return [
            'today' => [
                'orders' => $this->getOrderCount($today, $today),
                'revenue' => $this->getRevenue($today, $today),
                'new_customers' => $this->getNewCustomerCount($today, $today),
                'active_rentals' => $this->getActiveRentalCount()
            ],
            'this_month' => [
                'orders' => $this->getOrderCount($thisMonth, $today),
                'revenue' => $this->getRevenue($thisMonth, $today),
                'new_customers' => $this->getNewCustomerCount($thisMonth, $today),
                'overdue_count' => $this->getOverdueCount()
            ],
            'last_month' => [
                'orders' => $this->getOrderCount($lastMonth, $lastMonthEnd),
                'revenue' => $this->getRevenue($lastMonth, $lastMonthEnd),
                'new_customers' => $this->getNewCustomerCount($lastMonth, $lastMonthEnd)
            ],
            'growth_rates' => $this->calculateGrowthRates($thisMonth, $today, $lastMonth, $lastMonthEnd)
        ];
    }

    /**
     * 売上分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param string $groupBy グループ化（day, week, month）
     * @return array
     */
    public function getSalesAnalysis(\DateTime $startDate, \DateTime $endDate, $groupBy = 'day')
    {
        $salesData = $this->orderRepository->getSalesData($startDate, $endDate, $groupBy);
        $paymentData = $this->paymentRepository->getSuccessRate($startDate, $endDate, $groupBy);

        return [
            'sales_trend' => $salesData,
            'payment_success_rate' => $paymentData,
            'total_revenue' => $this->getRevenue($startDate, $endDate),
            'total_orders' => $this->getOrderCount($startDate, $endDate),
            'average_order_value' => $this->getAverageOrderValue($startDate, $endDate),
            'top_products' => $this->getTopProducts($startDate, $endDate),
            'revenue_by_period' => $this->getRevenueByPeriod($startDate, $endDate)
        ];
    }

    /**
     * 在庫分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getInventoryAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $utilizationRate = $this->inventoryRepository->getUtilizationRate($startDate, $endDate);
        $inventoryAlerts = $this->inventoryRepository->getInventoryAlerts();
        $efficiencyAnalysis = $this->orderRepository->getInventoryEfficiencyAnalysis($startDate, $endDate);

        return [
            'utilization_rates' => $utilizationRate,
            'inventory_alerts' => $inventoryAlerts,
            'efficiency_analysis' => $efficiencyAnalysis,
            'low_stock_products' => $this->getLowStockProducts(),
            'high_demand_products' => $this->getHighDemandProducts($startDate, $endDate),
            'maintenance_schedule' => $this->getMaintenanceSchedule()
        ];
    }

    /**
     * 顧客分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getCustomerAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $customerStats = $this->orderRepository->getCustomerStatistics($startDate, $endDate);
        $repeatCustomers = $this->orderRepository->getRepeatCustomerAnalysis($startDate, $endDate);
        $overdueAnalysis = $this->orderRepository->getOverdueAnalysis($startDate, $endDate);

        return [
            'top_customers' => $customerStats,
            'repeat_analysis' => $repeatCustomers,
            'overdue_analysis' => $overdueAnalysis,
            'customer_retention_rate' => $this->getCustomerRetentionRate($startDate, $endDate),
            'average_rental_frequency' => $this->getAverageRentalFrequency($startDate, $endDate),
            'customer_lifetime_value' => $this->getCustomerLifetimeValue($startDate, $endDate)
        ];
    }

    /**
     * 商品分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getProductAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $profitabilityAnalysis = $this->orderRepository->getProfitabilityAnalysis($startDate, $endDate);
        $rentalPeriodAnalysis = $this->orderRepository->getRentalPeriodAnalysis($startDate, $endDate);
        $priceRangeAnalysis = $this->orderRepository->getPriceRangeAnalysis($startDate, $endDate);

        return [
            'profitability' => $profitabilityAnalysis,
            'rental_periods' => $rentalPeriodAnalysis,
            'price_ranges' => $priceRangeAnalysis,
            'seasonal_trends' => $this->getSeasonalTrends(),
            'product_performance' => $this->getProductPerformance($startDate, $endDate),
            'category_analysis' => $this->getCategoryAnalysis($startDate, $endDate)
        ];
    }

    /**
     * カート分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getCartAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $cartStats = $this->cartRepository->getCartStatistics($startDate, $endDate);
        $abandonmentAnalysis = $this->cartRepository->getCartAbandonmentAnalysis($startDate, $endDate);
        $popularProducts = $this->cartRepository->getPopularProductsInCart($startDate, $endDate);
        $retentionAnalysis = $this->cartRepository->getCartRetentionAnalysis($startDate, $endDate);

        return [
            'cart_statistics' => $cartStats,
            'abandonment_analysis' => $abandonmentAnalysis,
            'popular_products' => $popularProducts,
            'retention_analysis' => $retentionAnalysis,
            'conversion_rate' => $this->getCartConversionRate($startDate, $endDate)
        ];
    }

    /**
     * 決済分析を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getPaymentAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        $methodStats = $this->paymentRepository->getPaymentMethodStatistics($startDate, $endDate);
        $successRate = $this->paymentRepository->getSuccessRate($startDate, $endDate);
        $errorAnalysis = $this->paymentRepository->getPaymentErrorAnalysis($startDate, $endDate);

        return [
            'method_statistics' => $methodStats,
            'success_rates' => $successRate,
            'error_analysis' => $errorAnalysis,
            'total_processed' => $this->paymentRepository->getSuccessfulPaymentTotal($startDate, $endDate),
            'average_transaction_value' => $this->getAverageTransactionValue($startDate, $endDate),
            'refund_rate' => $this->getRefundRate($startDate, $endDate)
        ];
    }

    /**
     * 予測分析を取得
     *
     * @param int $forecastDays 予測日数
     * @return array
     */
    public function getForecastAnalysis($forecastDays = 30)
    {
        return [
            'revenue_forecast' => $this->forecastRevenue($forecastDays),
            'demand_forecast' => $this->forecastDemand($forecastDays),
            'inventory_needs' => $this->forecastInventoryNeeds($forecastDays),
            'seasonal_adjustments' => $this->getSeasonalAdjustments($forecastDays)
        ];
    }

    /**
     * KPI指標を取得
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @return array
     */
    public function getKPIs(\DateTime $startDate, \DateTime $endDate)
    {
        return [
            'revenue_per_customer' => $this->getRevenuePerCustomer($startDate, $endDate),
            'inventory_turnover' => $this->getInventoryTurnover($startDate, $endDate),
            'customer_acquisition_cost' => $this->getCustomerAcquisitionCost($startDate, $endDate),
            'return_on_investment' => $this->getReturnOnInvestment($startDate, $endDate),
            'operational_efficiency' => $this->getOperationalEfficiency($startDate, $endDate),
            'customer_satisfaction_score' => $this->getCustomerSatisfactionScore($startDate, $endDate)
        ];
    }

    /**
     * 注文数を取得
     */
    private function getOrderCount(\DateTime $startDate, \DateTime $endDate)
    {
        return count($this->orderRepository->findByDateRange($startDate, $endDate));
    }

    /**
     * 売上を取得
     */
    private function getRevenue(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->paymentRepository->getSuccessfulPaymentTotal($startDate, $endDate);
    }

    /**
     * 新規顧客数を取得（簡易実装）
     */
    private function getNewCustomerCount(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では初回注文顧客をカウント
        return 0;
    }

    /**
     * アクティブなレンタル数を取得
     */
    private function getActiveRentalCount()
    {
        return count($this->orderRepository->findByStatus(3)); // レンタル中
    }

    /**
     * 延滞数を取得
     */
    private function getOverdueCount()
    {
        return count($this->orderRepository->findOverdueOrders());
    }

    /**
     * 成長率を計算
     */
    private function calculateGrowthRates(\DateTime $currentStart, \DateTime $currentEnd, \DateTime $previousStart, \DateTime $previousEnd)
    {
        $currentRevenue = $this->getRevenue($currentStart, $currentEnd);
        $previousRevenue = $this->getRevenue($previousStart, $previousEnd);
        
        $revenueGrowth = $this->calculatePercentageGrowth($currentRevenue, $previousRevenue);

        $currentOrders = $this->getOrderCount($currentStart, $currentEnd);
        $previousOrders = $this->getOrderCount($previousStart, $previousEnd);
        
        $orderGrowth = $this->calculatePercentageGrowth($currentOrders, $previousOrders);

        return [
            'revenue' => $revenueGrowth,
            'orders' => $orderGrowth
        ];
    }

    /**
     * パーセンテージ成長率を計算
     */
    private function calculatePercentageGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return (($current - $previous) / $previous) * 100;
    }

    /**
     * 平均注文金額を取得
     */
    private function getAverageOrderValue(\DateTime $startDate, \DateTime $endDate)
    {
        $revenue = $this->getRevenue($startDate, $endDate);
        $orderCount = $this->getOrderCount($startDate, $endDate);
        
        return $orderCount > 0 ? $revenue / $orderCount : 0;
    }

    /**
     * トップ商品を取得
     */
    private function getTopProducts(\DateTime $startDate, \DateTime $endDate, $limit = 5)
    {
        // 実装では売上上位商品を取得
        return [];
    }

    /**
     * 期間別売上を取得
     */
    private function getRevenueByPeriod(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->orderRepository->getSalesData($startDate, $endDate, 'month');
    }

    /**
     * 在庫不足商品を取得
     */
    private function getLowStockProducts()
    {
        return $this->inventoryRepository->getInventoryAlerts();
    }

    /**
     * 高需要商品を取得
     */
    private function getHighDemandProducts(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では注文数上位商品を取得
        return [];
    }

    /**
     * メンテナンススケジュールを取得
     */
    private function getMaintenanceSchedule()
    {
        // 実装ではメンテナンス予定を取得
        return [];
    }

    /**
     * 顧客維持率を取得
     */
    private function getCustomerRetentionRate(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では顧客維持率を計算
        return 0;
    }

    /**
     * 平均レンタル頻度を取得
     */
    private function getAverageRentalFrequency(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では顧客あたりの平均レンタル回数を計算
        return 0;
    }

    /**
     * 顧客生涯価値を取得
     */
    private function getCustomerLifetimeValue(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では顧客の生涯価値を計算
        return 0;
    }

    /**
     * 季節トレンドを取得
     */
    private function getSeasonalTrends()
    {
        return $this->orderRepository->getSeasonalityAnalysis();
    }

    /**
     * 商品パフォーマンスを取得
     */
    private function getProductPerformance(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では商品別のパフォーマンス指標を取得
        return [];
    }

    /**
     * カテゴリ分析を取得
     */
    private function getCategoryAnalysis(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装ではカテゴリ別の分析を取得
        return [];
    }

    /**
     * カート変換率を取得
     */
    private function getCartConversionRate(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装ではカートから注文への変換率を計算
        return 0;
    }

    /**
     * 平均取引金額を取得
     */
    private function getAverageTransactionValue(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では平均取引金額を計算
        return 0;
    }

    /**
     * 返金率を取得
     */
    private function getRefundRate(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では返金率を計算
        return 0;
    }

    /**
     * 売上予測
     */
    private function forecastRevenue($days)
    {
        // 実装では過去データから売上を予測
        return [];
    }

    /**
     * 需要予測
     */
    private function forecastDemand($days)
    {
        // 実装では商品別需要を予測
        return [];
    }

    /**
     * 在庫需要予測
     */
    private function forecastInventoryNeeds($days)
    {
        // 実装では必要在庫数を予測
        return [];
    }

    /**
     * 季節調整を取得
     */
    private function getSeasonalAdjustments($days)
    {
        // 実装では季節要因による調整値を取得
        return [];
    }

    /**
     * 顧客あたり売上を取得
     */
    private function getRevenuePerCustomer(\DateTime $startDate, \DateTime $endDate)
    {
        $revenue = $this->getRevenue($startDate, $endDate);
        $customerCount = $this->getNewCustomerCount($startDate, $endDate);
        
        return $customerCount > 0 ? $revenue / $customerCount : 0;
    }

    /**
     * 在庫回転率を取得
     */
    private function getInventoryTurnover(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では在庫回転率を計算
        return 0;
    }

    /**
     * 顧客獲得コストを取得
     */
    private function getCustomerAcquisitionCost(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では顧客獲得コストを計算
        return 0;
    }

    /**
     * 投資収益率を取得
     */
    private function getReturnOnInvestment(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装ではROIを計算
        return 0;
    }

    /**
     * 運営効率を取得
     */
    private function getOperationalEfficiency(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では運営効率指標を計算
        return 0;
    }

    /**
     * 顧客満足度スコアを取得
     */
    private function getCustomerSatisfactionScore(\DateTime $startDate, \DateTime $endDate)
    {
        // 実装では顧客満足度を計算
        return 0;
    }
}