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
use Plugin\Rental\Repository\RentalInventoryRepository;
use Plugin\Rental\Form\Type\Admin\RentalReportType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @var RentalInventoryRepository
     */
    private $inventoryRepository;

    /**
     * コンストラクタ
     */
    public function __construct(
        RentalOrderRepository $orderRepository,
        RentalProductRepository $productRepository,
        RentalInventoryRepository $inventoryRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->inventoryRepository = $inventoryRepository;
    }

    /**
     * レポートダッシュボード
     *
     * @Route("", name="admin_rental_report", methods={"GET"})
     * @Template("@Rental/admin/report/index.twig")
     */
    public function index(Request $request)
    {
        $currentMonth = new \DateTime('first day of this month');
        $lastMonth = new \DateTime('first day of last month');

        return [
            'summary' => $this->getSummaryData(),
            'monthly_trend' => $this->getMonthlyTrend(12),
            'popular_products' => $this->orderRepository->getPopularProducts(10),
            'inventory_alerts' => $this->inventoryRepository->getStockAlerts(5),
            'current_month_report' => $this->orderRepository->getMonthlyReport($currentMonth->format('Y'), $currentMonth->format('n')),
            'last_month_report' => $this->orderRepository->getMonthlyReport($lastMonth->format('Y'), $lastMonth->format('n')),
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
        $form = $this->createForm(RentalReportType::class, [
            'start_date' => new \DateTime('first day of this month'),
            'end_date' => new \DateTime('last day of this month'),
        ]);

        $form->handleRequest($request);

        $reportData = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $reportData = $this->generateSalesReport($data['start_date'], $data['end_date']);
        }

        return [
            'form' => $form->createView(),
            'report_data' => $reportData,
            'chart_data' => $this->getSalesChartData($reportData),
        ];
    }

    /**
     * 商品別レポート
     *
     * @Route("/products", name="admin_rental_report_products", methods={"GET", "POST"})
     * @Template("@Rental/admin/report/products.twig")
     */
    public function products(Request $request)
    {
        $form = $this->createForm(RentalReportType::class, [
            'start_date' => new \DateTime('first day of this month'),
            'end_date' => new \DateTime('last day of this month'),
        ]);

        $form->handleRequest($request);

        $reportData = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $reportData = $this->generateProductReport($data['start_date'], $data['end_date']);
        }

        return [
            'form' => $form->createView(),
            'report_data' => $reportData,
            'product_statistics' => $this->productRepository->getStatistics(),
            'inventory_statistics' => $this->inventoryRepository->getInventoryStatistics(),
            'utilization_rates' => $this->inventoryRepository->getUtilizationRate(20),
        ];
    }

    /**
     * 顧客別レポート
     *
     * @Route("/customers", name="admin_rental_report_customers", methods={"GET", "POST"})
     * @Template("@Rental/admin/report/customers.twig")
     */
    public function customers(Request $request)
    {
        $form = $this->createForm(RentalReportType::class, [
            'start_date' => new \DateTime('first day of this month'),
            'end_date' => new \DateTime('last day of this month'),
        ]);

        $form->handleRequest($request);

        $reportData = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $reportData = $this->generateCustomerReport($data['start_date'], $data['end_date']);
        }

        return [
            'form' => $form->createView(),
            'report_data' => $reportData,
            'customer_rankings' => $this->getCustomerRankings(),
        ];
    }

    /**
     * 延滞・リスクレポート
     *
     * @Route("/risk", name="admin_rental_report_risk", methods={"GET"})
     * @Template("@Rental/admin/report/risk.twig")
     */
    public function risk(Request $request)
    {
        return [
            'overdue_analysis' => $this->getOverdueAnalysis(),
            'risk_customers' => $this->getRiskCustomers(),
            'inventory_risks' => $this->getInventoryRisks(),
            'financial_risks' => $this->getFinancialRisks(),
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

        try {
            $startDate = new \DateTime($request->request->get('start_date'));
            $endDate = new \DateTime($request->request->get('end_date'));

            switch ($type) {
                case 'sales':
                    $data = $this->generateSalesReport($startDate, $endDate);
                    $csv = $this->generateSalesCsv($data);
                    $filename = 'sales_report';
                    break;

                case 'products':
                    $data = $this->generateProductReport($startDate, $endDate);
                    $csv = $this->generateProductCsv($data);
                    $filename = 'product_report';
                    break;

                case 'customers':
                    $data = $this->generateCustomerReport($startDate, $endDate);
                    $csv = $this->generateCustomerCsv($data);
                    $filename = 'customer_report';
                    break;

                default:
                    throw new \InvalidArgumentException('Invalid report type');
            }

            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '_' . date('Y-m-d_H-i-s') . '.csv"');

            return $response;

        } catch (\Exception $e) {
            $this->addError('レポートのエクスポートに失敗しました。', 'admin');
            log_error('レンタルレポートエクスポートエラー', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return $this->redirectToRoute('admin_rental_report_' . $type);
        }
    }

    /**
     * サマリーデータを取得
     *
     * @return array
     */
    private function getSummaryData()
    {
        $today = new \DateTime();
        $thisMonth = new \DateTime('first day of this month');
        $lastMonth = new \DateTime('first day of last month');

        $thisMonthData = $this->orderRepository->getMonthlyReport($thisMonth->format('Y'), $thisMonth->format('n'));
        $lastMonthData = $this->orderRepository->getMonthlyReport($lastMonth->format('Y'), $lastMonth->format('n'));

        return [
            'total_orders' => $this->orderRepository->count([]),
            'active_rentals' => $this->orderRepository->count(['status' => \Plugin\Rental\Entity\RentalOrder::STATUS_ACTIVE]),
            'overdue_rentals' => $this->orderRepository->count(['status' => \Plugin\Rental\Entity\RentalOrder::STATUS_OVERDUE]),
            'this_month_revenue' => $thisMonthData['total_revenue'] ?: '0',
            'last_month_revenue' => $lastMonthData['total_revenue'] ?: '0',
            'this_month_orders' => $thisMonthData['order_count'] ?: 0,
            'last_month_orders' => $lastMonthData['order_count'] ?: 0,
            'revenue_growth' => $this->calculateGrowthRate($lastMonthData['total_revenue'] ?: '0', $thisMonthData['total_revenue'] ?: '0'),
            'order_growth' => $this->calculateGrowthRate($lastMonthData['order_count'] ?: 0, $thisMonthData['order_count'] ?: 0),
        ];
    }

    /**
     * 月次トレンドデータを取得
     *
     * @param int $months
     * @return array
     */
    private function getMonthlyTrend($months = 12)
    {
        $trends = [];
        $currentDate = new \DateTime();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = clone $currentDate;
            $date->sub(new \DateInterval('P' . $i . 'M'));
            
            $monthData = $this->orderRepository->getMonthlyReport($date->format('Y'), $date->format('n'));
            
            $trends[] = [
                'month' => $date->format('Y-m'),
                'revenue' => $monthData['total_revenue'] ?: '0',
                'orders' => $monthData['order_count'] ?: 0,
                'avg_amount' => $monthData['avg_order_amount'] ?: '0',
            ];
        }

        return $trends;
    }

    /**
     * 売上レポートを生成
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    private function generateSalesReport(\DateTime $startDate, \DateTime $endDate)
    {
        $orders = $this->orderRepository->createQueryBuilder('ro')
            ->where('ro.create_date BETWEEN :start_date AND :end_date')
            ->setParameter('start_date', $startDate)
            ->setParameter('end_date', $endDate)
            ->getQuery()
            ->getResult();

        $report = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_orders' => count($orders),
                'total_revenue' => '0',
                'total_deposit' => '0',
                'total_overdue_fee' => '0',
                'avg_order_amount' => '0',
            ],
            'daily_data' => [],
            'status_breakdown' => [],
        ];

        // 集計処理
        foreach ($orders as $order) {
            $report['summary']['total_revenue'] = bcadd($report['summary']['total_revenue'], $order->getTotalAmount(), 2);
            
            if ($order->getDepositAmount()) {
                $report['summary']['total_deposit'] = bcadd($report['summary']['total_deposit'], $order->getDepositAmount(), 2);
            }
            
            if ($order->getOverdueFee()) {
                $report['summary']['total_overdue_fee'] = bcadd($report['summary']['total_overdue_fee'], $order->getOverdueFee(), 2);
            }

            // 日別データ
            $dateKey = $order->getCreateDate()->format('Y-m-d');
            if (!isset($report['daily_data'][$dateKey])) {
                $report['daily_data'][$dateKey] = [
                    'date' => $dateKey,
                    'orders' => 0,
                    'revenue' => '0',
                ];
            }
            $report['daily_data'][$dateKey]['orders']++;
            $report['daily_data'][$dateKey]['revenue'] = bcadd($report['daily_data'][$dateKey]['revenue'], $order->getTotalAmount(), 2);

            // ステータス別内訳
            $status = $order->getStatus();
            if (!isset($report['status_breakdown'][$status])) {
                $report['status_breakdown'][$status] = [
                    'status_name' => $order->getStatusName(),
                    'count' => 0,
                    'revenue' => '0',
                ];
            }
            $report['status_breakdown'][$status]['count']++;
            $report['status_breakdown'][$status]['revenue'] = bcadd($report['status_breakdown'][$status]['revenue'], $order->getTotalAmount(), 2);
        }

        // 平均注文金額計算
        if (count($orders) > 0) {
            $report['summary']['avg_order_amount'] = bcdiv($report['summary']['total_revenue'], count($orders), 2);
        }

        return $report;
    }

    /**
     * 商品別レポートを生成
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    private function generateProductReport(\DateTime $startDate, \DateTime $endDate)
    {
        $popularProducts = $this->orderRepository->getPopularProducts(50, $startDate, $endDate);
        
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'popular_products' => $popularProducts,
            'category_analysis' => $this->getCategoryAnalysis($startDate, $endDate),
            'product_performance' => $this->getProductPerformance($startDate, $endDate),
        ];
    }

    /**
     * 顧客別レポートを生成
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    private function generateCustomerReport(\DateTime $startDate, \DateTime $endDate)
    {
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'customer_summary' => $this->getCustomerSummary($startDate, $endDate),
            'new_customers' => $this->getNewCustomers($startDate, $endDate),
            'repeat_customers' => $this->getRepeatCustomers($startDate, $endDate),
            'customer_value_analysis' => $this->getCustomerValueAnalysis($startDate, $endDate),
        ];
    }

    /**
     * 延滞分析データを取得
     *
     * @return array
     */
    private function getOverdueAnalysis()
    {
        $overdueOrders = $this->orderRepository->findOverdueOrders();
        
        $analysis = [
            'total_overdue' => count($overdueOrders),
            'total_overdue_amount' => '0',
            'avg_overdue_days' => 0,
            'overdue_by_period' => [],
        ];

        $totalDays = 0;
        foreach ($overdueOrders as $order) {
            $overdueDays = $order->getOverdueDays();
            $totalDays += $overdueDays;
            
            if ($order->getOverdueFee()) {
                $analysis['total_overdue_amount'] = bcadd($analysis['total_overdue_amount'], $order->getOverdueFee(), 2);
            }

            // 期間別分類
            if ($overdueDays <= 7) {
                $period = '1週間以内';
            } elseif ($overdueDays <= 30) {
                $period = '1ヶ月以内';
            } else {
                $period = '1ヶ月超';
            }

            if (!isset($analysis['overdue_by_period'][$period])) {
                $analysis['overdue_by_period'][$period] = 0;
            }
            $analysis['overdue_by_period'][$period]++;
        }

        if (count($overdueOrders) > 0) {
            $analysis['avg_overdue_days'] = round($totalDays / count($overdueOrders), 1);
        }

        return $analysis;
    }

    /**
     * 成長率を計算
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return float
     */
    private function calculateGrowthRate($oldValue, $newValue)
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100.0 : 0.0;
        }

        $growth = bcsub($newValue, $oldValue, 2);
        $rate = bcdiv($growth, $oldValue, 4);
        return round(bcmul($rate, 100, 2), 1);
    }

    /**
     * その他の分析メソッド（簡略化のため概要のみ）
     */
    private function getSalesChartData($reportData) { return []; }
    private function getCustomerRankings() { return []; }
    private function getRiskCustomers() { return []; }
    private function getInventoryRisks() { return []; }
    private function getFinancialRisks() { return []; }
    private function getCategoryAnalysis($startDate, $endDate) { return []; }
    private function getProductPerformance($startDate, $endDate) { return []; }
    private function getCustomerSummary($startDate, $endDate) { return []; }
    private function getNewCustomers($startDate, $endDate) { return []; }
    private function getRepeatCustomers($startDate, $endDate) { return []; }
    private function getCustomerValueAnalysis($startDate, $endDate) { return []; }
    private function generateSalesCsv($data) { return ''; }
    private function generateProductCsv($data) { return ''; }
    private function generateCustomerCsv($data) { return ''; }
}