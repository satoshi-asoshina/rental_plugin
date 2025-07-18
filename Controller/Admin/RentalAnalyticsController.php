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
use Plugin\Rental\Service\RentalAnalyticsService;
use Plugin\Rental\Repository\RentalOrderRepository;
use Plugin\Rental\Repository\RentalProductRepository;
use Plugin\Rental\Form\Type\Admin\RentalAnalyticsType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * レンタル分析コントローラー
 */
class RentalAnalyticsController extends AbstractController
{
    /**
     * @var RentalAnalyticsService
     */
    private $analyticsService;

    /**
     * @var RentalOrderRepository
     */
    private $orderRepository;

    /**
     * @var RentalProductRepository
     */
    private $productRepository;

    /**
     * コンストラクタ
     *
     * @param RentalAnalyticsService $analyticsService
     * @param RentalOrderRepository $orderRepository
     * @param RentalProductRepository $productRepository
     */
    public function __construct(
        RentalAnalyticsService $analyticsService,
        RentalOrderRepository $orderRepository,
        RentalProductRepository $productRepository
    ) {
        $this->analyticsService = $analyticsService;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * 分析ダッシュボード
     *
     * @param Request $request
     * @return array|Response
     *
     * @Route("/%eccube_admin_route%/rental/analytics", name="admin_rental_analytics")
     * @Template("@Rental/admin/analytics/index.twig")
     */
    public function index(Request $request)
    {
        $form = $this->createForm(RentalAnalyticsType::class);
        $form->handleRequest($request);

        // デフォルト期間設定（過去30日）
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify('-30 days');

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $startDate = $data['start_date'] ?? $startDate;
            $endDate = $data['end_date'] ?? $endDate;
        }

        try {
            // 基本統計データ取得
            $analytics = $this->analyticsService->getAnalytics($startDate, $endDate);

            // ダッシュボード用サマリーデータ
            $summary = [
                'total_orders' => $analytics['total_orders'],
                'total_revenue' => $analytics['total_revenue'],
                'active_rentals' => $analytics['active_rentals'],
                'overdue_rentals' => $analytics['overdue_rentals'],
                'average_rental_days' => $analytics['average_rental_days'],
                'return_rate' => $analytics['return_rate'],
                'revenue_growth' => $analytics['revenue_growth'],
                'customer_satisfaction' => $analytics['customer_satisfaction'],
            ];

            // チャート用データ
            $chartData = [
                'revenue_trend' => $this->analyticsService->getRevenueTrend($startDate, $endDate),
                'order_status_distribution' => $this->analyticsService->getOrderStatusDistribution($startDate, $endDate),
                'popular_products' => $this->analyticsService->getPopularProducts($startDate, $endDate, 10),
                'customer_segments' => $this->analyticsService->getCustomerSegments($startDate, $endDate),
                'rental_duration_analysis' => $this->analyticsService->getRentalDurationAnalysis($startDate, $endDate),
            ];

            return [
                'form' => $form->createView(),
                'summary' => $summary,
                'chart_data' => $chartData,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'analytics' => $analytics,
            ];

        } catch (\Exception $e) {
            $this->addError('admin.rental.analytics.error', 'admin');
            log_error('レンタル分析エラー', ['error' => $e->getMessage()]);

            return [
                'form' => $form->createView(),
                'summary' => [],
                'chart_data' => [],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'analytics' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 売上トレンドデータをAJAXで取得
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/%eccube_admin_route%/rental/analytics/revenue-trend", name="admin_rental_analytics_revenue_trend", methods={"GET"})
     */
    public function getRevenueTrend(Request $request): JsonResponse
    {
        $startDate = new \DateTime($request->query->get('start_date', '-30 days'));
        $endDate = new \DateTime($request->query->get('end_date', 'now'));
        $granularity = $request->query->get('granularity', 'daily'); // daily, weekly, monthly

        try {
            $data = $this->analyticsService->getRevenueTrend($startDate, $endDate, $granularity);
            
            return $this->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 商品パフォーマンス分析をAJAXで取得
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/%eccube_admin_route%/rental/analytics/product-performance", name="admin_rental_analytics_product_performance", methods={"GET"})
     */
    public function getProductPerformance(Request $request): JsonResponse
    {
        $startDate = new \DateTime($request->query->get('start_date', '-30 days'));
        $endDate = new \DateTime($request->query->get('end_date', 'now'));
        $limit = (int) $request->query->get('limit', 20);

        try {
            $data = $this->analyticsService->getProductPerformance($startDate, $endDate, $limit);
            
            return $this->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 顧客分析データをAJAXで取得
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/%eccube_admin_route%/rental/analytics/customer-analysis", name="admin_rental_analytics_customer_analysis", methods={"GET"})
     */
    public function getCustomerAnalysis(Request $request): JsonResponse
    {
        $startDate = new \DateTime($request->query->get('start_date', '-30 days'));
        $endDate = new \DateTime($request->query->get('end_date', 'now'));

        try {
            $data = [
                'segments' => $this->analyticsService->getCustomerSegments($startDate, $endDate),
                'retention' => $this->analyticsService->getCustomerRetention($startDate, $endDate),
                'lifetime_value' => $this->analyticsService->getCustomerLifetimeValue($startDate, $endDate),
                'acquisition' => $this->analyticsService->getCustomerAcquisition($startDate, $endDate),
            ];
            
            return $this->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 在庫利用率分析をAJAXで取得
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/%eccube_admin_route%/rental/analytics/inventory-utilization", name="admin_rental_analytics_inventory_utilization", methods={"GET"})
     */
    public function getInventoryUtilization(Request $request): JsonResponse
    {
        $startDate = new \DateTime($request->query->get('start_date', '-30 days'));
        $endDate = new \DateTime($request->query->get('end_date', 'now'));

        try {
            $data = $this->analyticsService->getInventoryUtilization($startDate, $endDate);
            
            return $this->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * リスク分析データをAJAXで取得
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/%eccube_admin_route%/rental/analytics/risk-analysis", name="admin_rental_analytics_risk_analysis", methods={"GET"})
     */
    public function getRiskAnalysis(Request $request): JsonResponse
    {
        $startDate = new \DateTime($request->query->get('start_date', '-30 days'));
        $endDate = new \DateTime($request->query->get('end_date', 'now'));

        try {
            $data = [
                'overdue_analysis' => $this->analyticsService->getOverdueAnalysis($startDate, $endDate),
                'damage_analysis' => $this->analyticsService->getDamageAnalysis($startDate, $endDate),
                'loss_analysis' => $this->analyticsService->getLossAnalysis($startDate, $endDate),
                'risk_score' => $this->analyticsService->calculateRiskScore($startDate, $endDate),
            ];
            
            return $this->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * レポートを Excel でエクスポート
     *
     * @param Request $request
     * @return Response
     *
     * @Route("/%eccube_admin_route%/rental/analytics/export", name="admin_rental_analytics_export", methods={"POST"})
     */
    public function export(Request $request): Response
    {
        $startDate = new \DateTime($request->request->get('start_date', '-30 days'));
        $endDate = new \DateTime($request->request->get('end_date', 'now'));
        $type = $request->request->get('type', 'comprehensive'); // comprehensive, revenue, products, customers

        try {
            $filename = sprintf(
                'rental_analytics_%s_%s_to_%s.xlsx',
                $type,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            $excelData = $this->analyticsService->exportToExcel($startDate, $endDate, $type);

            $response = new Response($excelData);
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

            return $response;

        } catch (\Exception $e) {
            $this->addError('admin.rental.analytics.export.error', 'admin');
            return $this->redirectToRoute('admin_rental_analytics');
        }
    }

    /**
     * 分析データを CSV でエクスポート
     *
     * @param Request $request
     * @return Response
     *
     * @Route("/%eccube_admin_route%/rental/analytics/export-csv", name="admin_rental_analytics_export_csv", methods={"POST"})
     */
    public function exportCsv(Request $request): Response
    {
        $startDate = new \DateTime($request->request->get('start_date', '-30 days'));
        $endDate = new \DateTime($request->request->get('end_date', 'now'));
        $type = $request->request->get('type', 'orders');

        try {
            $filename = sprintf(
                'rental_analytics_%s_%s_to_%s.csv',
                $type,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            $csvData = $this->analyticsService->exportToCsv($startDate, $endDate, $type);

            $response = new Response($csvData);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

            return $response;

        } catch (\Exception $e) {
            $this->addError('admin.rental.analytics.export.error', 'admin');
            return $this->redirectToRoute('admin_rental_analytics');
        }
    }

    /**
     * リアルタイム統計データを取得
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/%eccube_admin_route%/rental/analytics/realtime", name="admin_rental_analytics_realtime", methods={"GET"})
     */
    public function getRealtime(Request $request): JsonResponse
    {
        try {
            $data = [
                'active_rentals' => $this->orderRepository->countActiveRentals(),
                'today_orders' => $this->orderRepository->countTodayOrders(),
                'today_revenue' => $this->analyticsService->getTodayRevenue(),
                'overdue_count' => $this->orderRepository->countOverdue(),
                'pending_returns' => $this->orderRepository->countPendingReturns(),
                'low_stock_products' => $this->productRepository->findLowStockProducts(),
                'last_updated' => new \DateTime(),
            ];
            
            return $this->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 予測データを取得
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/%eccube_admin_route%/rental/analytics/forecast", name="admin_rental_analytics_forecast", methods={"GET"})
     */
    public function getForecast(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 30);

        try {
            $data = [
                'revenue_forecast' => $this->analyticsService->forecastRevenue($days),
                'demand_forecast' => $this->analyticsService->forecastDemand($days),
                'inventory_forecast' => $this->analyticsService->forecastInventoryNeeds($days),
                'confidence_interval' => $this->analyticsService->getConfidenceInterval($days),
            ];
            
            return $this->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}