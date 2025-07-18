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
use Symfony\Component\DependencyInjection\ContainerInterface;
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
        // データベースマイグレーション実行
        $this->executeMigration($container);
        
        // 初期設定データ投入
        $this->insertInitialData($container);
        
        // フロントページ作成
        $this->createFrontPages($container);
        
        // テンプレートファイルコピー
        $this->copyTemplateFiles();
    }

    /**
     * プラグイン無効化時の処理
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        // ページを非表示にする（削除はしない）
        $this->hideFrontPages($container);
    }

    /**
     * プラグインアンインストール時の処理
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        // フロントページ削除
        $this->removeFrontPages($container);
        
        // テンプレートファイル削除
        $this->removeTemplateFiles();
        
        // 注意: データベースのテーブルは削除しない（データ保護のため）
        // 必要に応じて手動でテーブル削除を行ってください
    }

    /**
     * プラグインアップデート時の処理
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta, ContainerInterface $container)
    {
        // バージョンアップ時のマイグレーション実行
        $this->executeMigration($container);
        
        // テンプレートファイル更新
        $this->copyTemplateFiles();
    }

    /**
     * データベースマイグレーション実行
     *
     * @param ContainerInterface $container
     */
    private function executeMigration(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $migrationService = $container->get('Eccube\Service\SchemaService');

        try {
            // マイグレーション実行
            $migrationService->createSchema();
            $entityManager->flush();
        } catch (\Exception $e) {
            // エラーログ出力
            $logger = $container->get('monolog.logger.plugin');
            $logger->error('レンタルプラグイン マイグレーション失敗: ' . $e->getMessage());
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
        
        // 既存データチェック
        $existingConfig = $entityManager->getRepository('Plugin\Rental\Entity\RentalConfig')
            ->findOneBy(['config_key' => 'auto_approval']);
            
        if ($existingConfig) {
            return; // 既に初期データが存在する場合はスキップ
        }

        // 初期設定データ
        $initialConfigs = [
            ['config_key' => 'auto_approval', 'config_value' => '0'],
            ['config_key' => 'max_rental_days', 'config_value' => '30'],
            ['config_key' => 'min_rental_days', 'config_value' => '1'],
            ['config_key' => 'reminder_days', 'config_value' => '3'],
            ['config_key' => 'overdue_fee_rate', 'config_value' => '0.1'],
            ['config_key' => 'deposit_required', 'config_value' => '0'],
        ];

        try {
            foreach ($initialConfigs as $configData) {
                $config = new \Plugin\Rental\Entity\RentalConfig();
                $config->setConfigKey($configData['config_key']);
                $config->setConfigValue($configData['config_value']);
                $config->setCreateDate(new \DateTime());
                $config->setUpdateDate(new \DateTime());
                
                $entityManager->persist($config);
            }
            
            $entityManager->flush();
        } catch (\Exception $e) {
            $logger = $container->get('monolog.logger.plugin');
            $logger->error('レンタルプラグイン 初期データ投入失敗: ' . $e->getMessage());
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
        $layoutRepository = $entityManager->getRepository(Layout::class);

        // デフォルトレイアウト取得
        $layout = $layoutRepository->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);

        $pages = [
            [
                'name' => 'レンタル注文',
                'url' => 'rental_order',
                'file_name' => 'Rental/default/input',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
            ],
            [
                'name' => 'レンタル注文確認',
                'url' => 'rental_order_confirm',
                'file_name' => 'Rental/default/confirm',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
            ],
            [
                'name' => 'レンタル注文完了',
                'url' => 'rental_order_complete',
                'file_name' => 'Rental/default/complete',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
            ],
        ];

        foreach ($pages as $pageData) {
            // 既存ページチェック
            $existingPage = $pageRepository->findOneBy(['url' => $pageData['url']]);
            if ($existingPage) {
                continue; // 既に存在する場合はスキップ
            }

            $page = new Page();
            $page->setName($pageData['name']);
            $page->setUrl($pageData['url']);
            $page->setFileName($pageData['file_name']);
            $page->setEditType($pageData['edit_type']);
            $page->setCreateDate(new \DateTime());
            $page->setUpdateDate(new \DateTime());

            $entityManager->persist($page);

            // ページレイアウト関連付け
            $pageLayout = new PageLayout();
            $pageLayout->setPage($page);
            $pageLayout->setPageId($page->getId());
            $pageLayout->setLayout($layout);
            $pageLayout->setLayoutId($layout->getId());
            $pageLayout->setSortNo(1);

            $entityManager->persist($pageLayout);
        }

        $entityManager->flush();
    }

    /**
     * フロントページを非表示にする
     *
     * @param ContainerInterface $container
     */
    private function hideFrontPages(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $pageRepository = $entityManager->getRepository(Page::class);

        $urls = ['rental_order', 'rental_order_confirm', 'rental_order_complete'];

        foreach ($urls as $url) {
            $page = $pageRepository->findOneBy(['url' => $url]);
            if ($page) {
                // ページを非表示にする（削除はしない）
                $page->setEditType(Page::EDIT_TYPE_PREVIEW);
                $entityManager->persist($page);
            }
        }

        $entityManager->flush();
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
        $pageLayoutRepository = $entityManager->getRepository(PageLayout::class);

        $urls = ['rental_order', 'rental_order_confirm', 'rental_order_complete'];

        foreach ($urls as $url) {
            $page = $pageRepository->findOneBy(['url' => $url]);
            if ($page) {
                // ページレイアウト削除
                $pageLayouts = $pageLayoutRepository->findBy(['Page' => $page]);
                foreach ($pageLayouts as $pageLayout) {
                    $entityManager->remove($pageLayout);
                }
                
                // ページ削除
                $entityManager->remove($page);
            }
        }

        $entityManager->flush();
    }

    /**
     * テンプレートファイルコピー
     */
    private function copyTemplateFiles()
    {
        $filesystem = new Filesystem();
        
        // コピー元とコピー先のパス設定
        $sourceDir = __DIR__ . '/Resource/template';
        $targetDir = __DIR__ . '/../../../app/template';
        
        // ディレクトリが存在しない場合は作成
        if (!$filesystem->exists($targetDir)) {
            $filesystem->mkdir($targetDir);
        }

        // テンプレートファイルコピー（必要に応じて）
        // 現在は基本的なファイル構成のみなので、後のフェーズで実装
    }

    /**
     * テンプレートファイル削除
     */
    private function removeTemplateFiles()
    {
        $filesystem = new Filesystem();
        
        // 削除対象ファイル（必要に応じて設定）
        $filesToRemove = [
            // 後のフェーズで実装
        ];

        foreach ($filesToRemove as $file) {
            if ($filesystem->exists($file)) {
                $filesystem->remove($file);
            }
        }
    }
}