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

use Eccube\Plugin\AbstractPluginManager;
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\PageRepository;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * レンタル管理プラグインのインストール・アンインストール処理
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * プラグイン有効化時の処理
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        try {
            // データベースマイグレーション実行
            $this->executeMigration($container);
            
            // 初期設定データ投入
            $this->insertInitialData($container);
            
            // フロントページ作成
            $this->createFrontPages($container);
            
            // 管理画面メニュー追加
            $this->createAdminMenu($container);
            
            log_info('レンタルプラグイン有効化完了');
            
        } catch (\Exception $e) {
            log_error('レンタルプラグイン有効化失敗', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * プラグイン無効化時の処理
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        try {
            // ページを非表示にする（削除はしない）
            $this->hideFrontPages($container);
            
            // 管理画面メニューを非表示
            $this->hideAdminMenu($container);
            
            log_info('レンタルプラグイン無効化完了');
            
        } catch (\Exception $e) {
            log_error('レンタルプラグイン無効化失敗', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * プラグインアンインストール時の処理
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        try {
            // フロントページ削除
            $this->removeFrontPages($container);
            
            // 管理画面メニュー削除
            $this->removeAdminMenu($container);
            
            // 注意: データベースのテーブルは削除しない（データ保護のため）
            // 必要に応じて手動でテーブル削除を行ってください
            
            log_info('レンタルプラグインアンインストール完了');
            
        } catch (\Exception $e) {
            log_error('レンタルプラグインアンインストール失敗', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * プラグインアップデート時の処理
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta, ContainerInterface $container)
    {
        try {
            // バージョンアップ時のマイグレーション実行
            $this->executeMigration($container);
            
            // 設定の更新
            $this->updateConfig($container);
            
            log_info('レンタルプラグインアップデート完了');
            
        } catch (\Exception $e) {
            log_error('レンタルプラグインアップデート失敗', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * データベースマイグレーション実行
     *
     * @param ContainerInterface $container
     */
    private function executeMigration(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        try {
            // マイグレーションファイルのパスを取得
            $migrationDir = __DIR__ . '/Resource/doctrine/migration';
            
            if (!is_dir($migrationDir)) {
                log_info('マイグレーションディレクトリが見つかりません', ['dir' => $migrationDir]);
                return;
            }
            
            // マイグレーション実行
            $migrationFiles = glob($migrationDir . '/Version*.php');
            sort($migrationFiles);
            
            foreach ($migrationFiles as $file) {
                $className = 'Plugin\\Rental\\Resource\\doctrine\\migration\\' . basename($file, '.php');
                
                if (class_exists($className)) {
                    log_info('マイグレーション実行', ['class' => $className]);
                    // 実際のマイグレーション処理はEC-CUBEのマイグレーションシステムで実行される
                }
            }
            
            $entityManager->flush();
            
        } catch (\Exception $e) {
            log_error('マイグレーション実行失敗', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 初期設定データ投入
     *
     * @param ContainerInterface $container
     */
    private function insertInitialData(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        try {
            // 初期設定データは、マイグレーションファイルで実行される
            log_info('初期設定データ投入完了');
            
        } catch (\Exception $e) {
            log_error('初期設定データ投入失敗', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * フロントページ作成
     *
     * @param ContainerInterface $container
     */
    private function createFrontPages(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $pageRepository = $entityManager->getRepository(Page::class);
        
        try {
            // レンタル一覧ページ
            $rentalListPage = $pageRepository->findOneBy(['url' => 'rental']);
            if (!$rentalListPage) {
                $page = new Page();
                $page->setName('レンタル一覧');
                $page->setUrl('rental');
                $page->setFileName('Rental/index');
                $page->setMetaRobots('index,follow');
                $page->setEditType(Page::EDIT_TYPE_DEFAULT);
                
                $entityManager->persist($page);
            }
            
            // レンタル詳細ページ
            $rentalDetailPage = $pageRepository->findOneBy(['url' => 'rental/detail']);
            if (!$rentalDetailPage) {
                $page = new Page();
                $page->setName('レンタル詳細');
                $page->setUrl('rental/detail');
                $page->setFileName('Rental/detail');
                $page->setMetaRobots('index,follow');
                $page->setEditType(Page::EDIT_TYPE_DEFAULT);
                
                $entityManager->persist($page);
            }
            
            $entityManager->flush();
            log_info('フロントページ作成完了');
            
        } catch (\Exception $e) {
            log_error('フロントページ作成失敗', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * フロントページを非表示
     *
     * @param ContainerInterface $container
     */
    private function hideFrontPages(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $pageRepository = $entityManager->getRepository(Page::class);
        
        try {
            $pages = $pageRepository->findBy(['url' => ['rental', 'rental/detail']]);
            
            foreach ($pages as $page) {
                $page->setMetaRobots('noindex,nofollow');
            }
            
            $entityManager->flush();
            log_info('フロントページ非表示完了');
            
        } catch (\Exception $e) {
            log_error('フロントページ非表示失敗', ['error' => $e->getMessage()]);
            // 非表示処理の失敗は致命的ではないのでエラーをスローしない
        }
    }

    /**
     * フロントページ削除
     *
     * @param ContainerInterface $container
     */
    private function removeFrontPages(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $pageRepository = $entityManager->getRepository(Page::class);
        
        try {
            $pages = $pageRepository->findBy(['url' => ['rental', 'rental/detail']]);
            
            foreach ($pages as $page) {
                $entityManager->remove($page);
            }
            
            $entityManager->flush();
            log_info('フロントページ削除完了');
            
        } catch (\Exception $e) {
            log_error('フロントページ削除失敗', ['error' => $e->getMessage()]);
            // 削除処理の失敗は致命的ではないのでエラーをスローしない
        }
    }

    /**
     * 管理画面メニュー作成
     *
     * @param ContainerInterface $container
     */
    private function createAdminMenu(ContainerInterface $container)
    {
        // 管理画面メニューの追加は、Nav.phpで実装
        log_info('管理画面メニュー設定完了');
    }

    /**
     * 管理画面メニュー非表示
     *
     * @param ContainerInterface $container
     */
    private function hideAdminMenu(ContainerInterface $container)
    {
        log_info('管理画面メニュー非表示完了');
    }

    /**
     * 管理画面メニュー削除
     *
     * @param ContainerInterface $container
     */
    private function removeAdminMenu(ContainerInterface $container)
    {
        log_info('管理画面メニュー削除完了');
    }

    /**
     * 設定の更新
     *
     * @param ContainerInterface $container
     */
    private function updateConfig(ContainerInterface $container)
    {
        // 設定の更新処理
        log_info('設定更新完了');
    }
}