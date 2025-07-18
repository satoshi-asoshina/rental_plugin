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
 * レンタル管理プラグイン 拡張フィールド追加
 */
class Version20250502000000 extends AbstractMigration
{
    /**
     * マイグレーション実行
     *
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // 既存カラムチェック（冪等性確保）
        $rentalProduct = $schema->getTable('plg_rental_product');
        if ($rentalProduct->hasColumn('category_options')) {
            return;
        }

        // 1. レンタル商品テーブルに拡張フィールドを追加
        $rentalProduct->addColumn('category_options', 'text', [
            'notnull' => false,
            'comment' => 'カテゴリオプション設定'
        ]);
        $rentalProduct->addColumn('size_options', 'text', [
            'notnull' => false,
            'comment' => 'サイズオプション設定'
        ]);
        $rentalProduct->addColumn('color_options', 'text', [
            'notnull' => false,
            'comment' => 'カラーオプション設定'
        ]);
        $rentalProduct->addColumn('insurance_fee', 'decimal', [
            'notnull' => false,
            'comment' => '保険料金'
        ]);
        $rentalProduct->addColumn('early_return_discount', 'decimal', [
            'notnull' => false,
            'comment' => '早期返却割引率'
        ]);
        $rentalProduct->addColumn('extension_fee_rate', 'decimal', [
            'notnull' => false,
            'comment' => '延長料金率'
        ]);
        $rentalProduct->addColumn('replacement_fee', 'decimal', [
            'notnull' => false,
            'comment' => '交換料金'
        ]);
        $rentalProduct->addColumn('maintenance_cycle', 'integer', [
            'notnull' => false,
            'comment' => 'メンテナンス周期(日)'
        ]);
        $rentalProduct->addColumn('special_instructions', 'text', [
            'notnull' => false,
            'comment' => '特別な取扱い指示'
        ]);

        // 2. レンタル注文テーブルに拡張フィールドを追加
        $rentalOrder = $schema->getTable('plg_rental_order');
        $rentalOrder->addColumn('selected_options', 'text', [
            'notnull' => false,
            'comment' => '選択されたオプション'
        ]);
        $rentalOrder->addColumn('insurance_fee', 'decimal', [
            'notnull' => false,
            'comment' => '保険料金'
        ]);
        $rentalOrder->addColumn('extension_fee', 'decimal', [
            'notnull' => false,
            'comment' => '延長料金'
        ]);
        $rentalOrder->addColumn('early_return_discount', 'decimal', [
            'notnull' => false,
            'comment' => '早期返却割引'
        ]);
        $rentalOrder->addColumn('replacement_fee', 'decimal', [
            'notnull' => false,
            'comment' => '交換料金'
        ]);
        $rentalOrder->addColumn('payment_method', 'string', [
            'length' => 50,
            'notnull' => false,
            'comment' => '支払い方法'
        ]);
        $rentalOrder->addColumn('payment_status', 'integer', [
            'notnull' => false,
            'default' => 1,
            'comment' => '支払いステータス'
        ]);
        $rentalOrder->addColumn('payment_date', 'datetime', [
            'notnull' => false,
            'comment' => '支払い日時'
        ]);
        $rentalOrder->addColumn('refund_amount', 'decimal', [
            'notnull' => false,
            'comment' => '返金額'
        ]);
        $rentalOrder->addColumn('refund_date', 'datetime', [
            'notnull' => false,
            'comment' => '返金日時'
        ]);
        $rentalOrder->addColumn('contract_file', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '契約書ファイル'
        ]);
        $rentalOrder->addColumn('inspection_notes', 'text', [
            'notnull' => false,
            'comment' => '検品メモ'
        ]);
        $rentalOrder->addColumn('priority_level', 'integer', [
            'notnull' => false,
            'default' => 1,
            'comment' => '優先度レベル'
        ]);

        // 3. レンタル決済テーブルを新規作成
        $rentalPayment = $schema->createTable('plg_rental_payment');
        $rentalPayment->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'comment' => '決済ID'
        ]);
        $rentalPayment->addColumn('rental_order_id', 'integer', [
            'notnull' => true,
            'comment' => 'レンタル注文ID'
        ]);
        $rentalPayment->addColumn('payment_type', 'string', [
            'length' => 50,
            'notnull' => true,
            'comment' => '決済タイプ'
        ]);
        $rentalPayment->addColumn('payment_amount', 'decimal', [
            'notnull' => true,
            'comment' => '決済金額'
        ]);
        $rentalPayment->addColumn('payment_method', 'string', [
            'length' => 50,
            'notnull' => true,
            'comment' => '決済方法'
        ]);
        $rentalPayment->addColumn('payment_status', 'integer', [
            'notnull' => true,
            'default' => 1,
            'comment' => '決済ステータス'
        ]);
        $rentalPayment->addColumn('transaction_id', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => 'トランザクションID'
        ]);
        $rentalPayment->addColumn('payment_date', 'datetime', [
            'notnull' => false,
            'comment' => '決済日時'
        ]);
        $rentalPayment->addColumn('refund_amount', 'decimal', [
            'notnull' => false,
            'comment' => '返金額'
        ]);
        $rentalPayment->addColumn('refund_date', 'datetime', [
            'notnull' => false,
            'comment' => '返金日時'
        ]);
        $rentalPayment->addColumn('payment_details', 'text', [
            'notnull' => false,
            'comment' => '決済詳細'
        ]);
        $rentalPayment->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalPayment->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalPayment->setPrimaryKey(['id']);
        $rentalPayment->addIndex(['rental_order_id'], 'IDX_RENTAL_PAYMENT_ORDER');
        $rentalPayment->addIndex(['payment_type'], 'IDX_RENTAL_PAYMENT_TYPE');
        $rentalPayment->addIndex(['payment_status'], 'IDX_RENTAL_PAYMENT_STATUS');
        $rentalPayment->addIndex(['transaction_id'], 'IDX_RENTAL_PAYMENT_TRANSACTION');
        $rentalPayment->addIndex(['payment_date'], 'IDX_RENTAL_PAYMENT_DATE');

        // 4. レンタル拡張カートテーブルを新規作成
        $rentalCartExtension = $schema->createTable('plg_rental_cart_extension');
        $rentalCartExtension->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'comment' => 'カート拡張ID'
        ]);
        $rentalCartExtension->addColumn('rental_cart_id', 'integer', [
            'notnull' => true,
            'comment' => 'レンタルカートID'
        ]);
        $rentalCartExtension->addColumn('extension_type', 'string', [
            'length' => 50,
            'notnull' => true,
            'comment' => '拡張タイプ'
        ]);
        $rentalCartExtension->addColumn('extension_value', 'text', [
            'notnull' => false,
            'comment' => '拡張値'
        ]);
        $rentalCartExtension->addColumn('additional_fee', 'decimal', [
            'notnull' => false,
            'comment' => '追加料金'
        ]);
        $rentalCartExtension->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalCartExtension->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalCartExtension->setPrimaryKey(['id']);
        $rentalCartExtension->addIndex(['rental_cart_id'], 'IDX_RENTAL_CART_EXT_CART');
        $rentalCartExtension->addIndex(['extension_type'], 'IDX_RENTAL_CART_EXT_TYPE');

        // インデックス追加
        $rentalOrder->addIndex(['payment_status'], 'IDX_RENTAL_ORDER_PAYMENT_STATUS');
        $rentalOrder->addIndex(['payment_date'], 'IDX_RENTAL_ORDER_PAYMENT_DATE');
        $rentalOrder->addIndex(['priority_level'], 'IDX_RENTAL_ORDER_PRIORITY');

        // 拡張設定を追加
        $this->addSql("INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES 
            ('insurance_enabled', '1', '保険機能有効', datetime('now'), datetime('now')),
            ('early_return_discount_enabled', '1', '早期返却割引有効', datetime('now'), datetime('now')),
            ('extension_enabled', '1', '延長機能有効', datetime('now'), datetime('now')),
            ('replacement_enabled', '1', '交換機能有効', datetime('now'), datetime('now')),
            ('contract_required', '0', '契約書必須', datetime('now'), datetime('now')),
            ('inspection_required', '1', '検品必須', datetime('now'), datetime('now')),
            ('priority_management', '1', '優先度管理有効', datetime('now'), datetime('now')),
            ('payment_methods', '{\"credit\":\"クレジットカード\",\"bank\":\"銀行振込\",\"convenience\":\"コンビニ決済\",\"cash\":\"現金\"}', '利用可能決済方法', datetime('now'), datetime('now')),
            ('refund_policy', '7', '返金ポリシー(日数)', datetime('now'), datetime('now')),
            ('default_priority_level', '1', 'デフォルト優先度レベル', datetime('now'), datetime('now'))
        ");

        // 決済ステータスのマスターデータ追加
        $this->addSql("INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES 
            ('payment_status_names', '{\"1\":\"未払い\",\"2\":\"支払い済み\",\"3\":\"部分支払い\",\"4\":\"返金済み\",\"5\":\"キャンセル\"}', '決済ステータス名', datetime('now'), datetime('now'))
        ");
    }

    /**
     * ロールバック処理
     *
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // 新規作成したテーブルを削除
        if ($schema->hasTable('plg_rental_cart_extension')) {
            $schema->dropTable('plg_rental_cart_extension');
        }
        
        if ($schema->hasTable('plg_rental_payment')) {
            $schema->dropTable('plg_rental_payment');
        }

        // レンタル商品テーブルから拡張フィールドを削除
        $rentalProduct = $schema->getTable('plg_rental_product');
        $productColumnsToRemove = [
            'category_options', 'size_options', 'color_options', 'insurance_fee',
            'early_return_discount', 'extension_fee_rate', 'replacement_fee',
            'maintenance_cycle', 'special_instructions'
        ];

        foreach ($productColumnsToRemove as $column) {
            if ($rentalProduct->hasColumn($column)) {
                $rentalProduct->dropColumn($column);
            }
        }

        // レンタル注文テーブルから拡張フィールドを削除
        $rentalOrder = $schema->getTable('plg_rental_order');
        $orderColumnsToRemove = [
            'selected_options', 'insurance_fee', 'extension_fee', 'early_return_discount',
            'replacement_fee', 'payment_method', 'payment_status', 'payment_date',
            'refund_amount', 'refund_date', 'contract_file', 'inspection_notes',
            'priority_level'
        ];

        foreach ($orderColumnsToRemove as $column) {
            if ($rentalOrder->hasColumn($column)) {
                $rentalOrder->dropColumn($column);
            }
        }

        // インデックス削除
        $indexesToRemove = [
            'IDX_RENTAL_ORDER_PAYMENT_STATUS',
            'IDX_RENTAL_ORDER_PAYMENT_DATE',
            'IDX_RENTAL_ORDER_PRIORITY'
        ];

        foreach ($indexesToRemove as $index) {
            if ($rentalOrder->hasIndex($index)) {
                $rentalOrder->dropIndex($index);
            }
        }

        // 拡張設定を削除
        $this->addSql("DELETE FROM plg_rental_config WHERE config_key IN ('insurance_enabled', 'early_return_discount_enabled', 'extension_enabled', 'replacement_enabled', 'contract_required', 'inspection_required', 'priority_management', 'payment_methods', 'refund_policy', 'default_priority_level', 'payment_status_names')");
    }

    /**
     * マイグレーションの説明を取得
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'レンタル管理プラグイン - 拡張フィールド・決済機能追加';
    }
}