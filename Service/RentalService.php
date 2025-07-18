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
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Repository\RentalOrderRepository;
use Plugin\Rental\Repository\RentalProductRepository;
use Plugin\Rental\Repository\RentalCartRepository;
use Plugin\Rental\Repository\RentalInventoryRepository;
use Plugin\Rental\Exception\RentalException;
use Plugin\Rental\Exception\RentalValidationException;
use Plugin\Rental\Exception\RentalInventoryException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * レンタル管理メインビジネスロジック Service
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
     * @var RentalCalculationService
     */
    private $calculationService;

    /**
     * @var RentalValidationService
     */
    private $validationService;

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
        RentalCalculationService $calculationService,
        RentalValidationService $validationService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->configRepository = $configRepository;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->cartRepository = $cartRepository;
        $this->inventoryRepository = $inventoryRepository;
        $this->calculationService = $calculationService;
        $this->validationService = $validationService;
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
            // カート商品を取得
            $cartItems = $this->cartRepository->findBySessionOrCustomer(
                $orderData['session_id'] ?? '',
                $customer
            );

            if (empty($cartItems)) {
                throw new RentalValidationException('カートが空です');
            }

            // 在庫チェック
            $this->validateInventoryAvailability($cartItems);

            // 注文を作成
            $order = new RentalOrder();
            $order->setCustomer($customer);
            $order->setOrderNo($this->generateOrderNumber());
            $order->setStatus(RentalOrder::STATUS_PENDING);

            // カート商品を注文に変換
            $totalAmount = '0';
            foreach ($cartItems as $cartItem) {
                $rentalProduct = $this->productRepository->findByProduct($cartItem->getProduct());
                if (!$rentalProduct) {
                    throw new RentalValidationException('レンタル対象外の商品が含まれています');
                }

                // 各商品ごとに個別注文を作成（簡素化のため、ここでは最初の商品のみ）
                $order->setRentalProduct($rentalProduct);
                $order->setQuantity($cartItem->getQuantity());
                $order->setRentalStartDate($cartItem->getRentalStartDate());
                $order->setRentalEndDate($cartItem->getRentalEndDate());
                
                $calculatedPrice = $this->calculationService->calculateRentalPrice(
                    $rentalProduct,
                    $cartItem->getRentalStartDate(),
                    $cartItem->getRentalEndDate(),
                    $cartItem->getQuantity()
                );
                
                $totalAmount = bcadd($totalAmount, $calculatedPrice, 2);
                break; // 簡素化のため最初の商品のみ処理
            }

            $order->setTotalAmount($totalAmount);

            // 保証金設定
            if ($this->configRepository->isDepositRequired()) {
                $depositAmount = $this->calculationService->calculateDepositAmount($order);
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

            // 注文を保存
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            // 在庫を予約
            $this->reserveInventory($order);

            // カートをクリア
            $this->cartRepository->clearBySessionOrCustomer(
                $orderData['session_id'] ?? '',
                $customer
            );

            $this->entityManager->commit();

            $this->logger->info('レンタル注文作成完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'customer_id' => $customer->getId()
            ]);

            return $order;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('レンタル注文作成エラー', [
                'error' => $e->getMessage(),
                'customer_id' => $customer->getId()
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
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $this->entityManager->commit();

            $this->logger->info('レンタル注文承認完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo()
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
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
            $this->entityManager->persist($order);

            // 在庫を予約からレンタル中に移行
            $this->activateInventory($order);

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('レンタル開始完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo()
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
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
        $this->validationService->validateOrderStatus($order, [RentalOrder::STATUS_ACTIVE, RentalOrder::STATUS_OVERDUE]);

        $returnDate = $returnDate ?: new \DateTime();
        
        $this->entityManager->beginTransaction();

        try {
            $order->setActualReturnDate($returnDate);

            // 延滞チェック
            if ($returnDate > $order->getRentalEndDate()) {
                $order->setStatus(RentalOrder::STATUS_OVERDUE);
                
                // 延滞料金計算
                $overdueFee = $this->calculationService->calculateOverdueFee($order, $returnDate);
                if ($overdueFee > 0) {
                    $order->setOverdueFee($overdueFee);
                }
            } else {
                $order->setStatus(RentalOrder::STATUS_RETURNED);
            }

            // 返却時の商品状態メモ
            if (isset($returnData['condition_notes'])) {
                $order->setProductConditionNotes($returnData['condition_notes']);
            }

            // 損害料金
            if (isset($returnData['damage_fee']) && $returnData['damage_fee'] > 0) {
                $order->setDamageFee($returnData['damage_fee']);
                $order->setStatus(RentalOrder::STATUS_DAMAGED);
            }

            // クリーニング料金
            if (isset($returnData['cleaning_fee']) && $returnData['cleaning_fee'] > 0) {
                $order->setCleaningFee($returnData['cleaning_fee']);
            }

            $this->entityManager->persist($order);

            // 在庫を解放
            $this->releaseInventory($order);

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('レンタル返却処理完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'return_date' => $returnDate->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
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
            $order->setAdminMemo($reason);
            $this->entityManager->persist($order);

            // 在庫予約を解放
            $this->cancelInventoryReservation($order);

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('レンタル注文キャンセル完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw new RentalException('キャンセル処理に失敗しました: ' . $e->getMessage());
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
            throw new RentalValidationException('新しい終了日は現在の終了日より後である必要があります');
        }

        $this->entityManager->beginTransaction();

        try {
            $extensionDays = $order->getRentalEndDate()->diff($newEndDate)->days;
            $extensionFee = $this->calculationService->calculateExtensionFee($order, $extensionDays);

            $order->setRentalEndDate($newEndDate);
            $order->setExtensionFee($extensionFee);
            $order->setTotalAmount(bcadd($order->getTotalAmount(), $extensionFee, 2));

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('レンタル期間延長完了', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'new_end_date' => $newEndDate->format('Y-m-d'),
                'extension_fee' => $extensionFee
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw new RentalException('期間延長に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 在庫可用性をチェック
     *
     * @param array $cartItems カート商品配列
     * @throws RentalInventoryException
     */
    private function validateInventoryAvailability(array $cartItems)
    {
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->getProduct();
            $quantity = $cartItem->getQuantity();

            if (!$this->inventoryRepository->isStockSufficient($product, $quantity)) {
                throw new RentalInventoryException($product->getName() . 'の在庫が不足しています');
            }

            // 期間重複チェック
            $conflictingOrders = $this->orderRepository->findConflictingOrders(
                $this->productRepository->findByProduct($product),
                $cartItem->getRentalStartDate(),
                $cartItem->getRentalEndDate()
            );

            $reservedQuantity = 0;
            foreach ($conflictingOrders as $conflictOrder) {
                $reservedQuantity += $conflictOrder->getQuantity();
            }

            $availableQuantity = $this->inventoryRepository->getActualAvailableQuantity($product) - $reservedQuantity;
            if ($availableQuantity < $quantity) {
                throw new RentalInventoryException($product->getName() . 'の指定期間での在庫が不足しています');
            }
        }
    }

    /**
     * 在庫を予約
     *
     * @param RentalOrder $order 注文
     */
    private function reserveInventory(RentalOrder $order)
    {
        $product = $order->getRentalProduct()->getProduct();
        $this->inventoryRepository->reserve($product, $order->getQuantity());
    }

    /**
     * 在庫をアクティブ化
     *
     * @param RentalOrder $order 注文
     */
    private function activateInventory(RentalOrder $order)
    {
        $product = $order->getRentalProduct()->getProduct();
        $this->inventoryRepository->activateRental($product, $order->getQuantity());
    }

    /**
     * 在庫を解放
     *
     * @param RentalOrder $order 注文
     */
    private function releaseInventory(RentalOrder $order)
    {
        $product = $order->getRentalProduct()->getProduct();
        $this->inventoryRepository->returnRental($product, $order->getQuantity());
    }

    /**
     * 在庫予約をキャンセル
     *
     * @param RentalOrder $order 注文
     */
    private function cancelInventoryReservation(RentalOrder $order)
    {
        $product = $order->getRentalProduct()->getProduct();
        $this->inventoryRepository->cancelReservation($product, $order->getQuantity());
    }

    /**
     * 注文番号を生成
     *
     * @return string
     */
    private function generateOrderNumber()
    {
        return 'R' . date('Ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 配送情報を設定
     *
     * @param RentalOrder $order 注文
     * @param array $deliveryData 配送データ
     */
    private function setDeliveryInfo(RentalOrder $order, array $deliveryData)
    {
        if (isset($deliveryData['address'])) {
            $order->setDeliveryAddress($deliveryData['address']);
        }
        
        if (isset($deliveryData['phone'])) {
            $order->setDeliveryPhone($deliveryData['phone']);
        }
        
        if (isset($deliveryData['date'])) {
            $order->setDeliveryDate($deliveryData['date']);
        }
        
        if (isset($deliveryData['time'])) {
            $order->setDeliveryTime($deliveryData['time']);
        }
    }

    /**
     * 延滞注文を取得
     *
     * @return RentalOrder[]
     */
    public function getOverdueOrders()
    {
        return $this->orderRepository->findOverdueOrders();
    }

    /**
     * 返却期限が近い注文を取得
     *
     * @param int $days 日数
     * @return RentalOrder[]
     */
    public function getUpcomingReturns($days = null)
    {
        $days = $days ?: $this->configRepository->getReminderDays();
        return $this->orderRepository->findUpcomingReturns($days);
    }

    /**
     * 顧客の注文履歴を取得
     *
     * @param Customer $customer 顧客
     * @param int|null $limit 取得件数
     * @return RentalOrder[]
     */
    public function getCustomerOrderHistory(Customer $customer, $limit = null)
    {
        return $this->orderRepository->findByCustomer($customer, ['create_date' => 'DESC'], $limit);
    }

    /**
     * レンタル注文統計を取得
     *
     * @return array
     */
    public function getOrderStatistics()
    {
        return $this->orderRepository->getStatistics();
    }

    /**
     * 月次レポートを取得
     *
     * @param int $year 年
     * @param int|null $month 月
     * @return array
     */
    public function getMonthlyReport($year, $month = null)
    {
        return $this->orderRepository->getMonthlyReport($year, $month);
    }
}