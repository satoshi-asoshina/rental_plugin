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
use Eccube\Repository\ProductRepository;
use Plugin\Rental\Entity\RentalProduct;
use Plugin\Rental\Repository\RentalProductRepository;
use Plugin\Rental\Repository\RentalOrderRepository;
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Service\RentalCalculationService;
use Plugin\Rental\Service\RentalInventoryService;
use Plugin\Rental\Form\Type\RentalFrontType;
use Plugin\Rental\Exception\RentalException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * フロント レンタル商品コントローラー
 * 
 * @Route("/rental")
 */
class RentalProductController extends AbstractController
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var RentalProductRepository
     */
    private $rentalProductRepository;

    /**
     * @var RentalOrderRepository
     */
    private $orderRepository;

    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * @var RentalCalculationService
     */
    private $calculationService;

    /**
     * @var RentalInventoryService
     */
    private $inventoryService;

    /**
     * コンストラクタ
     */
    public function __construct(
        ProductRepository $productRepository,
        RentalProductRepository $rentalProductRepository,
        RentalOrderRepository $orderRepository,
        RentalConfigRepository $configRepository,
        RentalCalculationService $calculationService,
        RentalInventoryService $inventoryService
    ) {
        $this->productRepository = $productRepository;
        $this->rentalProductRepository = $rentalProductRepository;
        $this->orderRepository = $orderRepository;
        $this->configRepository = $configRepository;
        $this->calculationService = $calculationService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * レンタル商品詳細表示
     *
     * @Route("/product/{id}", name="rental_product_detail", methods={"GET", "POST"}, requirements={"id" = "\d+"})
     * @Template("@Rental/default/Product/rental_detail.twig")
     */
    public function detail(Request $request, Product $product)
    {
        // レンタル商品設定を取得
        $rentalProduct = $this->rentalProductRepository->findOneBy(['Product' => $product]);
        
        if (!$rentalProduct || !$rentalProduct->getIsRentalEnabled()) {
            throw $this->createNotFoundException('この商品はレンタル対象外です。');
        }

        // レンタルフォーム作成
        $form = $this->createForm(RentalFrontType::class, null, [
            'rental_product' => $rentalProduct
        ]);
        
        $form->handleRequest($request);

        $calculationResult = null;
        $availabilityInfo = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            
            try {
                // 料金計算
                $calculationResult = $this->calculationService->calculateRental([
                    'rental_product' => $rentalProduct,
                    'start_date' => $formData['rental_start_date'],
                    'end_date' => $formData['rental_end_date'],
                    'quantity' => $formData['quantity'] ?? 1,
                    'options' => $formData['options'] ?? [],
                ]);

                // 在庫確認
                $availabilityInfo = $this->inventoryService->checkAvailability(
                    $rentalProduct,
                    $formData['rental_start_date'],
                    $formData['rental_end_date'],
                    $formData['quantity'] ?? 1
                );

            } catch (RentalException $e) {
                $this->addError($e->getMessage());
            }
        }

        return [
            'Product' => $product,
            'RentalProduct' => $rentalProduct,
            'form' => $form->createView(),
            'calculation_result' => $calculationResult,
            'availability_info' => $availabilityInfo,
            'rental_config' => $this->getRentalConfig(),
            'related_products' => $this->getRelatedRentalProducts($product),
            'reviews' => $this->getRentalReviews($product),
        ];
    }

    /**
     * レンタル可能日程確認（Ajax）
     *
     * @Route("/check-availability", name="rental_check_availability", methods={"POST"})
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $productId = $request->request->get('product_id');
            $startDate = new \DateTime($request->request->get('start_date'));
            $endDate = new \DateTime($request->request->get('end_date'));
            $quantity = (int) $request->request->get('quantity', 1);

            $product = $this->productRepository->find($productId);
            $rentalProduct = $this->rentalProductRepository->findOneBy(['Product' => $product]);

            if (!$rentalProduct) {
                throw new RentalException('レンタル商品が見つかりません。');
            }

            $availability = $this->inventoryService->checkAvailability(
                $rentalProduct,
                $startDate,
                $endDate,
                $quantity
            );

            return new JsonResponse([
                'available' => $availability['available'],
                'available_quantity' => $availability['available_quantity'],
                'message' => $availability['message'] ?? '',
                'alternative_dates' => $availability['alternative_dates'] ?? [],
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'available' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * レンタル料金計算（Ajax）
     *
     * @Route("/calculate-price", name="rental_calculate_price", methods={"POST"})
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        try {
            $productId = $request->request->get('product_id');
            $startDate = new \DateTime($request->request->get('start_date'));
            $endDate = new \DateTime($request->request->get('end_date'));
            $quantity = (int) $request->request->get('quantity', 1);
            $options = $request->request->get('options', []);

            $product = $this->productRepository->find($productId);
            $rentalProduct = $this->rentalProductRepository->findOneBy(['Product' => $product]);

            if (!$rentalProduct) {
                throw new RentalException('レンタル商品が見つかりません。');
            }

            $calculation = $this->calculationService->calculateRental([
                'rental_product' => $rentalProduct,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'quantity' => $quantity,
                'options' => $options,
            ]);

            return new JsonResponse([
                'base_price' => $calculation['base_price'],
                'total_days' => $calculation['total_days'],
                'subtotal' => $calculation['subtotal'],
                'deposit' => $calculation['deposit'],
                'tax' => $calculation['tax'],
                'total' => $calculation['total'],
                'breakdown' => $calculation['breakdown'],
                'discount_info' => $calculation['discount_info'] ?? null,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * レンタルカレンダー表示（Ajax）
     *
     * @Route("/calendar/{id}", name="rental_calendar", methods={"GET"}, requirements={"id" = "\d+"})
     */
    public function calendar(Request $request, Product $product): JsonResponse
    {
        try {
            $rentalProduct = $this->rentalProductRepository->findOneBy(['Product' => $product]);
            
            if (!$rentalProduct) {
                throw new RentalException('レンタル商品が見つかりません。');
            }

            $year = $request->query->getInt('year', date('Y'));
            $month = $request->query->getInt('month', date('n'));

            $calendarData = $this->inventoryService->getCalendarData($rentalProduct, $year, $month);

            return new JsonResponse([
                'calendar' => $calendarData,
                'year' => $year,
                'month' => $month,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * レンタル商品一覧
     *
     * @Route("/products", name="rental_product_list", methods={"GET"})
     * @Template("@Rental/default/product_list.twig")
     */
    public function productList(Request $request)
    {
        $qb = $this->rentalProductRepository->createQueryBuilder('rp')
            ->innerJoin('rp.Product', 'p')
            ->where('rp.is_rental_enabled = :enabled')
            ->andWhere('p.Status = :status')
            ->setParameter('enabled', true)
            ->setParameter('status', \Eccube\Entity\Master\ProductStatus::DISPLAY_SHOW)
            ->orderBy('p.create_date', 'DESC');

        // 検索条件適用
        $category = $request->query->get('category');
        if ($category) {
            $qb->innerJoin('p.ProductCategories', 'pc')
               ->andWhere('pc.Category = :category')
               ->setParameter('category', $category);
        }

        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');
        if ($priceMin) {
            $qb->andWhere('rp.daily_rate >= :price_min')
               ->setParameter('price_min', $priceMin);
        }
        if ($priceMax) {
            $qb->andWhere('rp.daily_rate <= :price_max')
               ->setParameter('price_max', $priceMax);
        }

        $availableFrom = $request->query->get('available_from');
        $availableTo = $request->query->get('available_to');
        if ($availableFrom && $availableTo) {
            // 在庫チェックのためのサブクエリ
            $availableProducts = $this->inventoryService->getAvailableProducts(
                new \DateTime($availableFrom),
                new \DateTime($availableTo)
            );
            
            if (!empty($availableProducts)) {
                $qb->andWhere('rp.id IN (:available_products)')
                   ->setParameter('available_products', $availableProducts);
            } else {
                // 利用可能な商品がない場合は空の結果を返す
                $qb->andWhere('rp.id = :none')
                   ->setParameter('none', 0);
            }
        }

        $rentalProducts = $qb->getQuery()->getResult();

        return [
            'rental_products' => $rentalProducts,
            'search_criteria' => [
                'category' => $category,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
                'available_from' => $availableFrom,
                'available_to' => $availableTo,
            ],
            'categories' => $this->getAvailableCategories(),
            'price_range' => $this->getPriceRange(),
        ];
    }

    /**
     * レンタル商品検索（Ajax）
     *
     * @Route("/search", name="rental_product_search", methods={"POST"})
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $keyword = $request->request->get('keyword', '');
            $category = $request->request->get('category');
            $startDate = $request->request->get('start_date');
            $endDate = $request->request->get('end_date');
            $priceMin = $request->request->get('price_min');
            $priceMax = $request->request->get('price_max');

            $searchResults = $this->rentalProductRepository->searchProducts([
                'keyword' => $keyword,
                'category' => $category,
                'start_date' => $startDate ? new \DateTime($startDate) : null,
                'end_date' => $endDate ? new \DateTime($endDate) : null,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
            ]);

            $products = [];
            foreach ($searchResults as $rentalProduct) {
                $product = $rentalProduct->getProduct();
                $products[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescriptionDetail(),
                    'daily_rate' => $rentalProduct->getDailyRate(),
                    'image' => $product->getMainListImage(),
                    'url' => $this->generateUrl('rental_product_detail', ['id' => $product->getId()]),
                    'available' => $this->inventoryService->isAvailable($rentalProduct),
                ];
            }

            return new JsonResponse([
                'products' => $products,
                'total' => count($products),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * レンタル設定を取得
     *
     * @return array
     */
    private function getRentalConfig(): array
    {
        return [
            'min_rental_days' => $this->configRepository->getInt('min_rental_days', 1),
            'max_rental_days' => $this->configRepository->getInt('max_rental_days', 30),
            'default_rental_days' => $this->configRepository->getInt('default_rental_days', 7),
            'rental_start_buffer_days' => $this->configRepository->getInt('rental_start_buffer_days', 1),
            'deposit_required' => $this->configRepository->getBoolean('deposit_required', false),
            'cancellation_policy' => $this->configRepository->getString('cancellation_policy', ''),
        ];
    }

    /**
     * 関連レンタル商品を取得
     *
     * @param Product $product
     * @return array
     */
    private function getRelatedRentalProducts(Product $product): array
    {
        $categories = $product->getProductCategories();
        
        if ($categories->isEmpty()) {
            return [];
        }

        $categoryIds = [];
        foreach ($categories as $productCategory) {
            $categoryIds[] = $productCategory->getCategory()->getId();
        }

        return $this->rentalProductRepository->createQueryBuilder('rp')
            ->innerJoin('rp.Product', 'p')
            ->innerJoin('p.ProductCategories', 'pc')
            ->where('rp.is_rental_enabled = :enabled')
            ->andWhere('p.Status = :status')
            ->andWhere('pc.Category IN (:categories)')
            ->andWhere('p.id != :current_product')
            ->setParameter('enabled', true)
            ->setParameter('status', \Eccube\Entity\Master\ProductStatus::DISPLAY_SHOW)
            ->setParameter('categories', $categoryIds)
            ->setParameter('current_product', $product->getId())
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();
    }

    /**
     * レンタルレビューを取得
     *
     * @param Product $product
     * @return array
     */
    private function getRentalReviews(Product $product): array
    {
        // 実装例：レンタル完了済みの注文からレビューを取得
        // 実際の実装では ReviewEntity などを作成する必要があります
        return [];
    }

    /**
     * 利用可能なカテゴリを取得
     *
     * @return array
     */
    private function getAvailableCategories(): array
    {
        return $this->rentalProductRepository->createQueryBuilder('rp')
            ->select('DISTINCT c.id, c.name')
            ->innerJoin('rp.Product', 'p')
            ->innerJoin('p.ProductCategories', 'pc')
            ->innerJoin('pc.Category', 'c')
            ->where('rp.is_rental_enabled = :enabled')
            ->andWhere('p.Status = :status')
            ->setParameter('enabled', true)
            ->setParameter('status', \Eccube\Entity\Master\ProductStatus::DISPLAY_SHOW)
            ->orderBy('c.sort_no', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * 価格帯を取得
     *
     * @return array
     */
    private function getPriceRange(): array
    {
        $result = $this->rentalProductRepository->createQueryBuilder('rp')
            ->select('MIN(rp.daily_rate) as min_price, MAX(rp.daily_rate) as max_price')
            ->where('rp.is_rental_enabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleResult();

        return [
            'min' => (int) $result['min_price'],
            'max' => (int) $result['max_price'],
        ];
    }
}