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

use Eccube\Plugin\AbstractPluginEventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * レンタル管理プラグインのメインクラス
 * 
 * プラグインのイベント処理とサービス登録を行います
 */
class RentalPlugin extends AbstractPluginEventSubscriber implements EventSubscriberInterface
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
            
            // エンティティイベント
            'eccube.event.entity.pre_persist' => 'onEntityPrePersist',
            'eccube.event.entity.pre_update' => 'onEntityPreUpdate',
            
            // ルーティングイベント
            'kernel.controller' => 'onKernelController',
            
            // フォームイベント
            'admin.product.edit.complete' => 'onAdminProductEditComplete',
        ];
    }

    /**
     * 管理画面商品編集ページでのテンプレート拡張
     * 
     * @param TemplateEvent $event
     */
    public function onAdminProductTemplate($event)
    {
        // 商品編集画面にレンタル設定タブを追加
        $event->addSnippet('@Rental/admin/Product/product_rental_nav.twig');
        $event->addSnippet('@Rental/admin/Product/product_rental_content.twig');
    }

    /**
     * 商品詳細ページでのテンプレート拡張
     * 
     * @param TemplateEvent $event
     */
    public function onProductDetailTemplate($event)
    {
        // 商品詳細ページにレンタル注文フォームを追加
        $parameters = $event->getParameters();
        
        if (isset($parameters['Product'])) {
            $product = $parameters['Product'];
            
            // レンタル対応商品かチェック
            $rentalProduct = $this->getRentalProductRepository()->findOneBy(['Product' => $product]);
            
            if ($rentalProduct && $rentalProduct->getIsRentalEnabled()) {
                $event->setParameter('rental_product', $rentalProduct);
                $event->addSnippet('@Rental/default/Product/rental_detail.twig');
            }
        }
    }

    /**
     * マイページでのテンプレート拡張
     * 
     * @param TemplateEvent $event
     */
    public function onMypageTemplate($event)
    {
        // マイページにレンタル履歴リンクを追加
        $event->addSnippet('@Rental/front/mypage_navi_add.twig');
    }

    /**
     * エンティティ保存前の処理
     * 
     * @param EntityEvent $event
     */
    public function onEntityPrePersist($event)
    {
        $entity = $event->getEntity();
        
        // レンタル関連エンティティの場合、作成日時を設定
        if ($this->isRentalEntity($entity)) {
            $entity->setCreateDate(new \DateTime());
            $entity->setUpdateDate(new \DateTime());
        }
    }

    /**
     * エンティティ更新前の処理
     * 
     * @param EntityEvent $event
     */
    public function onEntityPreUpdate($event)
    {
        $entity = $event->getEntity();
        
        // レンタル関連エンティティの場合、更新日時を設定
        if ($this->isRentalEntity($entity)) {
            $entity->setUpdateDate(new \DateTime());
        }
    }

    /**
     * コントローラー実行前の処理
     * 
     * @param ControllerEvent $event
     */
    public function onKernelController($event)
    {
        $controller = $event->getController();
        
        // レンタル関連コントローラーの場合、共通処理を実行
        if (is_array($controller) && strpos(get_class($controller[0]), 'Plugin\\Rental') !== false) {
            $this->initializeRentalController($controller[0], $event->getRequest());
        }
    }

    /**
     * 管理画面商品編集完了時の処理
     * 
     * @param EventArgs $event
     */
    public function onAdminProductEditComplete($event)
    {
        $form = $event->getForm();
        $product = $event->getProduct();
        
        // レンタル設定フォームのデータ処理
        if ($form->has('rental_setting')) {
            $rentalData = $form->get('rental_setting')->getData();
            $this->saveRentalProductSetting($product, $rentalData);
        }
    }

    /**
     * レンタル関連エンティティかどうかを判定
     * 
     * @param object $entity
     * @return bool
     */
    private function isRentalEntity($entity)
    {
        $rentalEntityClasses = [
            'Plugin\\Rental\\Entity\\RentalOrder',
            'Plugin\\Rental\\Entity\\RentalProduct',
            'Plugin\\Rental\\Entity\\RentalConfig',
            'Plugin\\Rental\\Entity\\RentalCart',
            'Plugin\\Rental\\Entity\\RentalInventory',
        ];
        
        return in_array(get_class($entity), $rentalEntityClasses);
    }

    /**
     * レンタルコントローラーの初期化処理
     * 
     * @param object $controller
     * @param Request $request
     */
    private function initializeRentalController($controller, $request)
    {
        // セキュリティチェック
        if (method_exists($controller, 'checkRentalSecurity')) {
            $controller->checkRentalSecurity($request);
        }
        
        // 共通パラメータ設定
        if (method_exists($controller, 'setRentalCommonParameters')) {
            $controller->setRentalCommonParameters();
        }
    }

    /**
     * レンタル商品設定を保存
     * 
     * @param Product $product
     * @param array $rentalData
     */
    private function saveRentalProductSetting($product, $rentalData)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $repository = $this->getRentalProductRepository();
        
        // 既存レンタル設定取得または新規作成
        $rentalProduct = $repository->findOneBy(['Product' => $product]);
        if (!$rentalProduct) {
            $rentalProduct = new \Plugin\Rental\Entity\RentalProduct();
            $rentalProduct->setProduct($product);
        }
        
        // データ設定
        if (isset($rentalData['daily_price'])) {
            $rentalProduct->setDailyPrice($rentalData['daily_price']);
        }
        if (isset($rentalData['weekly_price'])) {
            $rentalProduct->setWeeklyPrice($rentalData['weekly_price']);
        }
        if (isset($rentalData['monthly_price'])) {
            $rentalProduct->setMonthlyPrice($rentalData['monthly_price']);
        }
        if (isset($rentalData['is_rental_enabled'])) {
            $rentalProduct->setIsRentalEnabled($rentalData['is_rental_enabled']);
        }
        
        $entityManager->persist($rentalProduct);
        $entityManager->flush();
    }

    /**
     * RentalProductRepositoryを取得
     * 
     * @return RentalProductRepository
     */
    private function getRentalProductRepository()
    {
        return $this->container->get('doctrine.orm.entity_manager')
            ->getRepository('Plugin\\Rental\\Entity\\RentalProduct');
    }

    /**
     * サービスコンテナ設定
     * 
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        
        // カスタムサービスの登録（後のフェーズで実装）
    }

    /**
     * プラグイン固有の設定取得
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPluginConfig($key, $default = null)
    {
        $configRepository = $this->container->get('doctrine.orm.entity_manager')
            ->getRepository('Plugin\\Rental\\Entity\\RentalConfig');
            
        $config = $configRepository->findOneBy(['config_key' => $key]);
        
        return $config ? $config->getConfigValue() : $default;
    }

    /**
     * ログ出力
     * 
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = 'info')
    {
        $logger = $this->container->get('monolog.logger.plugin');
        $logger->$level('[RentalPlugin] ' . $message);
    }
}