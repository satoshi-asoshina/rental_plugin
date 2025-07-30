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
use Eccube\Util\FormUtil;
use Plugin\Rental\Entity\RentalOrder;
use Plugin\Rental\Repository\RentalOrderRepository;
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Service\RentalService;
use Plugin\Rental\Service\RentalCalculationService;
use Plugin\Rental\Form\Type\Admin\RentalSearchType;
use Plugin\Rental\Form\Type\RentalOrderType;
use Plugin\Rental\Exception\RentalException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * レンタル注文管理コントローラー
 * 
 * @Route("/%eccube_admin_route%/rental/order")
 */
class RentalController extends AbstractController
{
    /**
     * @var RentalOrderRepository
     */
    private $orderRepository;

    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * @var RentalService
     */
    private $rentalService;

    /**
     * @var RentalCalculationService
     */
    private $calculationService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * コンストラクタ
     */
    public function __construct(
        RentalOrderRepository $orderRepository,
        RentalConfigRepository $configRepository,
        RentalService $rentalService,
        RentalCalculationService $calculationService,
        EntityManagerInterface $entityManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->configRepository = $configRepository;
        $this->rentalService = $rentalService;
        $this->calculationService = $calculationService;
        $this->entityManager = $entityManager;
    }

    /**
     * レンタル注文一覧
     *
     * @Route("", name="admin_rental_order", methods={"GET", "POST"})
     * @Template("@Rental/admin/rental_list.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $searchForm = $this->createForm(RentalSearchType::class);
        $searchForm->handleRequest($request);

        $qb = $this->orderRepository->createQueryBuilder('ro')
            ->leftJoin('ro.Customer', 'c')
            ->leftJoin('ro.RentalProduct', 'rp')
            ->leftJoin('rp.Product', 'p')
            ->orderBy('ro.create_date', 'DESC');

        // 検索条件適用
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $searchData = $searchForm->getData();
            $qb = $this->orderRepository->getQueryBuilderBySearchData($qb, $searchData);
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            $this->eccubeConfig['eccube_default_page_count']
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'order_statistics' => $this->getOrderStatistics(),
            'status_counts' => $this->getStatusCounts(),
        ];
    }

    /**
     * レンタル注文詳細
     *
     * @Route("/{id}", name="admin_rental_order_detail", methods={"GET"}, requirements={"id" = "\d+"})
     * @Template("@Rental/admin/rental_detail.twig")
     */
    public function detail(Request $request, RentalOrder $order)
    {
        $form = $this->createForm(RentalOrderType::class, $order, [
            'read_only' => true
        ]);

        return [
            'form' => $form->createView(),
            'order' => $order,
            'calculation_details' => $this->getCalculationDetails($order),
            'status_history' => $this->getStatusHistory($order),
            'related_orders' => $this->getRelatedOrders($order),
        ];
    }

