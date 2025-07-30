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
use Eccube\Entity\Product;
use Plugin\Rental\Entity\RentalCart;
use Plugin\Rental\Entity\RentalProduct;
use Plugin\Rental\Repository\RentalCartRepository;
use Plugin\Rental\Repository\RentalProductRepository;
use Plugin\Rental\Service\RentalCalculationService;
use Plugin\Rental\Service\RentalInventoryService;
use Plugin\Rental\Service\RentalCartService;
use Plugin\Rental\Form\Type\RentalCartType;
use Plugin\Rental\Exception\RentalException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

/**
 * レンタルカート機能コントローラー
 * 
 * @Route("/rental/cart")
 */
class RentalCartController extends AbstractController
{
    /**
     * @var RentalCartRepository
     */
    private $cartRepository;

    /**
     * @var RentalProductRepository
     */
    private $rentalProductRepository;

    /**
     * @var RentalCalculationService
     */
    private $calculationService;

    /**
     * @var RentalInventoryService
     */
    private $inventoryService;

    /**
     * @var RentalCartService
     */
    private $cartService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * コンストラクタ
     */
    public function __construct(
        RentalCartRepository $cartRepository,
        RentalProductRepository $rentalProductRepository,
        RentalCalculationService $calculationService,
        RentalInventoryService $inventoryService,
        RentalCartService $cartService,
        EntityManagerInterface $entityManager
    ) {
        $this->cartRepository = $cartRepository;
        $this->rentalProductRepository = $rentalProductRepository;
        $this->calculationService = $calculationService;
        $this->inventoryService = $inventoryService;
        $this->cartService = $cartService;
        $this->entityManager = $entityManager;
    }

