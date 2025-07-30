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

use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Plugin\Rental\Entity\RentalProduct;
use Plugin\Rental\Repository\RentalProductRepository;
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Service\RentalInventoryService;
use Plugin\Rental\Form\Type\RentalFrontType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * 商品詳細ページ連携EventListener
 */
class ProductDetailListener
{
    /**
     * @var RentalProductRepository
     */
    private $rentalProductRepository;

    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * @var RentalInventoryService
     */
    private $inventoryService;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * コンストラクタ
     */
    public function __construct(
        RentalProductRepository $rentalProductRepository,
        RentalConfigRepository $configRepository,
        RentalInventoryService $inventoryService,
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->rentalProductRepository = $rentalProductRepository;
        $this->configRepository = $configRepository;
        $this->inventoryService = $inventoryService;
        $this->formFactory = $formFactory;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * 商品詳細ページにレンタル情報を追加
     *
     * @param TemplateEvent $event
     */
    public function onProductDetail(TemplateEvent $event)
    {
        $parameters = $event->getParameters();
        
        if (!isset($parameters['Product']) || !$parameters['Product'] instanceof Product) {
            return;
        }

        /** @var Product $product */
        $product = $parameters['Product'];

        // レンタル商品設定を取得
        $rentalProduct = $this->rentalProductRepository->findOneBy(['Product' => $product]);
        
        if (!$rentalProduct || !$rentalProduct->getIsRentalEnabled()) {
            return;
        }

        // レンタル情報をテンプレートパラメータに追加
        $rentalData = $this->prepareRentalData($product, $rentalProduct);
        $event->setParameters(array_merge($parameters, $rentalData));

        // レンタル用のCSSとJavaScriptを追加
        $this->addRentalAssets($event);

        // テンプレートにレンタル情報を追加
        $this->addRentalSection($event, $rentalProduct);
    }

    /**
     * レンタルデータを準備
     *
     * @param Product $product
     * @param RentalProduct $rentalProduct
     * @return array
     */
    private function prepareRentalData(Product $product, RentalProduct $rentalProduct): array
    {
        // 基本レンタル情報
        $rentalInfo = [
            'daily_rate' => $rentalProduct->getDailyRate(),
            'weekly_rate' => $rentalProduct->getWeeklyRate(),
            'monthly_rate' => $rentalProduct->getMonthlyRate(),
            'min_rental_days' => $rentalProduct->getMinRentalDays(),
            'max_rental_days' => $rentalProduct->getMaxRentalDays(),
            'stock_quantity' => $rentalProduct->getStockQuantity(),
            'deposit_amount' => $rentalProduct->getDepositAmount(),
            'preparation_days' => $rentalProduct->getPreparationDays(),
        ];

        // レンタル設定
        $rentalConfig = [
            'min_rental_days' => $this->configRepository->getInt('min_rental_days', 1),
            'max_rental_days' => $this->configRepository->getInt('max_rental_days', 30),
            'rental_start_buffer_days' => $this->configRepository->getInt('rental_start_buffer_days', 1),
            'deposit_required' => $this->configRepository->getBoolean('deposit_required', false),
        ];

        // 現在の在庫状況
        $availabilityInfo = $this->inventoryService->getAvailabilityInfo($rentalProduct);

        // レンタルフォーム作成
        $rentalForm = $this->formFactory->create(RentalFrontType::class, null, [
            'rental_product' => $rentalProduct
        ]);

        // URL生成
        $apiUrls = [
            'check_availability' => $this->urlGenerator->generate('rental_check_availability'),
            'calculate_price' => $this->urlGenerator->generate('rental_calculate_price'),
            'add_to_cart' => $this->urlGenerator->generate('rental_cart_add'),
            'calendar' => $this->urlGenerator->generate('rental_calendar', ['id' => $product->getId()]),
        ];

        return [
            'RentalProduct' => $rentalProduct,
            'rental_info' => $rentalInfo,
            'rental_config' => $rentalConfig,
            'availability_info' => $availabilityInfo,
            'rental_form' => $rentalForm,
            'rental_api_urls' => $apiUrls,
        ];
    }

    /**
     * レンタル用アセットを追加
     *
     * @param TemplateEvent $event
     */
    private function addRentalAssets(TemplateEvent $event)
    {
        $product = $event->getParameter('Product');
        
        // CSS追加
        $css = '
        <style>
        .rental-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .rental-price {
            font-size: 1.25rem;
            font-weight: bold;
            color: #007bff;
        }
        .rental-form {
            background: white;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-top: 1rem;
        }
        .availability-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .availability-indicator.available { background-color: #28a745; }
        .availability-indicator.unavailable { background-color: #dc3545; }
        .availability-indicator.checking { background-color: #ffc107; }
        .price-calculation {
            background-color: #e7f1ff;
            border: 1px solid #b3d7ff;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        .rental-badge {
            background-color: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        .rental-list-price {
            color: #007bff;
            font-weight: bold;
            font-size: 0.9rem;
        }
        </style>';

        // JavaScript追加
        $js = '
        <script>
        $(document).ready(function() {
            // レンタル期間変更時の処理
            $(".rental-date-input").on("change", function() {
                updateRentalCalculation();
                checkRentalAvailability();
            });

            // 数量変更時の処理
            $(".rental-quantity-input").on("change", function() {
                updateRentalCalculation();
                checkRentalAvailability();
            });

            // 料金計算
            function updateRentalCalculation() {
                const productId = ' . $product->getId() . ';
                const startDate = $("#rental_front_rental_start_date").val();
                const endDate = $("#rental_front_rental_end_date").val();
                const quantity = $("#rental_front_quantity").val();

                if (!startDate || !endDate) return;

                $.ajax({
                    url: "' . $this->urlGenerator->generate('rental_calculate_price') . '",
                    method: "POST",
                    data: {
                        product_id: productId,
                        start_date: startDate,
                        end_date: endDate,
                        quantity: quantity
                    },
                    success: function(response) {
                        $("#rental-price-display").text("¥" + response.total.toLocaleString());
                        $("#rental-days-display").text(response.total_days + "日間");
                        $(".price-calculation").show();
                    },
                    error: function(xhr) {
                        console.error("料金計算エラー:", xhr.responseJSON);
                    }
                });
            }

            // 在庫確認
            function checkRentalAvailability() {
                const productId = ' . $product->getId() . ';
                const startDate = $("#rental_front_rental_start_date").val();
                const endDate = $("#rental_front_rental_end_date").val();
                const quantity = $("#rental_front_quantity").val();

                if (!startDate || !endDate) return;

                const indicator = $(".availability-indicator");
                const message = $("#availability-message");
                
                // チェック中表示
                indicator.removeClass("available unavailable").addClass("checking");
                message.text("在庫確認中...");

                $.ajax({
                    url: "' . $this->urlGenerator->generate('rental_check_availability') . '",
                    method: "POST",
                    data: {
                        product_id: productId,
                        start_date: startDate,
                        end_date: endDate,
                        quantity: quantity
                    },
                    success: function(response) {
                        if (response.available) {
                            indicator.removeClass("unavailable checking").addClass("available");
                            message.text("在庫あり（" + response.available_quantity + "個まで利用可能）");
                            $("#rental-add-to-cart").prop("disabled", false);
                        } else {
                            indicator.removeClass("available checking").addClass("unavailable");
                            message.text(response.message || "在庫不足");
                            $("#rental-add-to-cart").prop("disabled", true);
                        }
                    },
                    error: function(xhr) {
                        indicator.removeClass("available checking").addClass("unavailable");
                        message.text("在庫確認に失敗しました");
                        $("#rental-add-to-cart").prop("disabled", true);
                    }
                });
            }

            // カートに追加処理
            $("#rental-add-to-cart").on("click", function(e) {
                e.preventDefault();
                
                const button = $(this);
                const originalText = button.text();
                button.prop("disabled", true).text("処理中...");
                
                const formData = {
                    product_id: ' . $product->getId() . ',
                    start_date: $("#rental_front_rental_start_date").val(),
                    end_date: $("#rental_front_rental_end_date").val(),
                    quantity: $("#rental_front_quantity").val()
                };

                $.ajax({
                    url: "' . $this->urlGenerator->generate('rental_cart_add') . '",
                    method: "POST",
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            alert("レンタルカートに追加しました");
                            // カート件数を更新
                            $(".cart-counter").text(response.cart_count);
                            
                            // カート画面への遷移を提案
                            if (confirm("カート画面に移動しますか？")) {
                                window.location.href = "' . $this->urlGenerator->generate('rental_cart') . '";
                            }
                        } else {
                            alert(response.message || "カートへの追加に失敗しました");
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        alert(response.message || "エラーが発生しました");
                    },
                    complete: function() {
                        button.prop("disabled", false).text(originalText);
                    }
                });
            });
        });
        </script>';

        $event->addSnippet($css);
        $event->addSnippet($js);
    }

    /**
     * レンタルセクションをテンプレートに追加
     *
     * @param TemplateEvent $event
     * @param RentalProduct $rentalProduct
     */
    private function addRentalSection(TemplateEvent $event, RentalProduct $rentalProduct)
    {
        $rentalInfo = $event->getParameter('rental_info');

        $rentalHtml = '
        <div class="rental-section">
            <h4><i class="fa fa-calendar-alt"></i> レンタル予約</h4>
            
            <div class="rental-info">
                <div class="row">
                    <div class="col-md-6">
                        <strong>日額料金:</strong> 
                        <span class="rental-price">¥' . number_format($rentalInfo['daily_rate']) . '</span>';
        
        if ($rentalInfo['weekly_rate']) {
            $rentalHtml .= '<br><strong>週額料金:</strong> ¥' . number_format($rentalInfo['weekly_rate']);
        }
        if ($rentalInfo['monthly_rate']) {
            $rentalHtml .= '<br><strong>月額料金:</strong> ¥' . number_format($rentalInfo['monthly_rate']);
        }
        
        $rentalHtml .= '
                    </div>
                    <div class="col-md-6">
                        <strong>レンタル期間:</strong> ' . $rentalInfo['min_rental_days'] . '日〜';
        
        if ($rentalInfo['max_rental_days']) {
            $rentalHtml .= $rentalInfo['max_rental_days'] . '日';
        } else {
            $rentalHtml .= '制限なし';
        }
        
        $rentalHtml .= '<br><strong>在庫数:</strong> ' . $rentalInfo['stock_quantity'] . '個';
        
        if ($rentalInfo['deposit_amount']) {
            $rentalHtml .= '<br><strong>保証金:</strong> ¥' . number_format($rentalInfo['deposit_amount']);
        }
        
        $rentalHtml .= '
                    </div>
                </div>
            </div>

            <div class="rental-form">
                <form id="rental-booking-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">レンタル開始日</label>
                                <input type="date" class="form-control rental-date-input" 
                                       id="rental_front_rental_start_date" 
                                       min="' . date('Y-m-d', strtotime('+' . ($rentalInfo['preparation_days'] ?? 1) . ' days')) . '">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">レンタル終了日</label>
                                <input type="date" class="form-control rental-date-input" 
                                       id="rental_front_rental_end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">数量</label>
                                <input type="number" class="form-control rental-quantity-input" 
                                       id="rental_front_quantity" 
                                       value="1" min="1" max="' . $rentalInfo['stock_quantity'] . '">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">在庫状況</label>
                                <div>
                                    <span class="availability-indicator"></span>
                                    <span id="availability-message">期間を選択してください</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="price-calculation" style="display: none;">
                        <div class="row text-center">
                            <div class="col-md-6">
                                <strong>料金:</strong> <span id="rental-price-display">¥0</span>
                            </div>
                            <div class="col-md-6">
                                <strong>期間:</strong> <span id="rental-days-display">0日間</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions mt-3">
                        <button type="button" class="btn btn-primary btn-lg w-100" 
                                id="rental-add-to-cart" disabled>
                            <i class="fa fa-cart-plus"></i> レンタルカートに追加
                        </button>
                    </div>
                </form>
            </div>
        </div>';

        // 商品詳細の適切な位置にレンタルセクションを追加
        $search = '<div class="ec-productRole__btn">';
        $replace = $rentalHtml . $search;
        
        $source = $event->getSource();
        if (strpos($source, $search) !== false) {
            $event->setSource(str_replace($search, $replace, $source));
        }
    }

    /**
     * 商品一覧ページでレンタル情報を表示
     *
     * @param TemplateEvent $event
     */
    public function onProductList(TemplateEvent $event)
    {
        $parameters = $event->getParameters();
        
        if (!isset($parameters['pagination'])) {
            return;
        }

        // 商品にレンタル情報を追加
        $this->addRentalInfoToProducts($event);
        
        // レンタル商品フィルターを追加
        $this->addRentalFilter($event);
    }

    /**
     * 商品にレンタル情報を追加
     *
     * @param TemplateEvent $event
     */
    private function addRentalInfoToProducts(TemplateEvent $event)
    {
        $css = '
        <style>
        .rental-badge {
            background-color: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        .rental-list-price {
            color: #007bff;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .rental-filter {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        </style>';

        $js = '
        <script>
        $(document).ready(function() {
            // 商品リストの各商品にレンタル情報を追加
            $(".ec-shelfRole__item").each(function() {
                const productLink = $(this).find("a").first();
                const href = productLink.attr("href");
                if (href) {
                    const productId = href.match(/\/products\/(\d+)/);
                    if (productId) {
                        checkProductRentalInfo(productId[1], $(this));
                    }
                }
            });

            function checkProductRentalInfo(productId, element) {
                // レンタル商品情報をチェック
                $.ajax({
                    url: "/rental/product/" + productId + "/info",
                    method: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response && response.is_rental_enabled) {
                            const badge = \'<span class="rental-badge">レンタル可</span>\';
                            const price = \'<div class="rental-list-price">日額¥\' + response.daily_rate.toLocaleString() + \'〜</div>\';
                            
                            element.find(".ec-shelfRole__itemName").append(badge);
                            element.find(".ec-shelfRole__itemPrice").after(price);
                        }
                    },
                    error: function() {
                        // エラーは無視（レンタル情報が取得できないだけ）
                    }
                });
            }

            // レンタル商品フィルター
            $("#rental-only-filter").on("change", function() {
                const isChecked = $(this).is(":checked");
                
                if (isChecked) {
                    // レンタル商品のみ表示
                    $(".ec-shelfRole__item").each(function() {
                        if ($(this).find(".rental-badge").length === 0) {
                            $(this).hide();
                        }
                    });
                } else {
                    // 全商品表示
                    $(".ec-shelfRole__item").show();
                }
            });
        });
        </script>';

        $event->addSnippet($css);
        $event->addSnippet($js);
    }

    /**
     * レンタル商品フィルターを追加
     *
     * @param TemplateEvent $event
     */
    private function addRentalFilter(TemplateEvent $event)
    {
        $filterHtml = '
        <div class="rental-filter">
            <h5><i class="fa fa-calendar-alt"></i> レンタル商品フィルター</h5>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="rental-only-filter">
                <label class="form-check-label" for="rental-only-filter">
                    レンタル可能商品のみ表示
                </label>
            </div>
        </div>';

        // 検索フィルターエリアに追加
        $search = '<div class="ec-searchnavRole__action">';
        $replace = $filterHtml . $search;
        
        $source = $event->getSource();
        if (strpos($source, $search) !== false) {
            $event->setSource(str_replace($search, $replace, $source));
        }
    }

    /**
     * カートページでレンタル商品を識別
     *
     * @param TemplateEvent $event
     */
    public function onCart(TemplateEvent $event)
    {
        // レンタルカートへのリンクを追加
        $rentalCartLink = '
        <div class="rental-cart-link mb-3">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                レンタル商品は専用のカートで管理されます。
                <a href="' . $this->urlGenerator->generate('rental_cart') . '" class="btn btn-outline-primary btn-sm ms-2">
                    <i class="fa fa-calendar"></i> レンタルカートを確認
                </a>
            </div>
        </div>';

        $search = '<div class="ec-cartRole__progress">';
        $replace = $rentalCartLink . $search;
        
        $source = $event->getSource();
        if (strpos($source, $search) !== false) {
            $event->setSource(str_replace($search, $replace, $source));
        }
    }
}