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
 * レンタル管理プラグイン 基本テーブル作成
 */
class Version20250402000000 extends AbstractMigration
{
    /**
     * マイグレーション実行
     *
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // 既存テーブルチェック（冪等性確保）
        if ($schema->hasTable('plg_rental_config')) {
            return;
        }

        // 1. レンタル設定テーブル
        $rentalConfig = $schema->createTable('plg_rental_config');
        $rentalConfig->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'comment' => '設定ID'
        ]);
        $rentalConfig->addColumn('config_key', 'string', [
            'length' => 255,
            'notnull' => true,
            'comment' => '設定キー'
        ]);
        $rentalConfig->addColumn('config_value', 'text', [
            'notnull' => false,
            'comment' => '設定値'
        ]);
        $rentalConfig->addColumn('config_description', 'text', [
            'notnull' => false,
            'comment' => '設定説明'
        ]);
        $rentalConfig->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalConfig->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalConfig->setPrimaryKey(['id']);
        $rentalConfig->addUniqueIndex(['config_key'], 'UNIQ_RENTAL_CONFIG_KEY');

        // 2. レンタル商品設定テーブル
        $rentalProduct = $schema->createTable('plg_rental_product');
        $rentalProduct->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'comment' => 'レンタル商品設定ID'
        ]);
        $rentalProduct->addColumn('product_id', 'integer', [
            'notnull' => true,
            'comment' => '商品ID'
        ]);
        $rentalProduct->addColumn('daily_price', 'decimal', [
            'notnull' => false,
            'comment' => '日額料金'
        ]);
        $rentalProduct->addColumn('weekly_price', 'decimal', [
            'notnull' => false,
            'comment' => '週額料金'
        ]);
        $rentalProduct->addColumn('monthly_price', 'decimal', [
            'notnull' => false,
            'comment' => '月額料金'
        ]);
        $rentalProduct->addColumn('deposit_amount', 'decimal', [
            'notnull' => false,
            'comment' => '保証金額'
        ]);
        $rentalProduct->addColumn('max_rental_days', 'integer', [
            'notnull' => false,
            'comment' => '最大レンタル日数'
        ]);
        $rentalProduct->addColumn('min_rental_days', 'integer', [
            'notnull' => true,
            'default' => 1,
            'comment' => '最小レンタル日数'
        ]);
        $rentalProduct->addColumn('is_rental_enabled', 'boolean', [
            'notnull' => true,
            'default' => true,
            'comment' => 'レンタル有効フラグ'
        ]);
        $rentalProduct->addColumn('auto_approval', 'boolean', [
            'notnull' => true,
            'default' => false,
            'comment' => '自動承認フラグ'
        ]);
        $rentalProduct->addColumn('preparation_days', 'integer', [
            'notnull' => false,
            'comment' => '準備日数'
        ]);
        $rentalProduct->addColumn('rental_note', 'text', [
            'notnull' => false,
            'comment' => 'レンタル注意事項'
        ]);
        $rentalProduct->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalProduct->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalProduct->setPrimaryKey(['id']);
        $rentalProduct->addUniqueIndex(['product_id'], 'UNIQ_RENTAL_PRODUCT_ID');
        $rentalProduct->addIndex(['is_rental_enabled'], 'IDX_RENTAL_PRODUCT_ENABLED');

        // 3. レンタル注文テーブル
        $rentalOrder = $schema->createTable('plg_rental_order');
        $rentalOrder->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'comment' => 'レンタル注文ID'
        ]);
        $rentalOrder->addColumn('customer_id', 'integer', [
            'notnull' => true,
            'comment' => '顧客ID'
        ]);
        $rentalOrder->addColumn('rental_product_id', 'integer', [
            'notnull' => true,
            'comment' => 'レンタル商品設定ID'
        ]);
        $rentalOrder->addColumn('order_no', 'string', [
            'length' => 255,
            'notnull' => true,
            'comment' => '注文番号'
        ]);
        $rentalOrder->addColumn('rental_start_date', 'datetime', [
            'notnull' => true,
            'comment' => 'レンタル開始日'
        ]);
        $rentalOrder->addColumn('rental_end_date', 'datetime', [
            'notnull' => true,
            'comment' => 'レンタル終了日'
        ]);
        $rentalOrder->addColumn('actual_return_date', 'datetime', [
            'notnull' => false,
            'comment' => '実際の返却日'
        ]);
        $rentalOrder->addColumn('total_amount', 'decimal', [
            'notnull' => true,
            'comment' => '合計金額'
        ]);
        $rentalOrder->addColumn('deposit_amount', 'decimal', [
            'notnull' => false,
            'comment' => '保証金額'
        ]);
        $rentalOrder->addColumn('overdue_fee', 'decimal', [
            'notnull' => false,
            'comment' => '延滞料金'
        ]);
        $rentalOrder->addColumn('status', 'integer', [
            'notnull' => true,
            'default' => 1,
            'comment' => 'ステータス'
        ]);
        $rentalOrder->addColumn('quantity', 'integer', [
            'notnull' => true,
            'default' => 1,
            'comment' => '数量'
        ]);
        $rentalOrder->addColumn('note', 'text', [
            'notnull' => false,
            'comment' => '備考'
        ]);
        $rentalOrder->addColumn('admin_memo', 'text', [
            'notnull' => false,
            'comment' => '管理者メモ'
        ]);
        $rentalOrder->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalOrder->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalOrder->setPrimaryKey(['id']);
        $rentalOrder->addUniqueIndex(['order_no'], 'UNIQ_RENTAL_ORDER_NO');
        $rentalOrder->addIndex(['customer_id'], 'IDX_RENTAL_ORDER_CUSTOMER');
        $rentalOrder->addIndex(['rental_product_id'], 'IDX_RENTAL_ORDER_PRODUCT');
        $rentalOrder->addIndex(['status'], 'IDX_RENTAL_ORDER_STATUS');
        $rentalOrder->addIndex(['rental_start_date'], 'IDX_RENTAL_ORDER_START_DATE');
        $rentalOrder->addIndex(['rental_end_date'], 'IDX_RENTAL_ORDER_END_DATE');

        // 初期設定データの投入
        $this->addSql("INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES 
            ('auto_approval', '0', '自動承認設定', datetime('now'), datetime('now')),
            ('max_rental_days', '30', '最大レンタル日数', datetime('now'), datetime('now')),
            ('min_rental_days', '1', '最小レンタル日数', datetime('now'), datetime('now')),
            ('reminder_days', '3', 'リマインダー日数', datetime('now'), datetime('now')),
            ('overdue_fee_rate', '0.1', '延滞料金率', datetime('now'), datetime('now')),
            ('deposit_required', '0', '保証金必須設定', datetime('now'), datetime('now')),
            ('business_days', '1,2,3,4,5', '営業日設定', datetime('now'), datetime('now')),
            ('holiday_rental', '1', '休日レンタル許可', datetime('now'), datetime('now')),
            ('notification_email', '', '通知メールアドレス', datetime('now'), datetime('now')),
            ('terms_of_service', '', '利用規約', datetime('now'), datetime('now'))
        ");
    }

    /**
     * ロールバック処理
     *
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // テーブル削除（逆順で削除）
        if ($schema->hasTable('plg_rental_order')) {
            $schema->dropTable('plg_rental_order');
        }
        
        if ($schema->hasTable('plg_rental_product')) {
            $schema->dropTable('plg_rental_product');
        }
        
        if ($schema->hasTable('plg_rental_config')) {
            $schema->dropTable('plg_rental_config');
        }
    }

    /**
     * マイグレーションの説明を取得
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'レンタル管理プラグイン - 基本テーブル作成 (設定、商品設定、注文)';
    }
}