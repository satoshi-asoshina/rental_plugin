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
 * レンタル管理プラグイン 基本テーブル作成（MySQL対応版）
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
            'unsigned' => true,
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
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalConfig->setPrimaryKey(['id']);
        $rentalConfig->addUniqueIndex(['config_key'], 'UNIQ_RENTAL_CONFIG_KEY');

        // 2. レンタル商品設定テーブル
        $rentalProduct = $schema->createTable('plg_rental_product');
        $rentalProduct->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'レンタル商品設定ID'
        ]);
        $rentalProduct->addColumn('product_id', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'comment' => '商品ID'
        ]);
        $rentalProduct->addColumn('rental_enabled', 'boolean', [
            'notnull' => true,
            'default' => false,
            'comment' => 'レンタル可能フラグ'
        ]);
        $rentalProduct->addColumn('daily_rate', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'comment' => '日額レンタル料金'
        ]);
        $rentalProduct->addColumn('weekly_rate', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'comment' => '週額レンタル料金'
        ]);
        $rentalProduct->addColumn('monthly_rate', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'comment' => '月額レンタル料金'
        ]);
        $rentalProduct->addColumn('max_rental_days', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'default' => 30,
            'comment' => '最大レンタル日数'
        ]);
        $rentalProduct->addColumn('min_rental_days', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'default' => 1,
            'comment' => '最小レンタル日数'
        ]);
        $rentalProduct->addColumn('stock_quantity', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'default' => 0,
            'comment' => 'レンタル在庫数'
        ]);
        $rentalProduct->addColumn('deposit_amount', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'default' => '0.00',
            'comment' => '保証金額'
        ]);
        $rentalProduct->addColumn('terms_of_service', 'text', [
            'notnull' => false,
            'comment' => 'レンタル利用規約'
        ]);
        $rentalProduct->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalProduct->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalProduct->setPrimaryKey(['id']);
        $rentalProduct->addUniqueIndex(['product_id'], 'UNIQ_RENTAL_PRODUCT_ID');
        $rentalProduct->addIndex(['rental_enabled'], 'IDX_RENTAL_PRODUCT_ENABLED');

        // 3. レンタル注文テーブル
        $rentalOrder = $schema->createTable('plg_rental_order');
        $rentalOrder->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'レンタル注文ID'
        ]);
        $rentalOrder->addColumn('order_id', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'comment' => 'EC-CUBE注文ID'
        ]);
        $rentalOrder->addColumn('customer_id', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'comment' => '顧客ID'
        ]);
        $rentalOrder->addColumn('rental_product_id', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'レンタル商品設定ID'
        ]);
        $rentalOrder->addColumn('rental_code', 'string', [
            'length' => 50,
            'notnull' => true,
            'comment' => 'レンタル注文番号'
        ]);
        $rentalOrder->addColumn('quantity', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'default' => 1,
            'comment' => 'レンタル数量'
        ]);
        $rentalOrder->addColumn('rental_start_date', 'date', [
            'notnull' => true,
            'comment' => 'レンタル開始日'
        ]);
        $rentalOrder->addColumn('rental_end_date', 'date', [
            'notnull' => true,
            'comment' => 'レンタル終了日'
        ]);
        $rentalOrder->addColumn('actual_return_date', 'date', [
            'notnull' => false,
            'comment' => '実際の返却日'
        ]);
        $rentalOrder->addColumn('rental_fee', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => true,
            'comment' => 'レンタル料金'
        ]);
        $rentalOrder->addColumn('deposit_fee', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'default' => '0.00',
            'comment' => '保証金'
        ]);
        $rentalOrder->addColumn('overdue_fee', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'default' => '0.00',
            'comment' => '延滞料金'
        ]);
        $rentalOrder->addColumn('total_amount', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => true,
            'comment' => '合計金額'
        ]);
        $rentalOrder->addColumn('status', 'string', [
            'length' => 20,
            'notnull' => true,
            'default' => 'reserved',
            'comment' => 'ステータス(reserved/renting/returned/overdue/cancelled)'
        ]);
        $rentalOrder->addColumn('customer_name', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '顧客名'
        ]);
        $rentalOrder->addColumn('customer_email', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '顧客メールアドレス'
        ]);
        $rentalOrder->addColumn('customer_phone', 'string', [
            'length' => 20,
            'notnull' => false,
            'comment' => '顧客電話番号'
        ]);
        $rentalOrder->addColumn('delivery_address', 'text', [
            'notnull' => false,
            'comment' => '配送先住所'
        ]);
        $rentalOrder->addColumn('notes', 'text', [
            'notnull' => false,
            'comment' => '備考'
        ]);
        $rentalOrder->addColumn('reminder_sent', 'boolean', [
            'notnull' => true,
            'default' => false,
            'comment' => 'リマインダー送信済みフラグ'
        ]);
        $rentalOrder->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalOrder->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalOrder->setPrimaryKey(['id']);
        $rentalOrder->addUniqueIndex(['rental_code'], 'UNIQ_RENTAL_ORDER_CODE');
        $rentalOrder->addIndex(['order_id'], 'IDX_RENTAL_ORDER_ORDER_ID');
        $rentalOrder->addIndex(['customer_id'], 'IDX_RENTAL_ORDER_CUSTOMER_ID');
        $rentalOrder->addIndex(['rental_product_id'], 'IDX_RENTAL_ORDER_PRODUCT_ID');
        $rentalOrder->addIndex(['status'], 'IDX_RENTAL_ORDER_STATUS');
        $rentalOrder->addIndex(['rental_start_date'], 'IDX_RENTAL_ORDER_START_DATE');
        $rentalOrder->addIndex(['rental_end_date'], 'IDX_RENTAL_ORDER_END_DATE');

        // 4. レンタルカートテーブル
        $rentalCart = $schema->createTable('plg_rental_cart');
        $rentalCart->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'レンタルカートID'
        ]);
        $rentalCart->addColumn('session_id', 'string', [
            'length' => 128,
            'notnull' => false,
            'comment' => 'セッションID'
        ]);
        $rentalCart->addColumn('customer_id', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'comment' => '顧客ID'
        ]);
        $rentalCart->addColumn('product_id', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'comment' => '商品ID'
        ]);
        $rentalCart->addColumn('quantity', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'default' => 1,
            'comment' => 'レンタル数量'
        ]);
        $rentalCart->addColumn('rental_start_date', 'date', [
            'notnull' => true,
            'comment' => 'レンタル開始日'
        ]);
        $rentalCart->addColumn('rental_end_date', 'date', [
            'notnull' => true,
            'comment' => 'レンタル終了日'
        ]);
        $rentalCart->addColumn('rental_fee', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => true,
            'comment' => 'レンタル料金'
        ]);
        $rentalCart->addColumn('deposit_fee', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'default' => '0.00',
            'comment' => '保証金'
        ]);
        $rentalCart->addColumn('options', 'text', [
            'notnull' => false,
            'comment' => 'オプション情報（JSON）'
        ]);
        $rentalCart->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalCart->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalCart->setPrimaryKey(['id']);
        $rentalCart->addIndex(['session_id'], 'IDX_RENTAL_CART_SESSION_ID');
        $rentalCart->addIndex(['customer_id'], 'IDX_RENTAL_CART_CUSTOMER_ID');
        $rentalCart->addIndex(['product_id'], 'IDX_RENTAL_CART_PRODUCT_ID');

        // 5. レンタルログテーブル
        $rentalLog = $schema->createTable('plg_rental_log');
        $rentalLog->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'ログID'
        ]);
        $rentalLog->addColumn('rental_order_id', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'comment' => 'レンタル注文ID'
        ]);
        $rentalLog->addColumn('log_type', 'string', [
            'length' => 50,
            'notnull' => true,
            'comment' => 'ログタイプ'
        ]);
        $rentalLog->addColumn('log_level', 'string', [
            'length' => 20,
            'notnull' => true,
            'default' => 'INFO',
            'comment' => 'ログレベル'
        ]);
        $rentalLog->addColumn('message', 'text', [
            'notnull' => true,
            'comment' => 'ログメッセージ'
        ]);
        $rentalLog->addColumn('context', 'text', [
            'notnull' => false,
            'comment' => 'コンテキスト情報（JSON）'
        ]);
        $rentalLog->addColumn('user_id', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'comment' => 'ユーザーID'
        ]);
        $rentalLog->addColumn('ip_address', 'string', [
            'length' => 45,
            'notnull' => false,
            'comment' => 'IPアドレス'
        ]);
        $rentalLog->addColumn('user_agent', 'text', [
            'notnull' => false,
            'comment' => 'ユーザーエージェント'
        ]);
        $rentalLog->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalLog->setPrimaryKey(['id']);
        $rentalLog->addIndex(['rental_order_id'], 'IDX_RENTAL_LOG_ORDER_ID');
        $rentalLog->addIndex(['log_type'], 'IDX_RENTAL_LOG_TYPE');
        $rentalLog->addIndex(['log_level'], 'IDX_RENTAL_LOG_LEVEL');
        $rentalLog->addIndex(['create_date'], 'IDX_RENTAL_LOG_CREATE_DATE');
    }

    /**
     * マイグレーション取り消し
     *
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // テーブル削除（逆順）
        if ($schema->hasTable('plg_rental_log')) {
            $schema->dropTable('plg_rental_log');
        }
        if ($schema->hasTable('plg_rental_cart')) {
            $schema->dropTable('plg_rental_cart');
        }
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
}