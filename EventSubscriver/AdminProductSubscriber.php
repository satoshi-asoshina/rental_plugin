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

namespace Plugin\Rental\EventSubscriber;

use Eccube\Entity\Product;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Plugin\Rental\Entity\RentalProduct;
use Plugin\Rental\Repository\RentalProductRepository;
use Plugin\Rental\Repository\RentalInventoryRepository;
use Plugin\Rental\Service\RentalValidationService;
use Plugin\Rental\Exception\RentalValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * 商品管理連携イベントサブスクライバー
 */
class AdminProductSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RentalProductRepository
     */
    private $rentalProductRepository;

    /**
     * @var RentalInventoryRepository
     */
    private $inventoryRepository;

    /**
     * @var RentalValidationService
     */
    private $validationService;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * コンストラクタ
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RentalProductRepository $rentalProductRepository,
        RentalInventoryRepository $inventoryRepository,
        RentalValidationService $validationService,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->rentalProductRepository = $rentalProductRepository;
        $this->inventoryRepository = $inventoryRepository;
        $this->validationService = $validationService;
        $this->requestStack = $requestStack;
    }

    /**
     * 購読するイベントを返す
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::ADMIN_PRODUCT_EDIT_INITIALIZE => 'onAdminProductEditInitialize',
            EccubeEvents::ADMIN_PRODUCT_EDIT_COMPLETE => 'onAdminProductEditComplete',
            EccubeEvents::ADMIN_PRODUCT_DELETE_COMPLETE => 'onAdminProductDeleteComplete',
            EccubeEvents::ADMIN_PRODUCT_COPY_COMPLETE => 'onAdminProductCopyComplete',
        ];
    }

    /**
     * 商品編集画面初期化時の処理
     *
     * @param EventArgs $event
     */
    public function onAdminProductEditInitialize(EventArgs $event)
    {
        $form = $event->getForm();
        $product = $event->getProduct();

        // 既存商品の場合はレンタル設定を取得
        if ($product && $product->getId()) {
            $rentalProduct = $this->rentalProductRepository->findByProduct($product);
            
            if ($rentalProduct) {
                // フォームにレンタル設定データを設定
                $form->get('rental_setting')->setData($rentalProduct);
            } else {
                // レンタル設定がない場合は新規作成（無効状態）
                $newRentalProduct = new RentalProduct();
                $newRentalProduct->setProduct($product);
                $newRentalProduct->setIsRentalEnabled(false);
                $form->get('rental_setting')->setData($newRentalProduct);
            }
        }
    }

    /**
     * 商品編集完了時の処理
     *
     * @param EventArgs $event
     */
    public function onAdminProductEditComplete(EventArgs $event)
    {
        $form = $event->getForm();
        $product = $event->getProduct();

        // レンタル設定フォームが存在する場合
        if ($form->has('rental_setting') && $form->get('rental_setting')->isSubmitted()) {
            $rentalProductData = $form->get('rental_setting')->getData();
            
            if ($rentalProductData instanceof RentalProduct) {
                try {
                    $this->saveRentalProductSetting($product, $rentalProductData);
                } catch (RentalValidationException $e) {
                    // バリデーションエラーをフォームに追加
                    $form->get('rental_setting')->addError(new \Symfony\Component\Form\FormError($e->getMessage()));
                    return;
                }
            }
        }
    }

    /**
     * 商品削除完了時の処理
     *
     * @param EventArgs $event
     */
    public function onAdminProductDeleteComplete(EventArgs $event)
    {
        $productId = $event->getProductId();
        
        // レンタル設定も削除
        $rentalProduct = $this->rentalProductRepository->findByProductId($productId);
        if ($rentalProduct) {
            // 関連する在庫情報も削除
            $this->inventoryRepository->removeInventory($rentalProduct->getProduct());
            
            // レンタル商品設定を削除
            $this->entityManager->remove($rentalProduct);
            $this->entityManager->flush();
        }
    }

    /**
     * 商品複製完了時の処理
     *
     * @param EventArgs $event
     */
    public function onAdminProductCopyComplete(EventArgs $event)
    {
        $originalProduct = $event->getOriginalProduct();
        $newProduct = $event->getProduct();

        // 元商品のレンタル設定を複製
        $originalRentalProduct = $this->rentalProductRepository->findByProduct($originalProduct);
        
        if ($originalRentalProduct) {
            $newRentalProduct = new RentalProduct();
            $newRentalProduct->setProduct($newProduct);
            
            // 設定値をコピー
            $newRentalProduct->setIsRentalEnabled($originalRentalProduct->getIsRentalEnabled());
            $newRentalProduct->setDailyPrice($originalRentalProduct->getDailyPrice());
            $newRentalProduct->setWeeklyPrice($originalRentalProduct->getWeeklyPrice());
            $newRentalProduct->setMonthlyPrice($originalRentalProduct->getMonthlyPrice());
            $newRentalProduct->setDepositAmount($originalRentalProduct->getDepositAmount());
            $newRentalProduct->setMinRentalDays($originalRentalProduct->getMinRentalDays());
            $newRentalProduct->setMaxRentalDays($originalRentalProduct->getMaxRentalDays());
            $newRentalProduct->setPreparationDays($originalRentalProduct->getPreparationDays());
            $newRentalProduct->setAutoApproval($originalRentalProduct->getAutoApproval());
            $newRentalProduct->setRentalNote($originalRentalProduct->getRentalNote());
            $newRentalProduct->setSpecialInstructions($originalRentalProduct->getSpecialInstructions());
            
            // 拡張フィールドもコピー
            $newRentalProduct->setInsuranceFee($originalRentalProduct->getInsuranceFee());
            $newRentalProduct->setEarlyReturnDiscount($originalRentalProduct->getEarlyReturnDiscount());
            $newRentalProduct->setExtensionFeeRate($originalRentalProduct->getExtensionFeeRate());
            $newRentalProduct->setReplacementFee($originalRentalProduct->getReplacementFee());
            $newRentalProduct->setMaintenanceCycle($originalRentalProduct->getMaintenanceCycle());
            $newRentalProduct->setCategoryOptions($originalRentalProduct->getCategoryOptions());
            $newRentalProduct->setSizeOptions($originalRentalProduct->getSizeOptions());
            $newRentalProduct->setColorOptions($originalRentalProduct->getColorOptions());

            $this->entityManager->persist($newRentalProduct);
            $this->entityManager->flush();

            // 在庫情報も初期化（在庫数は0でリセット）
            if ($newRentalProduct->getIsRentalEnabled()) {
                $this->inventoryRepository->findOrCreate($newProduct);
            }
        }
    }

    /**
     * レンタル商品設定を保存
     *
     * @param Product $product
     * @param RentalProduct $rentalProductData
     * @throws RentalValidationException
     */
    private function saveRentalProductSetting(Product $product, RentalProduct $rentalProductData)
    {
        // 既存のレンタル設定を取得または新規作成
        $rentalProduct = $this->rentalProductRepository->findByProduct($product);
        if (!$rentalProduct) {
            $rentalProduct = new RentalProduct();
            $rentalProduct->setProduct($product);
        }

        // レンタル有効化のチェック
        $isRentalEnabled = $rentalProductData->getIsRentalEnabled();
        
        if ($isRentalEnabled) {
            // レンタル有効化の場合はバリデーション実行
            $this->validateRentalProductData($rentalProductData);
            
            // 料金設定が存在するかチェック
            if (!$rentalProductData->getDailyPrice() && !$rentalProductData->getWeeklyPrice() && !$rentalProductData->getMonthlyPrice()) {
                throw new RentalValidationException('レンタル料金を最低1つは設定してください');
            }
        }

        // データを設定
        $rentalProduct->setIsRentalEnabled($isRentalEnabled);
        
        if ($isRentalEnabled) {
            $rentalProduct->setDailyPrice($rentalProductData->getDailyPrice());
            $rentalProduct->setWeeklyPrice($rentalProductData->getWeeklyPrice());
            $rentalProduct->setMonthlyPrice($rentalProductData->getMonthlyPrice());
            $rentalProduct->setDepositAmount($rentalProductData->getDepositAmount());
            $rentalProduct->setMinRentalDays($rentalProductData->getMinRentalDays() ?: 1);
            $rentalProduct->setMaxRentalDays($rentalProductData->getMaxRentalDays());
            $rentalProduct->setPreparationDays($rentalProductData->getPreparationDays() ?: 0);
            $rentalProduct->setAutoApproval($rentalProductData->getAutoApproval() ?: false);
            $rentalProduct->setRentalNote($rentalProductData->getRentalNote());
            $rentalProduct->setSpecialInstructions($rentalProductData->getSpecialInstructions());
            
            // 拡張フィールド
            $rentalProduct->setInsuranceFee($rentalProductData->getInsuranceFee());
            $rentalProduct->setEarlyReturnDiscount($rentalProductData->getEarlyReturnDiscount());
            $rentalProduct->setExtensionFeeRate($rentalProductData->getExtensionFeeRate());
            $rentalProduct->setReplacementFee($rentalProductData->getReplacementFee());
            $rentalProduct->setMaintenanceCycle($rentalProductData->getMaintenanceCycle());
            $rentalProduct->setCategoryOptions($rentalProductData->getCategoryOptions());
            $rentalProduct->setSizeOptions($rentalProductData->getSizeOptions());
            $rentalProduct->setColorOptions($rentalProductData->getColorOptions());
        }

        // データベースに保存
        $this->entityManager->persist($rentalProduct);
        $this->entityManager->flush();

        // 在庫情報の初期化
        if ($isRentalEnabled) {
            $this->inventoryRepository->findOrCreate($product);
        }
    }

    /**
     * レンタル商品データのバリデーション
     *
     * @param RentalProduct $rentalProductData
     * @throws RentalValidationException
     */
    private function validateRentalProductData(RentalProduct $rentalProductData)
    {
        // 最小・最大レンタル日数のチェック
        $minDays = $rentalProductData->getMinRentalDays() ?: 1;
        $maxDays = $rentalProductData->getMaxRentalDays();
        
        if ($maxDays && $maxDays < $minDays) {
            throw new RentalValidationException('最大レンタル日数は最小レンタル日数以上である必要があります');
        }

        // 料金の妥当性チェック
        $dailyPrice = $rentalProductData->getDailyPrice();
        $weeklyPrice = $rentalProductData->getWeeklyPrice();
        $monthlyPrice = $rentalProductData->getMonthlyPrice();

        if ($dailyPrice) {
            $this->validationService->validateAmount($dailyPrice, '日額料金');
        }
        
        if ($weeklyPrice) {
            $this->validationService->validateAmount($weeklyPrice, '週額料金');
        }
        
        if ($monthlyPrice) {
            $this->validationService->validateAmount($monthlyPrice, '月額料金');
        }

        if ($rentalProductData->getDepositAmount()) {
            $this->validationService->validateAmount($rentalProductData->getDepositAmount(), '保証金額');
        }

        // 割引率のチェック
        $earlyReturnDiscount = $rentalProductData->getEarlyReturnDiscount();
        if ($earlyReturnDiscount && ($earlyReturnDiscount < 0 || $earlyReturnDiscount > 1)) {
            throw new RentalValidationException('早期返却割引率は0から1の間で設定してください');
        }

        $extensionFeeRate = $rentalProductData->getExtensionFeeRate();
        if ($extensionFeeRate && ($extensionFeeRate < 0 || $extensionFeeRate > 10)) {
            throw new RentalValidationException('延長料金率は0から10の間で設定してください');
        }

        // 準備日数のチェック
        $preparationDays = $rentalProductData->getPreparationDays();
        if ($preparationDays && ($preparationDays < 0 || $preparationDays > 30)) {
            throw new RentalValidationException('準備日数は0から30日の間で設定してください');
        }

        // メンテナンス周期のチェック
        $maintenanceCycle = $rentalProductData->getMaintenanceCycle();
        if ($maintenanceCycle && ($maintenanceCycle < 1 || $maintenanceCycle > 365)) {
            throw new RentalValidationException('メンテナンス周期は1から365日の間で設定してください');
        }

        // JSONオプションの妥当性チェック
        $this->validateJsonOption($rentalProductData->getCategoryOptions(), 'カテゴリオプション');
        $this->validateJsonOption($rentalProductData->getSizeOptions(), 'サイズオプション');
        $this->validateJsonOption($rentalProductData->getColorOptions(), 'カラーオプション');
    }

    /**
     * JSONオプションの妥当性をチェック
     *
     * @param string|null $jsonString
     * @param string $fieldName
     * @throws RentalValidationException
     */
    private function validateJsonOption($jsonString, $fieldName)
    {
        if (empty($jsonString)) {
            return;
        }

        $decoded = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RentalValidationException($fieldName . 'のJSON形式が正しくありません');
        }
    }
}