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

namespace Plugin\Rental;

use Eccube\Event\TemplateEvent;
use Eccube\Event\EccubeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * レンタル管理プラグインのメインクラス
 * 
 * プラグインのイベント処理とサービス登録を行います
 */
class RentalPlugin implements EventSubscriberInterface
{
    /**
     * プラグインで処理するイベントを定義
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // テンプレートイベント
            '@admin/Product/product.twig' => 'onAdminProductTemplate',
            'Product/detail.twig' => 'onProductDetailTemplate',
            'Mypage/index.twig' => 'onMypageTemplate',
            'Cart/index.twig' => 'onCartTemplate',
            
            // EC-CUBEイベント
            EccubeEvents::ADMIN_PRODUCT_EDIT_COMPLETE => 'onAdminProductEditComplete',
            EccubeEvents::FRONT_CART_ADD_COMPLETE => 'onCartAddComplete',
            
            // カーネルイベント
            'kernel.controller' => 'onKernelController',
            'kernel.response' => 'onKernelResponse',
        ];
    }

    /**
     * 管理画面商品編集ページでのテンプレート拡張
     * 
     * @param TemplateEvent $event
     */
    public function onAdminProductTemplate(TemplateEvent $event)
    {
        try {
            // 商品編集画面にレンタル設定タブを追加
            $event->addSnippet('@Rental/admin/Product/product_rental_nav.twig');
            $event->addSnippet('@Rental/admin/Product/product_rental_content.twig');
            
            // JavaScriptとCSSを追加
            $event->addAsset('plugin_rental_admin.js');
            $event->addAsset('plugin_rental_admin.css');
            
            // ログ出力（デバッグ用）
            error_log('管理画面商品編集テンプレート拡張完了');
            
        } catch (\Exception $e) {
            error_log('管理画面商品編集テンプレート拡張失敗: ' . $e->getMessage());
        }
    }

    /**
     * 商品詳細ページでのテンプレート拡張
     * 
     * @param TemplateEvent $event
     */
    public function onProductDetailTemplate(TemplateEvent $event)
    {
        try {
            $parameters = $event->getParameters();
            
            if (isset($parameters['Product'])) {
                $product = $parameters['Product'];
                
                // レンタル対応商品かチェック
                $rentalProduct = $this->getRentalProductInfo($product);
                
                if ($rentalProduct && $rentalProduct->isRentalEnabled()) {
                    // レンタル注文フォームを追加
                    $event->addSnippet('@Rental/default/Product/rental_detail.twig');
                    $event->setParameter('rental_product', $rentalProduct);
                    
                    // レンタル用JavaScript追加
                    $event->addAsset('plugin_rental_front.js');
                    $event->addAsset('plugin_rental_front.css');
                }
            }
            
            error_log('商品詳細テンプレート拡張完了');
            
        } catch (\Exception $e) {
            error_log('商品詳細テンプレート拡張失敗: ' . $e->getMessage());
        }
    }

    /**
     * マイページでのテンプレート拡張
     * 
     * @param TemplateEvent $event
     */
    public function onMypageTemplate(TemplateEvent $event)
    {
        try {
            // レンタル履歴ナビゲーションを追加
            $event->addSnippet('@Rental/front/mypage_navi_add.twig');
            
            error_log('マイページテンプレート拡張完了');
            
        } catch (\Exception $e) {
            error_log('マイページテンプレート拡張失敗: ' . $e->getMessage());
        }
    }

    /**
     * カートページでのテンプレート拡張
     * 
     * @param TemplateEvent $event
     */
    public function onCartTemplate(TemplateEvent $event)
    {
        try {
            // レンタル商品がカートに含まれている場合の処理
            $parameters = $event->getParameters();
            
            if (isset($parameters['Carts'])) {
                $hasRentalItems = $this->checkRentalItemsInCart($parameters['Carts']);
                
                if ($hasRentalItems) {
                    $event->addSnippet('@Rental/default/cart/rental_cart_item.twig');
                    $event->setParameter('has_rental_items', true);
                }
            }
            
            error_log('カートテンプレート拡張完了');
            
        } catch (\Exception $e) {
            error_log('カートテンプレート拡張失敗: ' . $e->getMessage());
        }
    }