    /**
     * カート追加
     *
     * @Route("/add", name="rental_cart_add", methods={"POST"})
     */
    public function add(Request $request): JsonResponse
    {
        try {
            $productId = $request->request->get('product_id');
            $startDate = new \DateTime($request->request->get('start_date'));
            $endDate = new \DateTime($request->request->get('end_date'));
            $quantity = (int) $request->request->get('quantity', 1);
            $options = $request->request->get('options', []);

            $product = $this->entityManager->getRepository(Product::class)->find($productId);
            $rentalProduct = $this->rentalProductRepository->findOneBy(['Product' => $product]);

            if (!$rentalProduct || !$rentalProduct->getIsRentalEnabled()) {
                throw new RentalException('この商品はレンタルできません。');
            }

            // 在庫確認
            $availability = $this->inventoryService->checkAvailability(
                $rentalProduct,
                $startDate,
                $endDate,
                $quantity
            );

            if (!$availability['available']) {
                throw new RentalException($availability['message']);
            }

            // カートに追加
            $cartItem = $this->cartService->addToCart([
                'rental_product' => $rentalProduct,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'quantity' => $quantity,
                'options' => $options,
                'customer' => $this->getUser(),
                'session_id' => $request->getSession()->getId(),
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'カートに追加しました',
                'cart_item_id' => $cartItem->getId(),
                'cart_count' => $this->cartService->getCartItemCount(),
                'cart_total' => $this->cartService->getCartTotal(),
            ]);

        } catch (RentalException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'カートへの追加に失敗しました。',
            ], 500);
        }
    }

    /**
     * カート表示
     *
     * @Route("", name="rental_cart", methods={"GET", "POST"})
     * @Template("@Rental/default/cart/rental_cart.twig")
     */
    public function index(Request $request)
    {
        $cartItems = $this->cartService->getCartItems();
        
        if (empty($cartItems)) {
            return [
                'cart_items' => [],
                'cart_summary' => null,
                'conflicts' => [],
            ];
        }

        // カート内容の検証
        $conflicts = $this->cartService->validateCartItems($cartItems);
        
        // 料金計算
        $cartSummary = $this->cartService->calculateCartTotal($cartItems);

        // フォーム処理（数量変更など）
        $forms = [];
        foreach ($cartItems as $item) {
            $form = $this->createForm(RentalCartType::class, $item);
            $forms[$item->getId()] = $form;
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $itemId = $request->request->get('item_id');

            switch ($action) {
                case 'update_quantity':
                    return $this->updateQuantity($request, $itemId);
                case 'update_dates':
                    return $this->updateDates($request, $itemId);
                case 'remove':
                    return $this->removeItem($request, $itemId);
                case 'clear':
                    return $this->clearCart($request);
            }
        }

        return [
            'cart_items' => $cartItems,
            'cart_summary' => $cartSummary,
            'conflicts' => $conflicts,
            'forms' => array_map(function($form) { return $form->createView(); }, $forms),
        ];
    }

    /**
     * カートアイテム更新
     *
     * @Route("/update/{id}", name="rental_cart_update", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function update(Request $request, RentalCart $cartItem): JsonResponse
    {
        try {
            // 所有者確認
            if (!$this->cartService->isOwner($cartItem)) {
                throw new RentalException('不正なアクセスです。');
            }

            $updateData = [];
            
            // 数量更新
            if ($request->request->has('quantity')) {
                $newQuantity = (int) $request->request->get('quantity');
                if ($newQuantity < 1) {
                    throw new RentalException('数量は1以上で入力してください。');
                }
                $updateData['quantity'] = $newQuantity;
            }

            // 日付更新
            if ($request->request->has('start_date') && $request->request->has('end_date')) {
                $updateData['start_date'] = new \DateTime($request->request->get('start_date'));
                $updateData['end_date'] = new \DateTime($request->request->get('end_date'));
            }

            // オプション更新
            if ($request->request->has('options')) {
                $updateData['options'] = $request->request->get('options', []);
            }

            $updatedItem = $this->cartService->updateCartItem($cartItem, $updateData);

            return new JsonResponse([
                'success' => true,
                'message' => 'カートを更新しました',
                'item' => [
                    'id' => $updatedItem->getId(),
                    'quantity' => $updatedItem->getQuantity(),
                    'total_amount' => $updatedItem->getTotalAmount(),
                ],
                'cart_total' => $this->cartService->getCartTotal(),
            ]);

        } catch (RentalException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'カートの更新に失敗しました。',
            ], 500);
        }
    }

    /**
     * カートアイテム削除
     *
     * @Route("/remove/{id}", name="rental_cart_remove", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function remove(Request $request, RentalCart $cartItem): JsonResponse
    {
        try {
            // 所有者確認
            if (!$this->cartService->isOwner($cartItem)) {
                throw new RentalException('不正なアクセスです。');
            }

            $this->cartService->removeFromCart($cartItem);

            return new JsonResponse([
                'success' => true,
                'message' => 'カートから削除しました',
                'cart_count' => $this->cartService->getCartItemCount(),
                'cart_total' => $this->cartService->getCartTotal(),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'カートからの削除に失敗しました。',
            ], 500);
        }
    }

    /**
     * カート全削除
     *
     * @Route("/clear", name="rental_cart_clear", methods={"POST"})
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $this->cartService->clearCart();

            return new JsonResponse([
                'success' => true,
                'message' => 'カートを空にしました',
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'カートのクリアに失敗しました。',
            ], 500);
        }
    }

    /**
     * カート内容確認（Ajax）
     *
     * @Route("/check", name="rental_cart_check", methods={"GET"})
     */
    public function check(Request $request): JsonResponse
    {
        try {
            $cartItems = $this->cartService->getCartItems();
            $conflicts = $this->cartService->validateCartItems($cartItems);
            $cartSummary = $this->cartService->calculateCartTotal($cartItems);

            $items = [];
            foreach ($cartItems as $item) {
                $items[] = [
                    'id' => $item->getId(),
                    'product_name' => $item->getProduct()->getName(),
                    'start_date' => $item->getRentalStartDate()->format('Y-m-d'),
                    'end_date' => $item->getRentalEndDate()->format('Y-m-d'),
                    'quantity' => $item->getQuantity(),
                    'daily_rate' => $item->getDailyRate(),
                    'total_amount' => $item->getTotalAmount(),
                ];
            }

            return new JsonResponse([
                'items' => $items,
                'summary' => $cartSummary,
                'conflicts' => $conflicts,
                'count' => count($cartItems),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'カート情報の取得に失敗しました。',
            ], 500);
        }
    }

    /**
     * 在庫競合解決
     *
     * @Route("/resolve-conflict", name="rental_cart_resolve_conflict", methods={"POST"})
     */
    public function resolveConflict(Request $request): JsonResponse
    {
        try {
            $itemId = $request->request->get('item_id');
            $resolution = $request->request->get('resolution'); // 'remove', 'suggest_dates', 'reduce_quantity'
            
            $cartItem = $this->cartRepository->find($itemId);
            
            if (!$cartItem || !$this->cartService->isOwner($cartItem)) {
                throw new RentalException('不正なアクセスです。');
            }

            switch ($resolution) {
                case 'remove':
                    $this->cartService->removeFromCart($cartItem);
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'カートから削除しました',
                        'action' => 'removed'
                    ]);

                case 'suggest_dates':
                    $suggestions = $this->inventoryService->getSuggestedDates(
                        $cartItem->getRentalProduct(),
                        $cartItem->getRentalStartDate(),
                        $cartItem->getRentalEndDate(),
                        $cartItem->getQuantity()
                    );
                    
                    return new JsonResponse([
                        'success' => true,
                        'suggestions' => $suggestions,
                        'action' => 'suggestions_provided'
                    ]);

                case 'reduce_quantity':
                    $availableQuantity = $this->inventoryService->getAvailableQuantity(
                        $cartItem->getRentalProduct(),
                        $cartItem->getRentalStartDate(),
                        $cartItem->getRentalEndDate()
                    );
                    
                    if ($availableQuantity > 0) {
                        $this->cartService->updateCartItem($cartItem, [
                            'quantity' => $availableQuantity
                        ]);
                        
                        return new JsonResponse([
                            'success' => true,
                            'message' => "数量を{$availableQuantity}個に変更しました",
                            'action' => 'quantity_reduced',
                            'new_quantity' => $availableQuantity
                        ]);
                    } else {
                        throw new RentalException('利用可能な在庫がありません。');
                    }

                default:
                    throw new RentalException('無効な解決方法です。');
            }

        } catch (RentalException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => '競合解決に失敗しました。',
            ], 500);
        }
    }

    /**
     * カート同期（ログイン時の処理）
     *
     * @Route("/sync", name="rental_cart_sync", methods={"POST"})
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            if (!$this->getUser()) {
                throw new RentalException('ログインが必要です。');
            }

            $result = $this->cartService->syncCart($this->getUser(), $request->getSession()->getId());

            return new JsonResponse([
                'success' => true,
                'message' => 'カートを同期しました',
                'merged_items' => $result['merged_items'],
                'conflicts' => $result['conflicts'],
                'cart_count' => $this->cartService->getCartItemCount(),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'カートの同期に失敗しました。',
            ], 500);
        }
    }

    /**
     * 数量更新処理
     *
     * @param Request $request
     * @param int $itemId
     * @return Response
     */
    private function updateQuantity(Request $request, int $itemId): Response
    {
        try {
            $cartItem = $this->cartRepository->find($itemId);
            $newQuantity = (int) $request->request->get('quantity');

            if (!$cartItem || !$this->cartService->isOwner($cartItem)) {
                $this->addError('不正なアクセスです。');
                return $this->redirectToRoute('rental_cart');
            }

            $this->cartService->updateCartItem($cartItem, ['quantity' => $newQuantity]);
            $this->addSuccess('数量を更新しました。');

        } catch (RentalException $e) {
            $this->addError($e->getMessage());
        }

        return $this->redirectToRoute('rental_cart');
    }

    /**
     * 日付更新処理
     *
     * @param Request $request
     * @param int $itemId
     * @return Response
     */
    private function updateDates(Request $request, int $itemId): Response
    {
        try {
            $cartItem = $this->cartRepository->find($itemId);
            $startDate = new \DateTime($request->request->get('start_date'));
            $endDate = new \DateTime($request->request->get('end_date'));

            if (!$cartItem || !$this->cartService->isOwner($cartItem)) {
                $this->addError('不正なアクセスです。');
                return $this->redirectToRoute('rental_cart');
            }

            $this->cartService->updateCartItem($cartItem, [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            $this->addSuccess('レンタル期間を更新しました。');

        } catch (RentalException $e) {
            $this->addError($e->getMessage());
        }

        return $this->redirectToRoute('rental_cart');
    }

    /**
     * アイテム削除処理
     *
     * @param Request $request
     * @param int $itemId
     * @return Response
     */
    private function removeItem(Request $request, int $itemId): Response
    {
        try {
            $cartItem = $this->cartRepository->find($itemId);

            if (!$cartItem || !$this->cartService->isOwner($cartItem)) {
                $this->addError('不正なアクセスです。');
                return $this->redirectToRoute('rental_cart');
            }

            $this->cartService->removeFromCart($cartItem);
            $this->addSuccess('カートから削除しました。');

        } catch (\Exception $e) {
            $this->addError('削除に失敗しました。');
        }

        return $this->redirectToRoute('rental_cart');
    }

    /**
     * カート全削除処理
     *
     * @param Request $request
     * @return Response
     */
    private function clearCart(Request $request): Response
    {
        try {
            $this->cartService->clearCart();
            $this->addSuccess('カートを空にしました。');

        } catch (\Exception $e) {
            $this->addError('カートのクリアに失敗しました。');
        }

        return $this->redirectToRoute('rental_cart');
    }
}