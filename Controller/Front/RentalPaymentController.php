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

namespace Plugin\Rental\Controller\Front;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Plugin\Rental\Entity\RentalOrder;
use Plugin\Rental\Entity\RentalPayment;
use Plugin\Rental\Repository\RentalOrderRepository;
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Service\RentalService;
use Plugin\Rental\Service\RentalCartService;
use Plugin\Rental\Service\RentalPaymentService;
use Plugin\Rental\Service\RentalNotificationService;
use Plugin\Rental\Form\Type\RentalOrderType;
use Plugin\Rental\Form\Type\RentalPaymentType;
use Plugin\Rental\Exception\RentalException;
use Plugin\Rental\Exception\RentalPaymentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

/**
 * レンタル決済処理コントローラー
 * 
 * @Route("/rental/order")
 */
class RentalPaymentController extends AbstractController
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
     * @var RentalCartService
     */
    private $cartService;

    /**
     * @var RentalPaymentService
     */
    private $paymentService;

    /**
     * @var RentalNotificationService
     */
    private $notificationService;

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
        RentalCartService $cartService,
        RentalPaymentService $paymentService,
        RentalNotificationService $notificationService,
        EntityManagerInterface $entityManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->configRepository = $configRepository;
        $this->rentalService = $rentalService;
        $this->cartService = $cartService;
        $this->paymentService = $paymentService;
        $this->notificationService = $notificationService;
        $this->entityManager = $entityManager;
    }

    /**
     * 注文情報入力画面
     *
     * @Route("/input", name="rental_order_input", methods={"GET", "POST"})
     * @Template("@Rental/default/input.twig")
     */
    public function input(Request $request)
    {
        // カート確認
        $cartItems = $this->cartService->getCartItems();
        if (empty($cartItems)) {
            $this->addError('カートが空です。商品を追加してください。');
            return $this->redirectToRoute('rental_product_list');
        }

        // カート内容検証
        $conflicts = $this->cartService->validateCartItems($cartItems);
        if (!empty($conflicts)) {
            $this->addError('カート内に在庫不足の商品があります。カートを確認してください。');
            return $this->redirectToRoute('rental_cart');
        }

        // 顧客情報取得
        $customer = $this->getUser();
        if (!$customer instanceof Customer) {
            // ゲスト注文の場合の処理
            return $this->redirectToRoute('shopping_login');
        }

        // 注文エンティティ作成
        $order = $this->rentalService->createOrderFromCart($cartItems, $customer);

        // フォーム作成
        $form = $this->createForm(RentalOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // 注文情報をセッションに保存
                $request->getSession()->set('rental_order_data', serialize($order));
                
                return $this->redirectToRoute('rental_order_confirm');
                
            } catch (RentalException $e) {
                $this->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->addError('注文情報の処理に失敗しました。');
                log_error('レンタル注文入力エラー', ['error' => $e->getMessage()]);
            }
        }

        return [
            'form' => $form->createView(),
            'order' => $order,
            'cart_items' => $cartItems,
            'cart_summary' => $this->cartService->calculateCartTotal($cartItems),
        ];
    }

    /**
     * 注文確認画面
     *
     * @Route("/confirm", name="rental_order_confirm", methods={"GET", "POST"})
     * @Template("@Rental/default/confirm.twig")
     */
    public function confirm(Request $request)
    {
        // セッションから注文データ取得
        $orderData = $request->getSession()->get('rental_order_data');
        if (!$orderData) {
            $this->addError('注文情報が見つかりません。最初からやり直してください。');
            return $this->redirectToRoute('rental_order_input');
        }

        $order = unserialize($orderData);

        if ($request->isMethod('POST')) {
            if ($request->request->get('mode') === 'back') {
                return $this->redirectToRoute('rental_order_input');
            }

            try {
                // 最終在庫確認
                $this->rentalService->validateOrderInventory($order);
                
                // 注文確定処理
                $confirmedOrder = $this->rentalService->confirmOrder($order);
                
                // セッションクリア
                $request->getSession()->remove('rental_order_data');
                
                // 決済画面へ
                return $this->redirectToRoute('rental_payment', ['id' => $confirmedOrder->getId()]);
                
            } catch (RentalException $e) {
                $this->addError($e->getMessage());
                return $this->redirectToRoute('rental_cart');
            } catch (\Exception $e) {
                $this->addError('注文の確定に失敗しました。');
                log_error('レンタル注文確定エラー', ['error' => $e->getMessage()]);
            }
        }

        return [
            'order' => $order,
            'calculation_details' => $this->rentalService->getOrderCalculationDetails($order),
        ];
    }

    /**
     * 決済画面
     *
     * @Route("/payment/{id}", name="rental_payment", methods={"GET", "POST"}, requirements={"id" = "\d+"})
     * @Template("@Rental/default/payment.twig")
     */
    public function payment(Request $request, RentalOrder $order)
    {
        // 注文所有者確認
        if (!$this->rentalService->isOrderOwner($order, $this->getUser())) {
            throw $this->createNotFoundException('注文が見つかりません。');
        }

        // 決済済みチェック
        if ($order->getStatus() !== RentalOrder::STATUS_PENDING) {
            $this->addError('この注文は既に処理済みです。');
            return $this->redirectToRoute('rental_mypage_history');
        }

        // 決済フォーム作成
        $payment = new RentalPayment();
        $payment->setRentalOrder($order);
        
        $form = $this->createForm(RentalPaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // 決済処理実行
                $result = $this->paymentService->processPayment($payment);
                
                if ($result['success']) {
                    // 決済成功時の処理
                    $this->rentalService->completePayment($order, $payment);
                    
                    // 通知送信
                    $this->notificationService->sendOrderConfirmation($order);
                    
                    return $this->redirectToRoute('rental_payment_complete', ['id' => $order->getId()]);
                } else {
                    // 決済失敗時の処理
                    $this->addError($result['message'] ?? '決済処理に失敗しました。');
                }
                
            } catch (RentalPaymentException $e) {
                $this->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->addError('決済処理中にエラーが発生しました。');
                log_error('レンタル決済エラー', [
                    'order_id' => $order->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'form' => $form->createView(),
            'order' => $order,
            'payment_methods' => $this->getAvailablePaymentMethods(),
            'order_summary' => $this->rentalService->getOrderSummary($order),
        ];
    }

    /**
     * 決済完了画面
     *
     * @Route("/complete/{id}", name="rental_payment_complete", methods={"GET"}, requirements={"id" = "\d+"})
     * @Template("@Rental/default/complete.twig")
     */
    public function complete(Request $request, RentalOrder $order)
    {
        // 注文所有者確認
        if (!$this->rentalService->isOrderOwner($order, $this->getUser())) {
            throw $this->createNotFoundException('注文が見つかりません。');
        }

        // カートクリア
        $this->cartService->clearCart();

        return [
            'order' => $order,
            'estimated_delivery' => $this->getEstimatedDeliveryDate($order),
            'rental_guidelines' => $this->getRentalGuidelines(),
        ];
    }

    /**
     * クレジットカード決済処理（Ajax）
     *
     * @Route("/process-credit", name="rental_payment_process_credit", methods={"POST"})
     */
    public function processCreditPayment(Request $request): JsonResponse
    {
        try {
            $orderId = $request->request->get('order_id');
            $tokenId = $request->request->get('token_id');
            
            $order = $this->orderRepository->find($orderId);
            
            if (!$order || !$this->rentalService->isOrderOwner($order, $this->getUser())) {
                throw new RentalPaymentException('不正なアクセスです。');
            }

            $result = $this->paymentService->processCreditCard($order, $tokenId);

            if ($result['success']) {
                $this->rentalService->completePayment($order, $result['payment']);
                $this->notificationService->sendOrderConfirmation($order);
                
                return new JsonResponse([
                    'success' => true,
                    'message' => '決済が完了しました',
                    'redirect_url' => $this->generateUrl('rental_payment_complete', ['id' => $order->getId()])
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (RentalPaymentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            log_error('クレジットカード決済エラー', ['error' => $e->getMessage()]);
            return new JsonResponse([
                'success' => false,
                'message' => '決済処理中にエラーが発生しました'
            ], 500);
        }
    }

    /**
     * コンビニ決済処理
     *
     * @Route("/process-convenience", name="rental_payment_process_convenience", methods={"POST"})
     */
    public function processConveniencePayment(Request $request): JsonResponse
    {
        try {
            $orderId = $request->request->get('order_id');
            $convenienceType = $request->request->get('convenience_type');
            
            $order = $this->orderRepository->find($orderId);
            
            if (!$order || !$this->rentalService->isOrderOwner($order, $this->getUser())) {
                throw new RentalPaymentException('不正なアクセスです。');
            }

            $result = $this->paymentService->processConvenienceStore($order, $convenienceType);

            return new JsonResponse([
                'success' => true,
                'payment_code' => $result['payment_code'],
                'payment_url' => $result['payment_url'],
                'due_date' => $result['due_date'],
                'message' => 'コンビニ決済の準備が完了しました'
            ]);

        } catch (RentalPaymentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            log_error('コンビニ決済エラー', ['error' => $e->getMessage()]);
            return new JsonResponse([
                'success' => false,
                'message' => '決済処理中にエラーが発生しました'
            ], 500);
        }
    }

    /**
     * 銀行振込処理
     *
     * @Route("/process-bank-transfer", name="rental_payment_process_bank_transfer", methods={"POST"})
     */
    public function processBankTransfer(Request $request): JsonResponse
    {
        try {
            $orderId = $request->request->get('order_id');
            
            $order = $this->orderRepository->find($orderId);
            
            if (!$order || !$this->rentalService->isOrderOwner($order, $this->getUser())) {
                throw new RentalPaymentException('不正なアクセスです。');
            }

            $result = $this->paymentService->processBankTransfer($order);

            return new JsonResponse([
                'success' => true,
                'bank_info' => $result['bank_info'],
                'transfer_deadline' => $result['transfer_deadline'],
                'reference_number' => $result['reference_number'],
                'message' => '振込先情報を生成しました'
            ]);

        } catch (RentalPaymentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            log_error('銀行振込エラー', ['error' => $e->getMessage()]);
            return new JsonResponse([
                'success' => false,
                'message' => '処理中にエラーが発生しました'
            ], 500);
        }
    }

    /**
     * 決済状況確認（Ajax）
     *
     * @Route("/check-status/{id}", name="rental_payment_check_status", methods={"GET"}, requirements={"id" = "\d+"})
     */
    public function checkPaymentStatus(Request $request, RentalOrder $order): JsonResponse
    {
        try {
            if (!$this->rentalService->isOrderOwner($order, $this->getUser())) {
                throw new RentalPaymentException('不正なアクセスです。');
            }

            $status = $this->paymentService->getPaymentStatus($order);

            return new JsonResponse([
                'order_id' => $order->getId(),
                'payment_status' => $status['status'],
                'payment_method' => $status['method'],
                'paid_amount' => $status['paid_amount'],
                'payment_date' => $status['payment_date'],
                'next_action' => $status['next_action'] ?? null,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => '決済状況の取得に失敗しました'
            ], 500);
        }
    }

    /**
     * 注文キャンセル
     *
     * @Route("/cancel/{id}", name="rental_order_cancel", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function cancel(Request $request, RentalOrder $order): JsonResponse
    {
        try {
            if (!$this->rentalService->isOrderOwner($order, $this->getUser())) {
                throw new RentalException('不正なアクセスです。');
            }

            $cancelReason = $request->request->get('cancel_reason', '');
            
            $result = $this->rentalService->cancelOrder($order, $cancelReason);

            // 返金処理があれば実行
            if ($result['refund_required']) {
                $this->paymentService->processRefund($order, $result['refund_amount']);
            }

            // 通知送信
            $this->notificationService->sendCancellationNotification($order);

            return new JsonResponse([
                'success' => true,
                'message' => '注文をキャンセルしました',
                'refund_info' => $result['refund_info'] ?? null,
            ]);

        } catch (RentalException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            log_error('注文キャンセルエラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
            return new JsonResponse([
                'success' => false,
                'message' => 'キャンセル処理中にエラーが発生しました'
            ], 500);
        }
    }

    /**
     * 利用可能な決済方法を取得
     *
     * @return array
     */
    private function getAvailablePaymentMethods(): array
    {
        $methods = $this->configRepository->getArray('payment_methods', [
            'credit_card' => 'クレジットカード',
            'convenience' => 'コンビニ決済',
            'bank_transfer' => '銀行振込',
        ]);

        $available = [];
        foreach ($methods as $key => $name) {
            if ($this->paymentService->isPaymentMethodAvailable($key)) {
                $available[$key] = [
                    'name' => $name,
                    'description' => $this->getPaymentMethodDescription($key),
                    'fee' => $this->paymentService->getPaymentFee($key),
                ];
            }
        }

        return $available;
    }

    /**
     * 決済方法の説明を取得
     *
     * @param string $method
     * @return string
     */
    private function getPaymentMethodDescription(string $method): string
    {
        $descriptions = [
            'credit_card' => 'VISA、MasterCard、JCB、AMEX がご利用いただけます',
            'convenience' => 'セブンイレブン、ローソン、ファミリーマートでお支払い可能',
            'bank_transfer' => '指定口座への銀行振込（振込手数料はお客様負担）',
        ];

        return $descriptions[$method] ?? '';
    }

    /**
     * 配送予定日を取得
     *
     * @param RentalOrder $order
     * @return \DateTime
     */
    private function getEstimatedDeliveryDate(RentalOrder $order): \DateTime
    {
        $bufferDays = $this->configRepository->getInt('delivery_buffer_days', 2);
        $rentalStartDate = $order->getRentalStartDate();
        
        return (clone $rentalStartDate)->sub(new \DateInterval("P{$bufferDays}D"));
    }

    /**
     * レンタルガイドラインを取得
     *
     * @return array
     */
    private function getRentalGuidelines(): array
    {
        return [
            'care_instructions' => $this->configRepository->getString('care_instructions', ''),
            'return_policy' => $this->configRepository->getString('return_policy', ''),
            'damage_policy' => $this->configRepository->getString('damage_policy', ''),
            'cancellation_policy' => $this->configRepository->getString('cancellation_policy', ''),
        ];
    }
}