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

namespace Plugin\Rental\Resource\doctrine\migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * レンタル管理プラグイン 住所・配送情報追加
 */
class Version20250430000000 extends AbstractMigration
{
    /**
     * マイグレーション実行
     *
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // レンタル注文テーブルに配送関連カラムを追加
        $rentalOrder = $schema->getTable('plg_rental_order');
        
        // 既存カラムチェック（冪等性確保）
        if ($rentalOrder->hasColumn('delivery_address')) {
            return;
        }

        // 配送先住所情報を追加
        $rentalOrder->addColumn('delivery_name01', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '配送先名前(姓)'
        ]);
        $rentalOrder->addColumn('delivery_name02', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '配送先名前(名)'
        ]);
        $rentalOrder->addColumn('delivery_kana01', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '配送先名前カナ(姓)'
        ]);
        $rentalOrder->addColumn('delivery_kana02', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '配送先名前カナ(名)'
        ]);
        $rentalOrder->addColumn('delivery_postal_code', 'string', [
            'length' => 8,
            'notnull' => false,
            'comment' => '配送先郵便番号'
        ]);
        $rentalOrder->addColumn('delivery_pref', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '配送先都道府県'
        ]);
        $rentalOrder->addColumn('delivery_addr01', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '配送先住所1'
        ]);
        $rentalOrder->addColumn('delivery_addr02', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '配送先住所2'
        ]);
        $rentalOrder->addColumn('delivery_phone', 'string', [
            'length' => 14,
            'notnull' => false,
            'comment' => '配送先電話番号'
        ]);
        
        // 配送時間・日付関連
        $rentalOrder->addColumn('delivery_date', 'datetime', [
            'notnull' => false,
            'comment' => '配送希望日'
        ]);
        $rentalOrder->addColumn('delivery_time', 'string', [
            'length' => 50,
            'notnull' => false,
            'comment' => '配送希望時間'
        ]);
        $rentalOrder->addColumn('delivery_fee', 'decimal', [
            'notnull' => false,
            'comment' => '配送料金'
        ]);
        $rentalOrder->addColumn('delivery_status', 'integer', [
            'notnull' => false,
            'default' => 1,
            'comment' => '配送ステータス'
        ]);
        $rentalOrder->addColumn('tracking_number', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '追跡番号'
        ]);
        
        // 返却関連
        $rentalOrder->addColumn('return_delivery_date', 'datetime', [
            'notnull' => false,
            'comment' => '返却配送日'
        ]);
        $rentalOrder->addColumn('return_tracking_number', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '返却追跡番号'
        ]);
        $rentalOrder->addColumn('return_delivery_fee', 'decimal', [
            'notnull' => false,
            'comment' => '返却配送料金'
        ]);
        
        // 商品状態関連
        $rentalOrder->addColumn('product_condition_notes', 'text', [
            'notnull' => false,
            'comment' => '商品状態メモ'
        ]);
        $rentalOrder->addColumn('damage_fee', 'decimal', [
            'notnull' => false,
            'comment' => '損害料金'
        ]);
        $rentalOrder->addColumn('cleaning_fee', 'decimal', [
            'notnull' => false,
            'comment' => 'クリーニング料金'
        ]);

        // インデックス追加
        $rentalOrder->addIndex(['delivery_date'], 'IDX_RENTAL_ORDER_DELIVERY_DATE');
        $rentalOrder->addIndex(['delivery_status'], 'IDX_RENTAL_ORDER_DELIVERY_STATUS');
        $rentalOrder->addIndex(['tracking_number'], 'IDX_RENTAL_ORDER_TRACKING');

        // 配送設定テーブルを新規作成
        $deliveryConfig = $schema->createTable('plg_rental_delivery_config');
        $deliveryConfig->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'comment' => '配送設定ID'
        ]);
        $deliveryConfig->addColumn('delivery_name', 'string', [
            'length' => 255,
            'notnull' => true,
            'comment' => '配送方法名'
        ]);
        $deliveryConfig->addColumn('delivery_fee', 'decimal', [
            'notnull' => true,
            'default' => 0,
            'comment' => '配送料金'
        ]);
        $deliveryConfig->addColumn('free_delivery_amount', 'decimal', [
            'notnull' => false,
            'comment' => '送料無料金額'
        ]);
        $deliveryConfig->addColumn('delivery_time_options', 'text', [
            'notnull' => false,
            'comment' => '配送時間オプション'
        ]);
        $deliveryConfig->addColumn('is_active', 'boolean', [
            'notnull' => true,
            'default' => true,
            'comment' => '有効フラグ'
        ]);
        $deliveryConfig->addColumn('sort_no', 'integer', [
            'notnull' => true,
            'default' => 0,
            'comment' => 'ソート順'
        ]);
        $deliveryConfig->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $deliveryConfig->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $deliveryConfig->setPrimaryKey(['id']);
        $deliveryConfig->addIndex(['is_active'], 'IDX_RENTAL_DELIVERY_ACTIVE');
        $deliveryConfig->addIndex(['sort_no'], 'IDX_RENTAL_DELIVERY_SORT');

        // 初期配送設定データの投入
        $this->addSql("INSERT INTO plg_rental_delivery_config (delivery_name, delivery_fee, delivery_time_options, is_active, sort_no, create_date, update_date) VALUES 
            ('宅配便', 500, '{\"午前中\":\"午前中\",\"14-16\":\"14時-16時\",\"16-18\":\"16時-18時\",\"18-20\":\"18時-20時\",\"20-21\":\"20時-21時\"}', 1, 1, datetime('now'), datetime('now')),
            ('メール便', 200, '{}', 1, 2, datetime('now'), datetime('now')),
            ('店舗受取', 0, '{}', 1, 3, datetime('now'), datetime('now'))
        ");

        // 配送関連設定を追加
        $this->addSql("INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES 
            ('default_delivery_fee', '500', 'デフォルト配送料金', datetime('now'), datetime('now')),
            ('free_delivery_amount', '5000', '送料無料金額', datetime('now'), datetime('now')),
            ('delivery_company', 'ヤマト運輸', '配送会社名', datetime('now'), datetime('now')),
            ('return_delivery_required', '1', '返却配送必須', datetime('now'), datetime('now')),
            ('auto_tracking', '0', '自動追跡機能', datetime('now'), datetime('now'))
        ");
    }

    /**
     * ロールバック処理
     *
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // 配送設定テーブル削除
        if ($schema->hasTable('plg_rental_delivery_config')) {
            $schema->dropTable('plg_rental_delivery_config');
        }

        // レンタル注文テーブルから配送関連カラムを削除
        $rentalOrder = $schema->getTable('plg_rental_order');
        
        $columnsToRemove = [
            'delivery_name01', 'delivery_name02', 'delivery_kana01', 'delivery_kana02',
            'delivery_postal_code', 'delivery_pref', 'delivery_addr01', 'delivery_addr02',
            'delivery_phone', 'delivery_date', 'delivery_time', 'delivery_fee',
            'delivery_status', 'tracking_number', 'return_delivery_date',
            'return_tracking_number', 'return_delivery_fee', 'product_condition_notes',
            'damage_fee', 'cleaning_fee'
        ];

        foreach ($columnsToRemove as $column) {
            if ($rentalOrder->hasColumn($column)) {
                $rentalOrder->dropColumn($column);
            }
        }

        // インデックス削除
        $indexesToRemove = [
            'IDX_RENTAL_ORDER_DELIVERY_DATE',
            'IDX_RENTAL_ORDER_DELIVERY_STATUS',
            'IDX_RENTAL_ORDER_TRACKING'
        ];

        foreach ($indexesToRemove as $index) {
            if ($rentalOrder->hasIndex($index)) {
                $rentalOrder->dropIndex($index);
            }
        }

        // 配送関連設定を削除
        $this->addSql("DELETE FROM plg_rental_config WHERE config_key IN ('default_delivery_fee', 'free_delivery_amount', 'delivery_company', 'return_delivery_required', 'auto_tracking')");
    }

    /**
     * マイグレーションの説明を取得
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'レンタル管理プラグイン - 住所・配送情報追加';
    }
}