    /**
     * 管理画面商品編集完了時の処理
     * 
     * @param $event
     */
    public function onAdminProductEditComplete($event)
    {
        try {
            $product = $event->getSubject();
            $form = $event->getArgument('form');
            
            // レンタル設定の保存処理
            if ($form->has('rental_setting')) {
                $rentalData = $form->get('rental_setting')->getData();
                $this->saveRentalProductSettings($product, $rentalData);
            }
            
            error_log('商品レンタル設定保存完了 product_id: ' . $product->getId());
            
        } catch (\Exception $e) {
            error_log('商品レンタル設定保存失敗: ' . $e->getMessage());
        }
    }

    /**
     * カート追加完了時の処理
     * 
     * @param $event
     */
    public function onCartAddComplete($event)
    {
        try {
            $cartItem = $event->getSubject();
            
            // レンタル商品の場合の在庫チェック
            if ($this->isRentalProduct($cartItem->getProductClass()->getProduct())) {
                $this->validateRentalStock($cartItem);
            }
            
            error_log('レンタル商品カート追加チェック完了');
            
        } catch (\Exception $e) {
            error_log('レンタル商品カート追加チェック失敗: ' . $e->getMessage());
            throw $e; // 在庫不足などはエラーとして表示する必要がある
        }
    }

    /**
     * コントローラー実行前の処理
     * 
     * @param ControllerEvent $event
     */
    public function onKernelController(ControllerEvent $event)
    {
        try {
            $controller = $event->getController();
            
            // レンタル関連のコントローラーの場合の共通処理
            if (is_array($controller) && isset($controller[0])) {
                $controllerClass = get_class($controller[0]);
                
                if (strpos($controllerClass, 'Plugin\\Rental\\') === 0) {
                    // レンタルプラグインのコントローラーの場合
                    $this->setupRentalController($event);
                }
            }
            
        } catch (\Exception $e) {
            error_log('コントローラー前処理失敗: ' . $e->getMessage());
        }
    }

    /**
     * レスポンス処理
     * 
     * @param $event
     */
    public function onKernelResponse($event)
    {
        try {
            // レンタル関連のレスポンス後処理
            error_log('レスポンス後処理完了');
            
        } catch (\Exception $e) {
            error_log('レスポンス後処理失敗: ' . $e->getMessage());
        }
    }

    /**
     * レンタル商品情報取得
     * 
     * @param Product $product
     * @return RentalProduct|null
     */
    private function getRentalProductInfo($product)
    {
        try {
            // TODO: RentalProductRepositoryを使用して取得
            return null;
            
        } catch (\Exception $e) {
            error_log('レンタル商品情報取得失敗: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * カート内のレンタル商品チェック
     * 
     * @param array $carts
     * @return bool
     */
    private function checkRentalItemsInCart($carts)
    {
        try {
            // TODO: カート内商品のレンタル判定実装
            return false;
            
        } catch (\Exception $e) {
            error_log('カート内レンタル商品チェック失敗: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * レンタル商品設定保存
     * 
     * @param $product
     * @param array $rentalData
     */
    private function saveRentalProductSettings($product, $rentalData)
    {
        try {
            // TODO: レンタル設定保存処理実装
            error_log('レンタル設定保存 product_id: ' . $product->getId());
            
        } catch (\Exception $e) {
            error_log('レンタル設定保存失敗: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * レンタル商品判定
     * 
     * @param $product
     * @return bool
     */
    private function isRentalProduct($product)
    {
        try {
            // TODO: レンタル商品判定実装
            return false;
            
        } catch (\Exception $e) {
            error_log('レンタル商品判定失敗: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * レンタル在庫検証
     * 
     * @param $cartItem
     */
    private function validateRentalStock($cartItem)
    {
        try {
            // TODO: レンタル在庫検証実装
            
        } catch (\Exception $e) {
            error_log('レンタル在庫検証失敗: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * レンタルコントローラーセットアップ
     * 
     * @param ControllerEvent $event
     */
    private function setupRentalController(ControllerEvent $event)
    {
        try {
            // TODO: レンタルコントローラー共通処理実装
            error_log('レンタルコントローラーセットアップ完了');
            
        } catch (\Exception $e) {
            error_log('レンタルコントローラーセットアップ失敗: ' . $e->getMessage());
        }
    }
}