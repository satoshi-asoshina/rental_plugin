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

namespace Plugin\Rental\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\Rental\Repository\RentalOrderRepository;
use Plugin\Rental\Repository\RentalProductRepository;
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Service\RentalAnalyticsService;
use Plugin\Rental\Form\Type\Admin\RentalReportType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * レンタルレポート機能コントローラー
 * 
 * @Route("/%eccube_admin_route%/rental/report")
 */
class RentalReportController extends AbstractController
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
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * @var RentalAnalyticsService
     */
    private $analyticsService;

    /**
     * コンストラクタ
     */
    public function __construct(
        RentalOrderRepository $orderRepository,
        RentalProductRepository $productRepository,
        RentalConfigRepository $configRepository,
        RentalAnalyticsService $analyticsService
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->configRepository = $configRepository;
        $this->analyticsService = $analyticsService;
    }

    /**
     * レポート一覧
     *
     * @Route("", name="admin_rental_report", methods={"GET", "POST"})
     * @Template("@Rental/admin/report/index.twig")
     */
    public function index(Request $request)
    {
        $form = $this->createForm(RentalReportType::class);
        $form->handleRequest($request);

        $reportData = [];
        $chartData = [];
        
        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $reportData = $this->generateReport($criteria);
            $chartData = $this->generateChartData($criteria);
        } else {
            // デフォルトレポート（今月）
            $defaultCriteria = [
                'period' => 'month',
                'start_date' => new \DateTime('first day of this month'),
                'end_date' => new \DateTime('last day of this month'),
            ];
            $reportData = $this->generateReport($defaultCriteria);
            $chartData = $this->generateChartData($defaultCriteria);
        }

        return [
            'form' => $form->createView(),
            'report_data' => $reportData,
            'chart_data' => $chartData,
            'summary' => $this->getSummaryData(),
        ];
    }

    /**
     * 売上レポート
     *
     * @Route("/sales", name="admin_rental_report_sales", methods={"GET", "POST"})
     * @Template("@Rental/admin/report/sales.twig")
     */
    public function sales(Request $request)
    {
        $form = $this->createForm(RentalReportType::class, null, [
            'report_type' => 'sales'
        ]);
        $form->handleRequest($request);

        $salesData = [];
        $comparisonData = [];
        
        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $salesData = $this->generateSalesReport($criteria);
            $comparisonData = $this->generateSalesComparison($criteria);
        }

        return [
            'form' => $form->createView(),
            'sales_data' => $salesData,
            'comparison_data' => $comparisonData,
            'trends' => $this->getSalesTrends(),
        ];
    }

    /**
     * 商品パフォーマンスレポート
     *
     * @Route("/products", name="admin_rental_report_products", methods={"GET", "POST"})
     * @Template("@Rental/admin/report/products.twig")
     */
    public function products(Request $request)
    {
        $form = $this->createForm(RentalReportType::class, null, [
            'report_type' => 'products'
        ]);
        $form->handleRequest($request);

        $productData = [];
        
        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $productData = $this->generateProductReport($criteria);
        }

        return [
            'form' => $form->createView(),
            'product_data' => $productData,
            'top_products' => $this->getTopProducts(),
            'underperforming_products' => $this->getUnderperformingProducts(),
        ];
    }

    /**
     * 顧客分析レポート
     *
     * @Route("/customers", name="admin_rental_report_customers", methods={"GET", "POST"})
     * @Template("@Rental/admin/report/customers.twig")
     */
    public function customers(Request $request)
    {
        $form = $this->createForm(RentalReportType::class, null, [
            'report_type' => 'customers'
        ]);
        $form->handleRequest($request);

        $customerData = [];
        
        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $customerData = $this->generateCustomerReport($criteria);
        }

        return [
            'form' => $form->createView(),
            'customer_data' => $customerData,
            'customer_segments' => $this->getCustomerSegments(),
            'retention_metrics' => $this->getRetentionMetrics(),
        ];
    }

    /**
     * 在庫・稼働率レポート
     *
     * @Route("/inventory", name="admin_rental_report_inventory", methods={"GET", "POST"})
     * @Template("@Rental/admin/report/inventory.twig")
     */
    public function inventory(Request $request)
    {
        $form = $this->createForm(RentalReportType::class, null, [
            'report_type' => 'inventory'
        ]);
        $form->handleRequest($request);

        $inventoryData = [];
        
        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $inventoryData = $this->generateInventoryReport($criteria);
        }

        return [
            'form' => $form->createView(),
            'inventory_data' => $inventoryData,
            'utilization_rates' => $this->getUtilizationRates(),
            'availability_forecast' => $this->getAvailabilityForecast(),
        ];
    }

    /**
     * 延滞・リスク分析レポート
     *
     * @Route("/risk", name="admin_rental_report_risk", methods={"GET", "POST"})
     * @Template("@Rental/admin/report/risk.twig")
     */
    public function risk(Request $request)
    {
        $riskData = $this->generateRiskReport();

        return [
            'risk_data' => $riskData,
            'overdue_analysis' => $this->getOverdueAnalysis(),
            'risk_indicators' => $this->getRiskIndicators(),
        ];
    }

    /**
     * レポートエクスポート
     *
     * @Route("/export/{type}", name="admin_rental_report_export", methods={"POST"})
     */
    public function export(Request $request, string $type)
    {
        $this->isTokenValid();

        $format = $request->request->get('format', 'csv');
        $criteria = json_decode($request->request->get('criteria', '{}'), true);

        try {
            switch ($type) {
                case 'sales':
                    $data = $this->generateSalesReport($criteria);
                    break;
                case 'products':
                    $data = $this->generateProductReport($criteria);
                    break;
                case 'customers':
                    $data = $this->generateCustomerReport($criteria);
                    break;
                case 'inventory':
                    $data = $this->generateInventoryReport($criteria);
                    break;
                default:
                    throw new \InvalidArgumentException('無効なレポートタイプです。');
            }

            if ($format === 'csv') {
                return $this->exportToCsv($data, $type);
            } elseif ($format === 'excel') {
                return $this->exportToExcel($data, $type);
            } elseif ($format === 'pdf') {
                return $this->exportToPdf($data, $type);
            }

        } catch (\Exception $e) {
            $this->addError('エクスポートに失敗しました。', 'admin');
            log_error('レンタルレポートエクスポートエラー', [
                'type' => $type,
                'format' => $format,
                'error' => $e->getMessage()
            ]);

            return $this->redirectToRoute('admin_rental_report');
        }
    }

    /**
     * Ajax: リアルタイムデータ取得
     *
     * @Route("/realtime/{metric}", name="admin_rental_report_realtime", methods={"GET"})
     */
    public function realtime(Request $request, string $metric): JsonResponse
    {
        try {
            switch ($metric) {
                case 'orders_today':
                    $data = $this->analyticsService->getOrdersToday();
                    break;
                case 'revenue_today':
                    $data = $this->analyticsService->getRevenueToday();
                    break;
                case 'active_rentals':
                    $data = $this->analyticsService->getActiveRentals();
                    break;
                case 'overdue_count':
                    $data = $this->analyticsService->getOverdueCount();
                    break;
                default:
                    throw new \InvalidArgumentException('無効なメトリクスです。');
            }

            return new JsonResponse(['data' => $data]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'データ取得に失敗しました'], 500);
        }
    }

    /**
     * 基本レポートを生成
     *
     * @param array $criteria
     * @return array
     */
    private function generateReport(array $criteria): array
    {
        $startDate = $criteria['start_date'] ?? new \DateTime('-30 days');
        $endDate = $criteria['end_date'] ?? new \DateTime();

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'days' => $startDate->diff($endDate)->days + 1,
            ],
            'orders' => $this->getOrderMetrics($startDate, $endDate),
            'revenue' => $this->getRevenueMetrics($startDate, $endDate),
            'products' => $this->getProductMetrics($startDate, $endDate),
            'customers' => $this->getCustomerMetrics($startDate, $endDate),
        ];
    }

    /**
     * チャートデータを生成
     *
     * @param array $criteria
     * @return array
     */
    private function generateChartData(array $criteria): array
    {
        $startDate = $criteria['start_date'] ?? new \DateTime('-30 days');
        $endDate = $criteria['end_date'] ?? new \DateTime();

        return [
            'revenue_chart' => $this->getRevenueChartData($startDate, $endDate),
            'orders_chart' => $this->getOrdersChartData($startDate, $endDate),
            'product_popularity' => $this->getProductPopularityChart($startDate, $endDate),
            'status_distribution' => $this->getStatusDistributionChart($startDate, $endDate),
        ];
    }

    /**
     * 売上レポートを生成
     *
     * @param array $criteria
     * @return array
     */
    private function generateSalesReport(array $criteria): array
    {
        $startDate = $criteria['start_date'] ?? new \DateTime('-30 days');
        $endDate = $criteria['end_date'] ?? new \DateTime();

        return [
            'total_revenue' => $this->orderRepository->getTotalRevenue($startDate, $endDate),
            'average_order_value' => $this->orderRepository->getAverageOrderValue($startDate, $endDate),
            'revenue_by_day' => $this->orderRepository->getRevenueByDay($startDate, $endDate),
            'revenue_by_product' => $this->orderRepository->getRevenueByProduct($startDate, $endDate),
            'revenue_by_customer_segment' => $this->getRevenueByCustomerSegment($startDate, $endDate),
            'payment_method_breakdown' => $this->getPaymentMethodBreakdown($startDate, $endDate),
        ];
    }

    /**
     * 売上比較データを生成
     *
     * @param array $criteria
     * @return array
     */
    private function generateSalesComparison(array $criteria): array
    {
        $startDate = $criteria['start_date'] ?? new \DateTime('-30 days');
        $endDate = $criteria['end_date'] ?? new \DateTime();
        
        // 前期間の計算
        $daysDiff = $startDate->diff($endDate)->days + 1;
        $prevStartDate = (clone $startDate)->sub(new \DateInterval("P{$daysDiff}D"));
        $prevEndDate = (clone $endDate)->sub(new \DateInterval("P{$daysDiff}D"));

        $currentRevenue = $this->orderRepository->getTotalRevenue($startDate, $endDate);
        $previousRevenue = $this->orderRepository->getTotalRevenue($prevStartDate, $prevEndDate);

        return [
            'current_period' => [
                'revenue' => $currentRevenue,
                'orders' => $this->orderRepository->getOrderCount($startDate, $endDate),
                'average_order_value' => $this->orderRepository->getAverageOrderValue($startDate, $endDate),
            ],
            'previous_period' => [
                'revenue' => $previousRevenue,
                'orders' => $this->orderRepository->getOrderCount($prevStartDate, $prevEndDate),
                'average_order_value' => $this->orderRepository->getAverageOrderValue($prevStartDate, $prevEndDate),
            ],
            'growth_rate' => $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0,
        ];
    }

    /**
     * 商品レポートを生成
     *
     * @param array $criteria
     * @return array
     */
    private function generateProductReport(array $criteria): array
    {
        $startDate = $criteria['start_date'] ?? new \DateTime('-30 days');
        $endDate = $criteria['end_date'] ?? new \DateTime();

        return [
            'top_products' => $this->orderRepository->getTopRentedProducts($startDate, $endDate, 10),
            'product_revenue' => $this->orderRepository->getProductRevenue($startDate, $endDate),
            'product_utilization' => $this->getProductUtilization($startDate, $endDate),
            'new_products_performance' => $this->getNewProductsPerformance($startDate, $endDate),
            'seasonal_trends' => $this->getProductSeasonalTrends($startDate, $endDate),
        ];
    }

    /**
     * 顧客レポートを生成
     *
     * @param array $criteria
     * @return array
     */
    private function generateCustomerReport(array $criteria): array
    {
        $startDate = $criteria['start_date'] ?? new \DateTime('-30 days');
        $endDate = $criteria['end_date'] ?? new \DateTime();

        return [
            'new_customers' => $this->getNewCustomers($startDate, $endDate),
            'returning_customers' => $this->getReturningCustomers($startDate, $endDate),
            'customer_lifetime_value' => $this->getCustomerLifetimeValue($startDate, $endDate),
            'top_customers' => $this->getTopCustomers($startDate, $endDate),
            'customer_retention_rate' => $this->getCustomerRetentionRate($startDate, $endDate),
            'customer_segments' => $this->getCustomerSegmentAnalysis($startDate, $endDate),
        ];
    }

    /**
     * 在庫レポートを生成
     *
     * @param array $criteria
     * @return array
     */
    private function generateInventoryReport(array $criteria): array
    {
        $startDate = $criteria['start_date'] ?? new \DateTime('-30 days');
        $endDate = $criteria['end_date'] ?? new \DateTime();

        return [
            'utilization_rates' => $this->getDetailedUtilizationRates($startDate, $endDate),
            'availability_timeline' => $this->getAvailabilityTimeline($startDate, $endDate),
            'maintenance_schedule' => $this->getMaintenanceSchedule($startDate, $endDate),
            'inventory_turnover' => $this->getInventoryTurnover($startDate, $endDate),
            'stock_levels' => $this->getCurrentStockLevels(),
        ];
    }

    /**
     * リスクレポートを生成
     *
     * @return array
     */
    private function generateRiskReport(): array
    {
        return [
            'overdue_orders' => $this->getOverdueOrdersDetails(),
            'high_risk_customers' => $this->getHighRiskCustomers(),
            'payment_issues' => $this->getPaymentIssues(),
            'damage_reports' => $this->getDamageReports(),
            'fraud_indicators' => $this->getFraudIndicators(),
        ];
    }

    /**
     * 注文メトリクスを取得
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    private function getOrderMetrics(\DateTime $startDate, \DateTime $endDate): array
    {
        return [
            'total_orders' => $this->orderRepository->getOrderCount($startDate, $endDate),
            'completed_orders' => $this->orderRepository->getCompletedOrderCount($startDate, $endDate),
            'cancelled_orders' => $this->orderRepository->getCancelledOrderCount($startDate, $endDate),
            'average_rental_days' => $this->orderRepository->getAverageRentalDays($startDate, $endDate),
            'conversion_rate' => $this->getConversionRate($startDate, $endDate),
        ];
    }

    /**
     * 売上メトリクスを取得
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    private function getRevenueMetrics(\DateTime $startDate, \DateTime $endDate): array
    {
        return [
            'total_revenue' => $this->orderRepository->getTotalRevenue($startDate, $endDate),
            'rental_revenue' => $this->orderRepository->getRentalRevenue($startDate, $endDate),
            'deposit_revenue' => $this->orderRepository->getDepositRevenue($startDate, $endDate),
            'late_fee_revenue' => $this->orderRepository->getLateFeeRevenue($startDate, $endDate),
            'average_order_value' => $this->orderRepository->getAverageOrderValue($startDate, $endDate),
        ];
    }

    /**
     * 商品メトリクスを取得
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    private function getProductMetrics(\DateTime $startDate, \DateTime $endDate): array
    {
        return [
            'most_rented' => $this->orderRepository->getMostRentedProduct($startDate, $endDate),
            'highest_revenue' => $this->orderRepository->getHighestRevenueProduct($startDate, $endDate),
            'average_utilization' => $this->getAverageProductUtilization($startDate, $endDate),
            'new_products_added' => $this->productRepository->getNewProductsCount($startDate, $endDate),
        ];
    }

    /**
     * 顧客メトリクスを取得
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    private function getCustomerMetrics(\DateTime $startDate, \DateTime $endDate): array
    {
        return [
            'new_customers' => $this->getNewCustomersCount($startDate, $endDate),
            'returning_customers' => $this->getReturningCustomersCount($startDate, $endDate),
            'customer_retention_rate' => $this->getCustomerRetentionRate($startDate, $endDate),
            'average_customer_value' => $this->getAverageCustomerValue($startDate, $endDate),
        ];
    }

    /**
     * CSVエクスポート
     *
     * @param array $data
     * @param string $type
     * @return Response
     */
    private function exportToCsv(array $data, string $type): Response
    {
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        
        // レポートタイプ別のヘッダーとデータ処理
        switch ($type) {
            case 'sales':
                $csv .= $this->generateSalesCsv($data);
                break;
            case 'products':
                $csv .= $this->generateProductsCsv($data);
                break;
            case 'customers':
                $csv .= $this->generateCustomersCsv($data);
                break;
            case 'inventory':
                $csv .= $this->generateInventoryCsv($data);
                break;
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="rental_' . $type . '_report_' . date('Y-m-d_H-i-s') . '.csv"');

        return $response;
    }

    /**
     * 売上CSV生成
     *
     * @param array $data
     * @return string
     */
    private function generateSalesCsv(array $data): string
    {
        $csv = "日付,売上合計,注文数,平均注文金額\n";
        
        if (isset($data['revenue_by_day'])) {
            foreach ($data['revenue_by_day'] as $day) {
                $csv .= sprintf(
                    "%s,%s,%s,%s\n",
                    $day['date'],
                    number_format($day['revenue']),
                    $day['orders'],
                    number_format($day['average_order_value'])
                );
            }
        }
        
        return $csv;
    }

    /**
     * 商品CSV生成
     *
     * @param array $data
     * @return string
     */
    private function generateProductsCsv(array $data): string
    {
        $csv = "商品名,レンタル回数,売上金額,稼働率\n";
        
        if (isset($data['top_products'])) {
            foreach ($data['top_products'] as $product) {
                $csv .= sprintf(
                    "\"%s\",%s,%s,%s%%\n",
                    $product['name'],
                    $product['rental_count'],
                    number_format($product['revenue']),
                    number_format($product['utilization_rate'], 1)
                );
            }
        }
        
        return $csv;
    }

    /**
     * 顧客CSV生成
     *
     * @param array $data
     * @return string
     */
    private function generateCustomersCsv(array $data): string
    {
        $csv = "顧客名,注文回数,総レンタル金額,平均注文金額\n";
        
        if (isset($data['top_customers'])) {
            foreach ($data['top_customers'] as $customer) {
                $csv .= sprintf(
                    "\"%s\",%s,%s,%s\n",
                    $customer['name'],
                    $customer['order_count'],
                    number_format($customer['total_amount']),
                    number_format($customer['average_order_value'])
                );
            }
        }
        
        return $csv;
    }

    /**
     * 在庫CSV生成
     *
     * @param array $data
     * @return string
     */
    private function generateInventoryCsv(array $data): string
    {
        $csv = "商品名,在庫数,稼働中,稼働率,次回メンテナンス\n";
        
        if (isset($data['stock_levels'])) {
            foreach ($data['stock_levels'] as $item) {
                $csv .= sprintf(
                    "\"%s\",%s,%s,%s%%,%s\n",
                    $item['product_name'],
                    $item['total_stock'],
                    $item['active_rentals'],
                    number_format($item['utilization_rate'], 1),
                    $item['next_maintenance'] ?? '未定'
                );
            }
        }
        
        return $csv;
    }

    /**
     * サマリーデータを取得
     *
     * @return array
     */
    private function getSummaryData(): array
    {
        return [
            'today' => $this->getTodayStats(),
            'this_week' => $this->getThisWeekStats(),
            'this_month' => $this->getThisMonthStats(),
            'alerts' => $this->getActiveAlerts(),
        ];
    }

    /**
     * 今日の統計を取得
     *
     * @return array
     */
    private function getTodayStats(): array
    {
        $today = new \DateTime();
        $startOfDay = (clone $today)->setTime(0, 0, 0);
        $endOfDay = (clone $today)->setTime(23, 59, 59);

        return [
            'orders' => $this->orderRepository->getOrderCount($startOfDay, $endOfDay),
            'revenue' => $this->orderRepository->getTotalRevenue($startOfDay, $endOfDay),
            'new_customers' => $this->getNewCustomersCount($startOfDay, $endOfDay),
        ];
    }

    /**
     * 今週の統計を取得
     *
     * @return array
     */
    private function getThisWeekStats(): array
    {
        $startOfWeek = new \DateTime('monday this week');
        $endOfWeek = new \DateTime('sunday this week');

        return [
            'orders' => $this->orderRepository->getOrderCount($startOfWeek, $endOfWeek),
            'revenue' => $this->orderRepository->getTotalRevenue($startOfWeek, $endOfWeek),
            'completion_rate' => $this->getCompletionRate($startOfWeek, $endOfWeek),
        ];
    }

    /**
     * 今月の統計を取得
     *
     * @return array
     */
    private function getThisMonthStats(): array
    {
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');

        return [
            'orders' => $this->orderRepository->getOrderCount($startOfMonth, $endOfMonth),
            'revenue' => $this->orderRepository->getTotalRevenue($startOfMonth, $endOfMonth),
            'growth_rate' => $this->getMonthlyGrowthRate(),
        ];
    }

    /**
     * アクティブなアラートを取得
     *
     * @return array
     */
    private function getActiveAlerts(): array
    {
        return [
            'overdue_orders' => $this->orderRepository->getOverdueOrderCount(),
            'low_stock_products' => $this->productRepository->getLowStockCount(),
            'maintenance_due' => $this->getMaintenanceDueCount(),
            'payment_issues' => $this->getPaymentIssuesCount(),
        ];
    }

    // その他のヘルパーメソッド...
    private function getConversionRate(\DateTime $startDate, \DateTime $endDate): float { return 0.0; }
    private function getAverageProductUtilization(\DateTime $startDate, \DateTime $endDate): float { return 0.0; }
    private function getNewCustomersCount(\DateTime $startDate, \DateTime $endDate): int { return 0; }
    private function getReturningCustomersCount(\DateTime $startDate, \DateTime $endDate): int { return 0; }
    private function getAverageCustomerValue(\DateTime $startDate, \DateTime $endDate): float { return 0.0; }
    private function getCompletionRate(\DateTime $startDate, \DateTime $endDate): float { return 0.0; }
    private function getMonthlyGrowthRate(): float { return 0.0; }
    private function getMaintenanceDueCount(): int { return 0; }
    private function getPaymentIssuesCount(): int { return 0; }
}