    /**
     * レンタル注文編集
     *
     * @Route("/{id}/edit", name="admin_rental_order_edit", methods={"GET", "POST"}, requirements={"id" = "\d+"})
     * @Template("@Rental/admin/rental_edit.twig")
     */
    public function edit(Request $request, RentalOrder $order)
    {
        $form = $this->createForm(RentalOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // 料金再計算
                $this->calculationService->recalculateOrder($order);
                
                $this->entityManager->persist($order);
                $this->entityManager->flush();

                $this->addSuccess('レンタル注文を更新しました。', 'admin');

                return $this->redirectToRoute('admin_rental_order_detail', ['id' => $order->getId()]);

            } catch (\Exception $e) {
                $this->addError('レンタル注文の更新に失敗しました。', 'admin');
                log_error('レンタル注文更新エラー', [
                    'order_id' => $order->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'form' => $form->createView(),
            'order' => $order,
            'calculation_details' => $this->getCalculationDetails($order),
        ];
    }

    /**
     * レンタル注文承認
     *
     * @Route("/{id}/approve", name="admin_rental_order_approve", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function approve(Request $request, RentalOrder $order)
    {
        $this->isTokenValid();

        try {
            $this->rentalService->approveRentalOrder($order);
            $this->addSuccess('レンタル注文を承認しました。', 'admin');
        } catch (RentalException $e) {
            $this->addError($e->getMessage(), 'admin');
        } catch (\Exception $e) {
            $this->addError('承認処理に失敗しました。', 'admin');
            log_error('レンタル注文承認エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return $this->redirectToRoute('admin_rental_order_detail', ['id' => $order->getId()]);
    }

    /**
     * レンタル開始
     *
     * @Route("/{id}/start", name="admin_rental_order_start", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function start(Request $request, RentalOrder $order)
    {
        $this->isTokenValid();

        try {
            $this->rentalService->startRental($order);
            $this->addSuccess('レンタルを開始しました。', 'admin');
        } catch (RentalException $e) {
            $this->addError($e->getMessage(), 'admin');
        } catch (\Exception $e) {
            $this->addError('開始処理に失敗しました。', 'admin');
            log_error('レンタル開始エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return $this->redirectToRoute('admin_rental_order_detail', ['id' => $order->getId()]);
    }

    /**
     * レンタル返却
     *
     * @Route("/{id}/return", name="admin_rental_order_return", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function returnRental(Request $request, RentalOrder $order)
    {
        $this->isTokenValid();

        try {
            $returnDate = $request->request->get('return_date') 
                ? new \DateTime($request->request->get('return_date'))
                : new \DateTime();
                
            $condition = $request->request->get('return_condition', 'good');
            $notes = $request->request->get('return_notes', '');
            
            $this->rentalService->returnRental($order, $returnDate, $condition, $notes);
            $this->addSuccess('返却処理を完了しました。', 'admin');
        } catch (RentalException $e) {
            $this->addError($e->getMessage(), 'admin');
        } catch (\Exception $e) {
            $this->addError('返却処理に失敗しました。', 'admin');
            log_error('レンタル返却エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return $this->redirectToRoute('admin_rental_order_detail', ['id' => $order->getId()]);
    }

    /**
     * レンタル期間延長
     *
     * @Route("/{id}/extend", name="admin_rental_order_extend", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function extend(Request $request, RentalOrder $order)
    {
        $this->isTokenValid();

        try {
            $newEndDate = new \DateTime($request->request->get('new_end_date'));
            $extensionReason = $request->request->get('extension_reason', '');
            
            $this->rentalService->extendRental($order, $newEndDate, $extensionReason);
            $this->addSuccess('レンタル期間を延長しました。', 'admin');
        } catch (RentalException $e) {
            $this->addError($e->getMessage(), 'admin');
        } catch (\Exception $e) {
            $this->addError('延長処理に失敗しました。', 'admin');
            log_error('レンタル延長エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return $this->redirectToRoute('admin_rental_order_detail', ['id' => $order->getId()]);
    }

    /**
     * レンタル注文キャンセル
     *
     * @Route("/{id}/cancel", name="admin_rental_order_cancel", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function cancel(Request $request, RentalOrder $order)
    {
        $this->isTokenValid();

        try {
            $cancelReason = $request->request->get('cancel_reason', '');
            $refundAmount = $request->request->get('refund_amount', 0);
            
            $this->rentalService->cancelRental($order, $cancelReason, $refundAmount);
            $this->addSuccess('レンタル注文をキャンセルしました。', 'admin');
        } catch (RentalException $e) {
            $this->addError($e->getMessage(), 'admin');
        } catch (\Exception $e) {
            $this->addError('キャンセル処理に失敗しました。', 'admin');
            log_error('レンタルキャンセルエラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return $this->redirectToRoute('admin_rental_order_detail', ['id' => $order->getId()]);
    }

    /**
     * 一括操作
     *
     * @Route("/bulk", name="admin_rental_order_bulk", methods={"POST"})
     */
    public function bulk(Request $request)
    {
        $this->isTokenValid();

        $action = $request->request->get('bulk_action');
        $orderIds = $request->request->get('order_ids', []);

        if (empty($orderIds)) {
            $this->addError('操作対象の注文を選択してください。', 'admin');
            return $this->redirectToRoute('admin_rental_order');
        }

        try {
            $count = 0;
            foreach ($orderIds as $orderId) {
                $order = $this->orderRepository->find($orderId);
                if (!$order) continue;

                switch ($action) {
                    case 'approve':
                        if ($order->getStatus() === RentalOrder::STATUS_PENDING) {
                            $this->rentalService->approveRentalOrder($order);
                            $count++;
                        }
                        break;
                    case 'start':
                        if ($order->getStatus() === RentalOrder::STATUS_APPROVED) {
                            $this->rentalService->startRental($order);
                            $count++;
                        }
                        break;
                    case 'send_reminder':
                        if (in_array($order->getStatus(), [RentalOrder::STATUS_ACTIVE, RentalOrder::STATUS_EXTENDED])) {
                            $this->rentalService->sendReturnReminder($order);
                            $count++;
                        }
                        break;
                }
            }

            $this->addSuccess("{$count}件の注文を処理しました。", 'admin');

        } catch (\Exception $e) {
            $this->addError('一括処理に失敗しました。', 'admin');
            log_error('レンタル一括処理エラー', ['error' => $e->getMessage()]);
        }

        return $this->redirectToRoute('admin_rental_order');
    }

    /**
     * 延滞管理画面
     *
     * @Route("/overdue", name="admin_rental_order_overdue", methods={"GET"})
     * @Template("@Rental/admin/rental_overdue.twig")
     */
    public function overdue(Request $request, PaginatorInterface $paginator)
    {
        $qb = $this->orderRepository->createQueryBuilder('ro')
            ->where('ro.status = :status OR ro.rental_end_date < :today')
            ->setParameter('status', RentalOrder::STATUS_OVERDUE)
            ->setParameter('today', new \DateTime())
            ->orderBy('ro.rental_end_date', 'ASC');

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            $this->eccubeConfig['eccube_default_page_count']
        );

        return [
            'pagination' => $pagination,
            'overdue_summary' => $this->getOverdueSummary(),
        ];
    }

    /**
     * 返却期限通知
     *
     * @Route("/reminder", name="admin_rental_order_reminder", methods={"GET"})
     * @Template("@Rental/admin/rental_reminder.twig")
     */
    public function reminder(Request $request, PaginatorInterface $paginator)
    {
        $upcomingReturns = $this->rentalService->getUpcomingReturns();
        
        $pagination = $paginator->paginate(
            $upcomingReturns,
            $request->query->getInt('page', 1),
            $this->eccubeConfig['eccube_default_page_count']
        );

        return [
            'pagination' => $pagination,
            'reminder_settings' => [
                'reminder_days' => $this->configRepository->getReminderDays(),
            ],
        ];
    }

    /**
     * CSVエクスポート
     *
     * @Route("/export", name="admin_rental_order_export", methods={"POST"})
     */
    public function export(Request $request)
    {
        $this->isTokenValid();

        try {
            $searchForm = $this->createForm(RentalSearchType::class);
            $searchForm->handleRequest($request);

            $qb = $this->orderRepository->createQueryBuilder('ro');
            
            if ($searchForm->isSubmitted() && $searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $qb = $this->orderRepository->getQueryBuilderBySearchData($qb, $searchData);
            }

            $orders = $qb->getQuery()->getResult();

            $csvData = $this->generateCsvData($orders);
            
            $response = new Response($csvData);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="rental_orders_' . date('Y-m-d_H-i-s') . '.csv"');

            return $response;
            
        } catch (\Exception $e) {
            $this->addError('CSVエクスポートに失敗しました。', 'admin');
            log_error('レンタルCSVエクスポートエラー', ['error' => $e->getMessage()]);
            
            return $this->redirectToRoute('admin_rental_order');
        }
    }

    /**
     * Ajax: 注文統計取得
     *
     * @Route("/stats", name="admin_rental_order_stats", methods={"GET"})
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $stats = $this->getOrderStatistics();
            return new JsonResponse($stats);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'データ取得に失敗しました'], 500);
        }
    }

    /**
     * 注文統計を取得
     *
     * @return array
     */
    private function getOrderStatistics(): array
    {
        return [
            'total_orders' => $this->orderRepository->getTotalOrderCount(),
            'pending_orders' => $this->orderRepository->getOrderCountByStatus(RentalOrder::STATUS_PENDING),
            'active_rentals' => $this->orderRepository->getOrderCountByStatus(RentalOrder::STATUS_ACTIVE),
            'overdue_orders' => $this->orderRepository->getOverdueOrderCount(),
            'total_revenue' => $this->orderRepository->getTotalRevenue(),
            'monthly_revenue' => $this->orderRepository->getMonthlyRevenue(),
        ];
    }

    /**
     * ステータス別件数を取得
     *
     * @return array
     */
    private function getStatusCounts(): array
    {
        $counts = [];
        $statuses = RentalOrder::getStatuses();
        
        foreach ($statuses as $status => $label) {
            $counts[$status] = $this->orderRepository->getOrderCountByStatus($status);
        }
        
        return $counts;
    }

    /**
     * 料金計算詳細を取得
     *
     * @param RentalOrder $order
     * @return array
     */
    private function getCalculationDetails(RentalOrder $order): array
    {
        return $this->calculationService->getCalculationDetails($order);
    }

    /**
     * ステータス履歴を取得
     *
     * @param RentalOrder $order
     * @return array
     */
    private function getStatusHistory(RentalOrder $order): array
    {
        // RentalLogRepositoryから取得
        return $this->entityManager->getRepository('Plugin:Rental:RentalLog')
            ->findBy(['rental_order_id' => $order->getId()], ['create_date' => 'DESC']);
    }

    /**
     * 関連注文を取得
     *
     * @param RentalOrder $order
     * @return array
     */
    private function getRelatedOrders(RentalOrder $order): array
    {
        if (!$order->getCustomer()) {
            return [];
        }

        return $this->orderRepository->findBy(
            ['Customer' => $order->getCustomer()],
            ['create_date' => 'DESC'],
            5
        );
    }

    /**
     * 延滞サマリーを取得
     *
     * @return array
     */
    private function getOverdueSummary(): array
    {
        return [
            'total_overdue' => $this->orderRepository->getOverdueOrderCount(),
            'overdue_amount' => $this->orderRepository->getOverdueAmount(),
            'average_overdue_days' => $this->orderRepository->getAverageOverdueDays(),
        ];
    }

    /**
     * CSVデータを生成
     *
     * @param array $orders
     * @return string
     */
    private function generateCsvData(array $orders): string
    {
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        
        // ヘッダー行
        $headers = [
            '注文ID', '顧客名', '商品名', 'レンタル開始日', 'レンタル終了日',
            'ステータス', 'レンタル料金', '作成日', '更新日'
        ];
        $csv .= implode(',', $headers) . "\n";

        // データ行
        foreach ($orders as $order) {
            $row = [
                $order->getId(),
                $order->getCustomer() ? $order->getCustomer()->getName01() . ' ' . $order->getCustomer()->getName02() : '',
                $order->getRentalProduct() ? $order->getRentalProduct()->getProduct()->getName() : '',
                $order->getRentalStartDate() ? $order->getRentalStartDate()->format('Y-m-d') : '',
                $order->getRentalEndDate() ? $order->getRentalEndDate()->format('Y-m-d') : '',
                $order->getStatusName(),
                number_format($order->getTotalAmount()),
                $order->getCreateDate()->format('Y-m-d H:i:s'),
                $order->getUpdateDate()->format('Y-m-d H:i:s'),
            ];
            
            // CSVエスケープ処理
            $escapedRow = array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row);
            
            $csv .= implode(',', $escapedRow) . "\n";
        }

        return $csv;
    }
}