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

namespace Plugin\Rental\EventListener;

use Eccube\Entity\Customer;
use Eccube\Event\TemplateEvent;
use Plugin\Rental\Entity\RentalOrder;
use Plugin\Rental\Repository\RentalOrderRepository;
use Plugin\Rental\Repository\RentalConfigRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * マイページ連携EventListener
 */
class MyPageListener
{
    /**
     * @var RentalOrderRepository
     */
    private $rentalOrderRepository;

    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * コンストラクタ
     */
    public function __construct(
        RentalOrderRepository $rentalOrderRepository,
        RentalConfigRepository $configRepository,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        TokenStorageInterface $tokenStorage
    ) {
        $this->rentalOrderRepository = $rentalOrderRepository;
        $this->configRepository = $configRepository;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * マイページトップにレンタル情報を追加
     *
     * @param TemplateEvent $event
     */
    public function onMyPageTop(TemplateEvent $event)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return;
        }

        // レンタル注文情報を取得
        $rentalData = $this->getRentalSummaryData($customer);
        
        // マイページにレンタル情報を追加
        $this->addRentalSummaryToMyPage($event, $rentalData);
        
        // レンタル関連のCSS/JSを追加
        $this->addMyPageAssets($event);
    }

    /**
     * マイページナビゲーションにレンタルメニューを追加
     *
     * @param TemplateEvent $event
     */
    public function onMyPageNav(TemplateEvent $event)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return;
        }

        // レンタル履歴の件数を取得
        $rentalCount = $this->rentalOrderRepository->count(['Customer' => $customer]);
        
        $navHtml = '
        <li class="ec-headerNaviRole__item">
            <a href="' . $this->urlGenerator->generate('mypage_rental_history') . '" class="ec-headerNaviRole__itemLink">
                <i class="fa fa-calendar-alt"></i> レンタル履歴
                ' . ($rentalCount > 0 ? '<span class="badge badge-secondary">' . $rentalCount . '</span>' : '') . '
            </a>
        </li>';

        // ナビゲーションメニューに追加
        $search = '</ul>';
        $replace = $navHtml . $search;
        
        $source = $event->getSource();
        $event->setSource(str_replace($search, $replace, $source));
    }

    /**
     * レンタル履歴ページの処理
     *
     * @param TemplateEvent $event
     */
    public function onRentalHistory(TemplateEvent $event)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return;
        }

        // レンタル履歴データを取得
        $rentalOrders = $this->rentalOrderRepository->findBy(
            ['Customer' => $customer],
            ['create_date' => 'DESC'],
            20
        );

        // パラメータに追加
        $parameters = $event->getParameters();
        $parameters['rental_orders'] = $rentalOrders;
        $parameters['rental_statistics'] = $this->getRentalStatistics($customer);
        $event->setParameters($parameters);

        // レンタル履歴用のスタイルを追加
        $this->addRentalHistoryAssets($event);
    }

    /**
     * 現在ログイン中の顧客を取得
     *
     * @return Customer|null
     */
    private function getCustomer(): ?Customer
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();
        return $user instanceof Customer ? $user : null;
    }

    /**
     * レンタルサマリーデータを取得
     *
     * @param Customer $customer
     * @return array
     */
    private function getRentalSummaryData(Customer $customer): array
    {
        $activeRentals = $this->rentalOrderRepository->findBy([
            'Customer' => $customer,
            'status' => [RentalOrder::STATUS_ACTIVE, RentalOrder::STATUS_EXTENDED]
        ]);

        $upcomingRentals = $this->rentalOrderRepository->createQueryBuilder('ro')
            ->where('ro.Customer = :customer')
            ->andWhere('ro.status = :status')
            ->andWhere('ro.rental_start_date > :today')
            ->setParameter('customer', $customer)
            ->setParameter('status', RentalOrder::STATUS_APPROVED)
            ->setParameter('today', new \DateTime())
            ->orderBy('ro.rental_start_date', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $overdueRentals = $this->rentalOrderRepository->createQueryBuilder('ro')
            ->where('ro.Customer = :customer')
            ->andWhere('ro.status = :status OR (ro.status = :active_status AND ro.rental_end_date < :today)')
            ->setParameter('customer', $customer)
            ->setParameter('status', RentalOrder::STATUS_OVERDUE)
            ->setParameter('active_status', RentalOrder::STATUS_ACTIVE)
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getResult();

        return [
            'active_rentals' => $activeRentals,
            'upcoming_rentals' => $upcomingRentals,
            'overdue_rentals' => $overdueRentals,
            'total_rentals' => $this->rentalOrderRepository->count(['Customer' => $customer]),
        ];
    }

    /**
     * マイページにレンタルサマリーを追加
     *
     * @param TemplateEvent $event
     * @param array $rentalData
     */
    private function addRentalSummaryToMyPage(TemplateEvent $event, array $rentalData)
    {
        $summaryHtml = '
        <div class="rental-summary-section">
            <h4><i class="fa fa-calendar-alt"></i> レンタル状況</h4>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="rental-status-card active">
                        <h5>' . count($rentalData['active_rentals']) . '</h5>
                        <p>レンタル中</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rental-status-card upcoming">
                        <h5>' . count($rentalData['upcoming_rentals']) . '</h5>
                        <p>予約済み</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rental-status-card overdue">
                        <h5>' . count($rentalData['overdue_rentals']) . '</h5>
                        <p>返却遅延</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rental-status-card total">
                        <h5>' . $rentalData['total_rentals'] . '</h5>
                        <p>総レンタル数</p>
                    </div>
                </div>
            </div>';

        // アクティブなレンタルがある場合
        if (!empty($rentalData['active_rentals'])) {
            $summaryHtml .= '
            <div class="active-rentals mt-3">
                <h6>現在レンタル中の商品</h6>
                <div class="row">';
            
            foreach (array_slice($rentalData['active_rentals'], 0, 3) as $rental) {
                $daysLeft = $rental->getRentalEndDate()->diff(new \DateTime())->days;
                $summaryHtml .= '
                <div class="col-md-4">
                    <div class="rental-item-card">
                        <h6>' . htmlspecialchars($rental->getRentalProduct()->getProduct()->getName()) . '</h6>
                        <small>返却まであと' . $daysLeft . '日</small>
                        <br>
                        <a href="' . $this->urlGenerator->generate('mypage_rental_detail', ['id' => $rental->getId()]) . '" 
                           class="btn btn-sm btn-outline-primary">詳細</a>
                    </div>
                </div>';
            }
            
            $summaryHtml .= '</div></div>';
        }

        // 返却遅延がある場合の警告
        if (!empty($rentalData['overdue_rentals'])) {
            $summaryHtml .= '
            <div class="alert alert-warning mt-3">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>返却期限を過ぎている商品があります。</strong>
                速やかに返却手続きを行ってください。
                <a href="' . $this->urlGenerator->generate('mypage_rental_history') . '?status=overdue" class="alert-link">
                    詳細を確認
                </a>
            </div>';
        }

        $summaryHtml .= '
            <div class="rental-actions mt-3">
                <a href="' . $this->urlGenerator->generate('mypage_rental_history') . '" 
                   class="btn btn-primary">
                    <i class="fa fa-list"></i> レンタル履歴を見る
                </a>
                <a href="' . $this->urlGenerator->generate('rental_product_list') . '" 
                   class="btn btn-outline-secondary">
                    <i class="fa fa-search"></i> 商品を探す
                </a>
            </div>
        </div>';

        // マイページの適切な位置に追加
        $search = '<div class="ec-mypageRole__main">';
        $replace = $search . $summaryHtml;
        
        $source = $event->getSource();
        $event->setSource(str_replace($search, $replace, $source));
    }

    /**
     * マイページ用アセットを追加
     *
     * @param TemplateEvent $event
     */
    private function addMyPageAssets(TemplateEvent $event)
    {
        $css = '
        <style>
        .rental-summary-section {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .rental-status-card {
            text-align: center;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            color: white;
        }
        .rental-status-card.active { background-color: #28a745; }
        .rental-status-card.upcoming { background-color: #17a2b8; }
        .rental-status-card.overdue { background-color: #dc3545; }
        .rental-status-card.total { background-color: #6c757d; }
        .rental-status-card h5 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }
        .rental-status-card p {
            margin: 0;
            font-size: 0.9rem;
        }
        .rental-item-card {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            background: white;
            margin-bottom: 1rem;
        }
        .rental-item-card h6 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        .rental-actions {
            text-align: center;
        }
        .rental-actions .btn {
            margin: 0 0.5rem;
        }
        </style>';

        $event->addSnippet($css);
    }

    /**
     * レンタル履歴用アセットを追加
     *
     * @param TemplateEvent $event
     */
    private function addRentalHistoryAssets(TemplateEvent $event)
    {
        $css = '
        <style>
        .rental-history-item {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: white;
        }
        .rental-status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-active { background-color: #28a745; color: #fff; }
        .status-returned { background-color: #6c757d; color: #fff; }
        .status-overdue { background-color: #dc3545; color: #fff; }
        .status-cancelled { background-color: #6f42c1; color: #fff; }
        .rental-period {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .rental-amount {
            font-size: 1.1rem;
            font-weight: bold;
            color: #007bff;
        }
        </style>';

        $js = '
        <script>
        $(document).ready(function() {
            // ステータスフィルター
            $("#rental-status-filter").on("change", function() {
                const status = $(this).val();
                if (status) {
                    window.location.href = "' . $this->urlGenerator->generate('mypage_rental_history') . '?status=" + status;
                } else {
                    window.location.href = "' . $this->urlGenerator->generate('mypage_rental_history') . '";
                }
            });

            // 詳細表示
            $(".rental-detail-btn").on("click", function() {
                const orderId = $(this).data("order-id");
                window.location.href = "' . $this->urlGenerator->generate('mypage_rental_detail', ['id' => '__ID__']) . '".replace("__ID__", orderId);
            });
        });
        </script>';

        $event->addSnippet($css);
        $event->addSnippet($js);
    }

    /**
     * レンタル統計を取得
     *
     * @param Customer $customer
     * @return array
     */
    private function getRentalStatistics(Customer $customer): array
    {
        $totalOrders = $this->rentalOrderRepository->count(['Customer' => $customer]);
        $completedOrders = $this->rentalOrderRepository->count([
            'Customer' => $customer,
            'status' => RentalOrder::STATUS_COMPLETED
        ]);
        
        $totalAmount = $this->rentalOrderRepository->createQueryBuilder('ro')
            ->select('SUM(ro.total_amount)')
            ->where('ro.Customer = :customer')
            ->andWhere('ro.status != :cancelled')
            ->setParameter('customer', $customer)
            ->setParameter('cancelled', RentalOrder::STATUS_CANCELLED)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'completion_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0,
            'total_amount' => $totalAmount ?: 0,
            'average_order_value' => $totalOrders > 0 ? round($totalAmount / $totalOrders, 0) : 0,
        ];
    }

    /**
     * 注文詳細ページでレンタル情報を表示
     *
     * @param TemplateEvent $event
     */
    public function onOrderDetail(TemplateEvent $event)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return;
        }

        // レンタル注文へのリンクを追加
        $linkHtml = '
        <div class="rental-link-section mt-3">
            <a href="' . $this->urlGenerator->generate('mypage_rental_history') . '" 
               class="btn btn-outline-primary">
                <i class="fa fa-calendar-alt"></i> レンタル履歴を確認
            </a>
        </div>';

        $search = '</div><!-- /.ec-orderRole__detail -->';
        $replace = $linkHtml . $search;
        
        $source = $event->getSource();
        $event->setSource(str_replace($search, $replace, $source));
    }
}