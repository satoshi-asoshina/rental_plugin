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

namespace Plugin\Rental\TemplateHook\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\Rental\Repository\RentalProductRepository;
use Plugin\Rental\Repository\RentalInventoryRepository;
use Plugin\Rental\Repository\RentalOrderRepository;
use Twig\Environment;

/**
 * 商品編集画面フッククラス
 */
class ProductEditHook
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var RentalProductRepository
     */
    private $rentalProductRepository;

    /**
     * @var RentalInventoryRepository
     */
    private $inventoryRepository;

    /**
     * @var RentalOrderRepository
     */
    private $orderRepository;

    /**
     * コンストラクタ
     */
    public function __construct(
        Environment $twig,
        EccubeConfig $eccubeConfig,
        RentalProductRepository $rentalProductRepository,
        RentalInventoryRepository $inventoryRepository,
        RentalOrderRepository $orderRepository
    ) {
        $this->twig = $twig;
        $this->eccubeConfig = $eccubeConfig;
        $this->rentalProductRepository = $rentalProductRepository;
        $this->inventoryRepository = $inventoryRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * 商品編集画面にレンタル設定タブを追加
     *
     * @param array $parameters
     * @return string
     */
    public function addRentalTab(array $parameters = [])
    {
        $product = $parameters['Product'] ?? null;
        $form = $parameters['form'] ?? null;

        if (!$product || !$form) {
            return '';
        }

        // レンタル設定情報を取得
        $rentalProduct = null;
        $inventoryInfo = null;
        $orderStatistics = null;
        
        if ($product->getId()) {
            $rentalProduct = $this->rentalProductRepository->findByProduct($product);
            
            if ($rentalProduct && $rentalProduct->getIsRentalEnabled()) {
                $inventoryInfo = $this->inventoryRepository->getInventoryDetails($product);
                $orderStatistics = $this->getProductOrderStatistics($product);
            }
        }

        return $this->twig->render('@Rental/admin/Product/product_rental_nav.twig', [
            'Product' => $product,
            'form' => $form,
            'rental_product' => $rentalProduct,
            'inventory_info' => $inventoryInfo,
            'order_statistics' => $orderStatistics,
        ]);
    }

    /**
     * 商品編集画面にレンタル設定コンテンツを追加
     *
     * @param array $parameters
     * @return string
     */
    public function addRentalContent(array $parameters = [])
    {
        $product = $parameters['Product'] ?? null;
        $form = $parameters['form'] ?? null;

        if (!$product || !$form) {
            return '';
        }

        // レンタル関連情報を取得
        $rentalProduct = null;
        $inventoryInfo = null;
        $recentOrders = [];
        $priceAnalysis = null;
        
        if ($product->getId()) {
            $rentalProduct = $this->rentalProductRepository->findByProduct($product);
            
            if ($rentalProduct) {
                $inventoryInfo = $this->inventoryRepository->getInventoryDetails($product);
                $recentOrders = $this->getRecentRentalOrders($product);
                
                if ($rentalProduct->getIsRentalEnabled()) {
                    $priceAnalysis = $this->analyzePricingSetting($rentalProduct);
                }
            }
        }

        return $this->twig->render('@Rental/admin/Product/product_rental_content.twig', [
            'Product' => $product,
            'form' => $form,
            'rental_product' => $rentalProduct,
            'inventory_info' => $inventoryInfo,
            'recent_orders' => $recentOrders,
            'price_analysis' => $priceAnalysis,
            'rental_config' => $this->getRentalConfig(),
        ]);
    }

    /**
     * 商品一覧にレンタル状態を表示
     *
     * @param array $parameters
     * @return string
     */
    public function addRentalStatusToList(array $parameters = [])
    {
        $products = $parameters['pagination'] ?? null;

        if (!$products) {
            return '';
        }

        $rentalStatuses = [];
        
        foreach ($products as $product) {
            $rentalProduct = $this->rentalProductRepository->findByProduct($product);
            $rentalStatuses[$product->getId()] = [
                'enabled' => $rentalProduct ? $rentalProduct->getIsRentalEnabled() : false,
                'has_pricing' => $rentalProduct ? $rentalProduct->hasPricingSetting() : false,
                'auto_approval' => $rentalProduct ? $rentalProduct->getAutoApproval() : false,
            ];
        }

        return $this->twig->render('@Rental/admin/Product/product_rental_list_status.twig', [
            'rental_statuses' => $rentalStatuses,
        ]);
    }

    /**
     * 商品のレンタル注文統計を取得
     *
     * @param \Eccube\Entity\Product $product
     * @return array
     */
    private function getProductOrderStatistics($product)
    {
        $rentalProduct = $this->rentalProductRepository->findByProduct($product);
        
        if (!$rentalProduct) {
            return null;
        }

        // 基本統計
        $totalOrders = $this->orderRepository->count(['RentalProduct' => $rentalProduct]);
        $activeOrders = $this->orderRepository->count([
            'RentalProduct' => $rentalProduct,
            'status' => \Plugin\Rental\Entity\RentalOrder::STATUS_ACTIVE
        ]);
        $completedOrders = $this->orderRepository->count([
            'RentalProduct' => $rentalProduct,
            'status' => \Plugin\Rental\Entity\RentalOrder::STATUS_RETURNED
        ]);
        $overdueOrders = $this->orderRepository->count([
            'RentalProduct' => $rentalProduct,
            'status' => \Plugin\Rental\Entity\RentalOrder::STATUS_OVERDUE
        ]);

        // 売上統計
        $orders = $this->orderRepository->findBy(['RentalProduct' => $rentalProduct]);
        $totalRevenue = '0';
        $totalRentalDays = 0;
        
        foreach ($orders as $order) {
            $totalRevenue = bcadd($totalRevenue, $order->getTotalAmount(), 2);
            $totalRentalDays += $order->getRentalDays();
        }

        $avgRentalDays = $totalOrders > 0 ? round($totalRentalDays / $totalOrders, 1) : 0;
        $avgOrderAmount = $totalOrders > 0 ? bcdiv($totalRevenue, $totalOrders, 2) : '0';

        return [
            'total_orders' => $totalOrders,
            'active_orders' => $activeOrders,
            'completed_orders' => $completedOrders,
            'overdue_orders' => $overdueOrders,
            'total_revenue' => $totalRevenue,
            'avg_rental_days' => $avgRentalDays,
            'avg_order_amount' => $avgOrderAmount,
        ];
    }

    /**
     * 最近のレンタル注文を取得
     *
     * @param \Eccube\Entity\Product $product
     * @return array
     */
    private function getRecentRentalOrders($product)
    {
        $rentalProduct = $this->rentalProductRepository->findByProduct($product);
        
        if (!$rentalProduct) {
            return [];
        }

        return $this->orderRepository->findBy(
            ['RentalProduct' => $rentalProduct],
            ['create_date' => 'DESC'],
            5
        );
    }

    /**
     * 料金設定の分析
     *
     * @param \Plugin\Rental\Entity\RentalProduct $rentalProduct
     * @return array
     */
    private function analyzePricingSetting($rentalProduct)
    {
        $analysis = [
            'has_daily' => !empty($rentalProduct->getDailyPrice()),
            'has_weekly' => !empty($rentalProduct->getWeeklyPrice()),
            'has_monthly' => !empty($rentalProduct->getMonthlyPrice()),
            'price_comparison' => [],
            'recommendations' => [],
        ];

        // 期間別料金比較
        if ($analysis['has_daily']) {
            $dailyPrice = $rentalProduct->getDailyPrice();
            $analysis['price_comparison']['1_day'] = $dailyPrice;
            $analysis['price_comparison']['7_days_daily'] = bcmul($dailyPrice, 7, 2);
            $analysis['price_comparison']['30_days_daily'] = bcmul($dailyPrice, 30, 2);
        }

        if ($analysis['has_weekly']) {
            $weeklyPrice = $rentalProduct->getWeeklyPrice();
            $analysis['price_comparison']['7_days_weekly'] = $weeklyPrice;
            $analysis['price_comparison']['30_days_weekly'] = bcmul($weeklyPrice, ceil(30/7), 2);
        }

        if ($analysis['has_monthly']) {
            $monthlyPrice = $rentalProduct->getMonthlyPrice();
            $analysis['price_comparison']['30_days_monthly'] = $monthlyPrice;
        }

        // 推奨事項
        if (!$analysis['has_daily'] && !$analysis['has_weekly'] && !$analysis['has_monthly']) {
            $analysis['recommendations'][] = '料金設定が不完全です。最低1つの料金を設定してください。';
        }

        if ($analysis['has_daily'] && $analysis['has_weekly']) {
            $dailyFor7Days = bcmul($rentalProduct->getDailyPrice(), 7, 2);
            $weeklyPrice = $rentalProduct->getWeeklyPrice();
            
            if (bccomp($weeklyPrice, $dailyFor7Days, 2) >= 0) {
                $analysis['recommendations'][] = '週額料金が日額×7日よりも高く設定されています。';
            }
        }

        if (!$rentalProduct->getDepositAmount() && $rentalProduct->getMonthlyPrice()) {
            $analysis['recommendations'][] = '長期レンタルの場合は保証金の設定を検討してください。';
        }

        return $analysis;
    }

    /**
     * レンタル設定を取得
     *
     * @return array
     */
    private function getRentalConfig()
    {
        // 簡易版の設定情報（実際は ConfigRepository から取得）
        return [
            'max_rental_days' => 30,
            'min_rental_days' => 1,
            'auto_approval_enabled' => false,
            'deposit_required' => false,
        ];
    }

    /**
     * レンタル機能の有効性をチェック
     *
     * @param \Eccube\Entity\Product $product
     * @return bool
     */
    public function isRentalAvailable($product)
    {
        $rentalProduct = $this->rentalProductRepository->findByProduct($product);
        
        return $rentalProduct && 
               $rentalProduct->getIsRentalEnabled() && 
               $rentalProduct->hasPricingSetting();
    }

    /**
     * レンタル在庫状況を取得
     *
     * @param \Eccube\Entity\Product $product
     * @return array
     */
    public function getRentalStockStatus($product)
    {
        if (!$this->isRentalAvailable($product)) {
            return ['status' => 'disabled', 'message' => 'レンタル無効'];
        }

        $inventoryInfo = $this->inventoryRepository->getInventoryDetails($product);
        
        if ($inventoryInfo['actual_available'] <= 0) {
            return ['status' => 'out_of_stock', 'message' => '在庫なし'];
        } elseif ($inventoryInfo['actual_available'] <= 5) {
            return ['status' => 'low_stock', 'message' => '在庫僅少'];
        } else {
            return ['status' => 'in_stock', 'message' => '在庫あり'];
        }
    }

    /**
     * 商品詳細ページでのレンタル情報表示
     *
     * @param array $parameters
     * @return string
     */
    public function addRentalInfoToDetail(array $parameters = [])
    {
        $product = $parameters['Product'] ?? null;

        if (!$product || !$this->isRentalAvailable($product)) {
            return '';
        }

        $rentalProduct = $this->rentalProductRepository->findByProduct($product);
        $stockStatus = $this->getRentalStockStatus($product);

        return $this->twig->render('@Rental/admin/Product/product_rental_detail_info.twig', [
            'Product' => $product,
            'rental_product' => $rentalProduct,
            'stock_status' => $stockStatus,
        ]);
    }
}