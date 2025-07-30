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

use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Plugin\Rental\Entity\RentalCart;
use Plugin\Rental\Entity\RentalOrder;
use Plugin\Rental\Entity\RentalProduct;
use Plugin\Rental\Entity\RentalInventory;
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Repository\RentalOrderRepository;
use Plugin\Rental\Repository\RentalProductRepository;
use Plugin\Rental\Repository\RentalCartRepository;
use Plugin\Rental\Repository\RentalInventoryRepository;
use Plugin\Rental\Repository\RentalPaymentRepository;
use Plugin\Rental\Repository\RentalLogRepository;
use Plugin\Rental\Exception\RentalException;
use Plugin\Rental\Exception\RentalValidationException;
use Plugin\Rental\Exception\RentalInventoryException;
use Plugin\Rental\Exception\RentalPaymentException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * レンタル管理メインビジネスロジック Service (完全版)
 */
class RentalService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * @var RentalOrderRepository
     */
    private $orderRepository;

    /**
     * @var RentalProductRepository
     */
    private $productRepository;

    /**
     * @var RentalCartRepository
     */
    private $cartRepository;

    /**
     * @var RentalInventoryRepository
     */
    private $inventoryRepository;

    /**
     * @var RentalPaymentRepository
     */
    private $paymentRepository;

    /**
     * @var RentalLogRepository
     */
    private $logRepository;

    /**
     * @var RentalCalculationService
     */
    private $calculationService;

    /**
     * @var RentalValidationService
     */
    private $validationService;

    /**
     * @var RentalNotificationService
     */
    private $notificationService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * コンストラクタ
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RentalConfigRepository $configRepository,
        RentalOrderRepository $orderRepository,
        RentalProductRepository $productRepository,
        RentalCartRepository $cartRepository,
        RentalInventoryRepository $inventoryRepository,
        RentalPaymentRepository $paymentRepository,
        RentalLogRepository $logRepository,
        RentalCalculationService $calculationService,
        RentalValidationService $validationService,
        RentalNotificationService $notificationService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->configRepository = $configRepository;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->cartRepository = $cartRepository;
        $this->inventoryRepository = $inventoryRepository;
        $this->paymentRepository = $paymentRepository;
        $this->logRepository = $logRepository;
        $this->calculationService = $calculationService;
        $this->validationService = $validationService;
        $this->notificationService = $notificationService;
        $this->logger = $logger;
    }

    /**
     * レンタル注文を作成
     *
     * @param Customer $customer 顧客
     * @param array $orderData 注文データ
     * @return RentalOrder
     * @throws RentalException
     */
    public function createRentalOrder(Customer $customer, array $orderData)
    {
        $this->entityManager->beginTransaction();
        
        try {
            // 顧客の検証
            $this->validationService->validateCustomerRentability($customer);

            // カート商品を取得
            $cartItems = $this->cartRepository->findBySessionOrCustomer(
                $orderData['session_id'] ?? '',
                $customer
            );

            if (empty($cartItems)) {
                throw new RentalValidationException('カートが空です');
            }

            // カート内容の検証
            $this->validateCartItems($cartItems);

            // 在庫の確認と仮予約
            $this->validateAndReserveInventory($cartItems);

            // 注文エンティティを作成
            $order = $this->createOrderEntity($customer, $cartItems, $orderData);

            // 注文を保存
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            // 在庫を正式に予約
            $this->reserveInventory($order, $cartItems);

            // カートをクリア
            $this->cartRepository->clearBySessionOrCustomer(
                $orderData['session_id'] ?? '',
                $customer
            );

            // ログ記録
            $this->logRepository->logOrderCreated($order, $customer);

            // 自動承認設定の場合は承認処理
            if ($this->configRepository->isAutoApprovalEnabled()) {
                $this->approveRentalOrder($order);
            }

            $this->entityManager->commit();

            // 通知送信
            $this->notificationService->sendOrderCreatedNotification($order, $customer);

            $this->logger->info('レンタル注文作成完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'customer_id' => $customer->getId(),
                'total_amount' => $order->getTotalAmount()
            ]);

            return $order;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('レンタル注文作成エラー', [
                'error' => $e->getMessage(),
                'customer_id' => $customer->getId(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new RentalException('注文の作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * レンタル注文を承認
     *
     * @param RentalOrder $order 注文
     * @return void
     * @throws RentalException
     */
    public function approveRentalOrder(RentalOrder $order)
    {
        $this->validationService->validateOrderStatus($order, [RentalOrder::STATUS_PENDING]);

        $this->entityManager->beginTransaction();

        try {
            $order->setStatus(RentalOrder::STATUS_RESERVED);
            $order->setApprovedDate(new \DateTime());
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            // ログ記録
            $this->logRepository->log(
                'order_approved',
                'レンタル注文が承認されました: ' . $order->getOrderNo(),
                'info',
                ['order_id' => $order->getId()],
                $order->getCustomer(),
                $order
            );

            $this->entityManager->commit();

            // 通知送信
            $this->notificationService->sendOrderApprovedNotification($order);

            $this->logger->info('レンタル注文承認完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo()
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logRepository->logError('レンタル注文承認エラー', $e, [
                'order_id' => $order->getId()
            ]);
            throw new RentalException('注文の承認に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * レンタル開始
     *
     * @param RentalOrder $order 注文
     * @return void
     * @throws RentalException
     */
    public function startRental(RentalOrder $order)
    {
        $this->validationService->validateOrderStatus($order, [RentalOrder::STATUS_RESERVED]);

        $this->entityManager->beginTransaction();

        try {
            $order->setStatus(RentalOrder::STATUS_ACTIVE);
            $order->setActualStartDate(new \DateTime());
            $this->entityManager->persist($order);

            // 在庫を予約からレンタル中に移行
            $this->activateInventory($order);

            $this->entityManager->flush();

            // ログ記録
            $this->logRepository->log(
                'rental_started',
                'レンタルが開始されました: ' . $order->getOrderNo(),
                'info',
                [
                    'order_id' => $order->getId(),
                    'actual_start_date' => $order->getActualStartDate()->format('Y-m-d H:i:s')
                ],
                $order->getCustomer(),
                $order
            );

            $this->entityManager->commit();

            // 通知送信
            $this->notificationService->sendRentalStartedNotification($order);

            $this->logger->info('レンタル開始完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo()
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logRepository->logError('レンタル開始エラー', $e, [
                'order_id' => $order->getId()
            ]);
            throw new RentalException('レンタル開始に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * レンタル返却処理
     *
     * @param RentalOrder $order 注文
     * @param \DateTime|null $returnDate 返却日
     * @param array $returnData 返却データ
     * @return void
     * @throws RentalException
     */
    public function returnRental(RentalOrder $order, \DateTime $returnDate = null, array $returnData = [])
    {
        $this->validationService->validateOrderStatus($order, [
            RentalOrder::STATUS_ACTIVE, 
            RentalOrder::STATUS_OVERDUE
        ]);

        $returnDate = $returnDate ?? new \DateTime();

        $this->entityManager->beginTransaction();

        try {
            // 延滞チェックと延滞料金計算
            $isOverdue = $returnDate > $order->getRentalEndDate();
            $overdueFee = 0;

            if ($isOverdue) {
                $overdueDays = $returnDate->diff($order->getRentalEndDate())->days;
                $overdueFee = $this->calculationService->calculateOverdueFee(
                    $order,
                    $overdueDays
                );
                
                if ($overdueFee > 0) {
                    $order->setOverdueFee($overdueFee);
                    // 延滞料金の決済記録を作成
                    $this->paymentRepository->createOverdueFee($order, $overdueFee, $overdueDays);
                }
            }

            // 早期返却割引の計算
            $earlyReturnDiscount = 0;
            if (!$isOverdue && $this->configRepository->getBoolean('early_return_discount_enabled', false)) {
                $earlyReturnDiscount = $this->calculationService->calculateEarlyReturnDiscount(
                    $order,
                    $returnDate
                );
                $order->setEarlyReturnDiscount($earlyReturnDiscount);
            }

            $order->setStatus(RentalOrder::STATUS_RETURNED);
            $order->setActualReturnDate($returnDate);
            $order->setReturnCondition($returnData['condition'] ?? '');
            $order->setReturnNotes($returnData['notes'] ?? '');

            $this->entityManager->persist($order);

            // 在庫を返却済みに移行
            $this->returnInventory($order);

            $this->entityManager->flush();

            // ログ記録
            $this->logRepository->logItemReturned(
                $order,
                $returnDate,
                $returnData['condition'] ?? '',
                $order->getCustomer()
            );

            $this->entityManager->commit();

            // 通知送信
            if ($isOverdue) {
                $this->notificationService->sendOverdueReturnNotification($order, $overdueDays);
            } else {
                $this->notificationService->sendReturnCompletedNotification($order);
            }

            $this->logger->info('レンタル返却完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'return_date' => $returnDate->format('Y-m-d H:i:s'),
                'is_overdue' => $isOverdue,
                'overdue_fee' => $overdueFee
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logRepository->logError('レンタル返却エラー', $e, [
                'order_id' => $order->getId()
            ]);
            throw new RentalException('返却処理に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * レンタル注文をキャンセル
     *
     * @param RentalOrder $order 注文
     * @param string $reason キャンセル理由
     * @return void
     * @throws RentalException
     */
    public function cancelRentalOrder(RentalOrder $order, string $reason = '')
    {
        $this->validationService->validateOrderStatus($order, [
            RentalOrder::STATUS_PENDING,
            RentalOrder::STATUS_RESERVED
        ]);

        $this->entityManager->beginTransaction();

        try {
            $order->setStatus(RentalOrder::STATUS_CANCELLED);
            $order->setCancelDate(new \DateTime());
            $order->setCancelReason($reason);
            $this->entityManager->persist($order);

            // 予約在庫を解放
            $this->releaseInventory($order);

            // 決済済みの場合は返金処理
            $this->processRefundIfPaid($order, $reason);

            $this->entityManager->flush();

            // ログ記録
            $this->logRepository->logOrderCanceled($order, $reason, $order->getCustomer());

            $this->entityManager->commit();

            // 通知送信
            $this->notificationService->sendOrderCancelledNotification($order, $reason);

            $this->logger->info('レンタル注文キャンセル完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logRepository->logError('レンタル注文キャンセルエラー', $e, [
                'order_id' => $order->getId()
            ]);
            throw new RentalException('注文のキャンセルに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * レンタル期間を延長
     *
     * @param RentalOrder $order 注文
     * @param \DateTime $newEndDate 新しい終了日
     * @return void
     * @throws RentalException
     */
    public function extendRental(RentalOrder $order, \DateTime $newEndDate)
    {
        $this->validationService->validateOrderStatus($order, [RentalOrder::STATUS_ACTIVE]);

        if ($newEndDate <= $order->getRentalEndDate()) {
            throw new RentalValidationException('延長後の終了日は現在の終了日より後である必要があります');
        }

        $this->entityManager->beginTransaction();

        try {
            // 延長期間の在庫確認
            $this->validateExtensionAvailability($order, $newEndDate);

            // 延長料金の計算
            $extensionFee = $this->calculationService->calculateExtensionFee(
                $order,
                $newEndDate
            );

            $originalEndDate = $order->getRentalEndDate();
            $order->setRentalEndDate($newEndDate);
            $order->setExtensionFee($extensionFee);
            $order->setTotalAmount(
                bcadd($order->getTotalAmount(), $extensionFee, 2)
            );

            $this->entityManager->persist($order);

            // 延長料金の決済記録を作成
            if ($extensionFee > 0) {
                $this->paymentRepository->createExtensionFee($order, $extensionFee);
            }

            $this->entityManager->flush();

            // ログ記録
            $this->logRepository->log(
                'rental_extended',
                'レンタル期間が延長されました: ' . $order->getOrderNo(),
                'info',
                [
                    'order_id' => $order->getId(),
                    'original_end_date' => $originalEndDate->format('Y-m-d'),
                    'new_end_date' => $newEndDate->format('Y-m-d'),
                    'extension_fee' => $extensionFee
                ],
                $order->getCustomer(),
                $order
            );

            $this->entityManager->commit();

            // 通知送信
            $this->notificationService->sendRentalExtendedNotification($order, $originalEndDate);

            $this->logger->info('レンタル期間延長完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'new_end_date' => $newEndDate->format('Y-m-d'),
                'extension_fee' => $extensionFee
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logRepository->logError('レンタル期間延長エラー', $e, [
                'order_id' => $order->getId()
            ]);
            throw new RentalException('期間延長に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * カート商品を検証
     *
     * @param RentalCart[] $cartItems カート商品配列
     * @throws RentalValidationException
     */
    private function validateCartItems(array $cartItems)
    {
        foreach ($cartItems as $cartItem) {
            $rentalProduct = $this->productRepository->findByProduct($cartItem->getProduct());
            
            if (!$rentalProduct) {
                throw new RentalValidationException(
                    $cartItem->getProduct()->getName() . ' はレンタル対象商品ではありません'
                );
            }

            // 商品のレンタル可能性を検証
            $this->validationService->validateProductRentability(
                $cartItem->getProduct(),
                $rentalProduct
            );

            // レンタル期間を検証
            $this->validationService->validateRentalPeriod(
                $rentalProduct,
                $cartItem->getRentalStartDate(),
                $cartItem->getRentalEndDate()
            );

            // 数量を検証
            $this->validationService->validateQuantity($cartItem->getQuantity());
        }
    }

    /**
     * 在庫を確認・仮予約
     *
     * @param RentalCart[] $cartItems カート商品配列
     * @throws RentalInventoryException
     */
    private function validateAndReserveInventory(array $cartItems)
    {
        foreach ($cartItems as $cartItem) {
            $rentalProduct = $this->productRepository->findByProduct($cartItem->getProduct());
            
            $availableStock = $this->inventoryRepository->getAvailableStock(
                $rentalProduct,
                $cartItem->getRentalStartDate(),
                $cartItem->getRentalEndDate()
            );

            if ($availableStock < $cartItem->getQuantity()) {
                throw new RentalInventoryException(
                    sprintf(
                        '%s の在庫が不足しています。利用可能数: %d個',
                        $cartItem->getProduct()->getName(),
                        $availableStock
                    )
                );
            }
        }
    }

    /**
     * 注文エンティティを作成
     *
     * @param Customer $customer 顧客
     * @param RentalCart[] $cartItems カート商品配列
     * @param array $orderData 注文データ
     * @return RentalOrder
     */
    private function createOrderEntity(Customer $customer, array $cartItems, array $orderData)
    {
        // 簡素化のため、最初のカート商品のみで注文を作成
        // 実際の実装では複数商品対応が必要
        $firstCartItem = $cartItems[0];
        $rentalProduct = $this->productRepository->findByProduct($firstCartItem->getProduct());

        $order = new RentalOrder();
        $order->setOrderNo($this->orderRepository->generateNextOrderNo());
        $order->setCustomer($customer);
        $order->setRentalProduct($rentalProduct);
        $order->setQuantity($firstCartItem->getQuantity());
        $order->setRentalStartDate($firstCartItem->getRentalStartDate());
        $order->setRentalEndDate($firstCartItem->getRentalEndDate());
        $order->setStatus(RentalOrder::STATUS_PENDING);

        // 料金計算
        $totalAmount = $this->calculationService->calculateRentalPrice(
            $rentalProduct,
            $firstCartItem->getRentalStartDate(),
            $firstCartItem->getRentalEndDate(),
            $firstCartItem->getQuantity()
        );

        $order->setTotalAmount($totalAmount);

        // 保証金設定
        if ($this->configRepository->isDepositRequired()) {
            $depositAmount = $this->calculationService->calculateDepositAmount($rentalProduct, $firstCartItem->getQuantity());
            $order->setDepositAmount($depositAmount);
        }

        // 配送情報設定
        if (isset($orderData['delivery'])) {
            $this->setDeliveryInfo($order, $orderData['delivery']);
        }

        // 備考設定
        if (isset($orderData['note'])) {
            $order->setNote($orderData['note']);
        }

        return $order;
    }

    /**
     * 在庫を予約
     *
     * @param RentalOrder $order 注文
     * @param RentalCart[] $cartItems カート商品配列
     */
    private function reserveInventory(RentalOrder $order, array $cartItems)
    {
        $rentalProduct = $order->getRentalProduct();
        $availableInventories = $this->inventoryRepository->findAvailableInventory(
            $rentalProduct,
            $order->getQuantity()
        );

        if (count($availableInventories) < $order->getQuantity()) {
            throw new RentalInventoryException('在庫の予約に失敗しました');
        }

        // 在庫を予約状態に更新
        foreach (array_slice($availableInventories, 0, $order->getQuantity()) as $inventory) {
            $inventory->setStatus(2); // 予約中（将来的には定数化）
            $inventory->setRentalOrder($order);
            $this->entityManager->persist($inventory);
        }
    }

    /**
     * 在庫をアクティブ化（レンタル開始時）
     *
     * @param RentalOrder $order 注文
     */
    private function activateInventory(RentalOrder $order)
    {
        $inventories = $this->inventoryRepository->findByRentalOrder($order);
        $this->inventoryRepository->setRented($inventories, $order);
    }

    /**
     * 在庫を返却済みに更新
     *
     * @param RentalOrder $order 注文
     */
    private function returnInventory(RentalOrder $order)
    {
        $inventories = $this->inventoryRepository->findByRentalOrder($order);
        $this->inventoryRepository->setReturned($inventories);
    }

    /**
     * 在庫の予約を解放
     *
     * @param RentalOrder $order 注文
     */
    private function releaseInventory(RentalOrder $order)
    {
        $inventories = $this->inventoryRepository->findByRentalOrder($order);
        foreach ($inventories as $inventory) {
            $inventory->setStatus(1); // 利用可能
            $inventory->setRentalOrder(null);
            $this->entityManager->persist($inventory);
        }
    }

    /**
     * 決済済みの場合は返金処理
     *
     * @param RentalOrder $order 注文
     * @param string $reason 返金理由
     */
    private function processRefundIfPaid(RentalOrder $order, string $reason)
    {
        $payments = $this->paymentRepository->findByRentalOrder($order);
        
        foreach ($payments as $payment) {
            if ($payment->getPaymentStatus() === 1 && $payment->getPaymentAmount() > 0) { // 成功した決済
                $this->paymentRepository->createRefund($payment, $payment->getPaymentAmount(), $reason);
            }
        }
    }

    /**
     * 延長可能性を検証
     *
     * @param RentalOrder $order 注文
     * @param \DateTime $newEndDate 新しい終了日
     * @throws RentalValidationException
     */
    private function validateExtensionAvailability(RentalOrder $order, \DateTime $newEndDate)
    {
        $availableStock = $this->inventoryRepository->getAvailableStock(
            $order->getRentalProduct(),
            $order->getRentalEndDate(),
            $newEndDate
        );

        if ($availableStock < $order->getQuantity()) {
            throw new RentalValidationException('延長期間中の在庫が不足しているため、延長できません');
        }
    }

    /**
     * 配送情報を設定
     *
     * @param RentalOrder $order 注文
     * @param array $deliveryData 配送データ
     */
    private function setDeliveryInfo(RentalOrder $order, array $deliveryData)
    {
        $order->setDeliveryName01($deliveryData['name01'] ?? '');
        $order->setDeliveryName02($deliveryData['name02'] ?? '');
        $order->setDeliveryKana01($deliveryData['kana01'] ?? '');
        $order->setDeliveryKana02($deliveryData['kana02'] ?? '');
        $order->setDeliveryCompanyName($deliveryData['company_name'] ?? '');
        $order->setDeliveryPostalCode($deliveryData['postal_code'] ?? '');
        $order->setDeliveryAddr01($deliveryData['addr01'] ?? '');
        $order->setDeliveryAddr02($deliveryData['addr02'] ?? '');
        $order->setDeliveryPhone($deliveryData['phone'] ?? '');
    }
}