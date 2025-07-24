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
        $this->createRentalConfigTable($schema);
        
        // 2. レンタル商品設定テーブル
        $this->createRentalProductTable($schema);
        
        // 3. レンタル注文テーブル
        $this->createRentalOrderTable($schema);
        
        // 4. レンタルカートテーブル
        $this->createRentalCartTable($schema);
        
        // 5. レンタルログテーブル
        $this->createRentalLogTable($schema);
    }

    /**
     * レンタル設定テーブル作成
     *
     * @param Schema $schema
     */
    private function createRentalConfigTable(Schema $schema): void
    {
        $table = $schema->createTable('plg_rental_config');
        
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
            'comment' => '設定ID'
        ]);
        
        $table->addColumn('config_key', 'string', [
            'length' => 255,
            'notnull' => true,
            'comment' => '設定キー'
        ]);
        
        $table->addColumn('config_value', 'text', [
            'notnull' => false,
            'comment' => '設定値'
        ]);
        
        $table->addColumn('config_description', 'text', [
            'notnull' => false,
            'comment' => '設定説明'
        ]);
        
        $table->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        
        $table->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['config_key'], 'UNIQ_RENTAL_CONFIG_KEY');
    }

    /**
     * レンタル商品設定テーブル作成
     *
     * @param Schema $schema
     */
    private function createRentalProductTable(Schema $schema): void
    {
        $table = $schema->createTable('plg_rental_product');
        
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'レンタル商品設定ID'
        ]);
        
        $table->addColumn('product_id', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'comment' => '商品ID'
        ]);
        
        $table->addColumn('rental_enabled', 'boolean', [
            'notnull' => true,
            'default' => false,
            'comment' => 'レンタル可能フラグ'
        ]);
        
        $table->addColumn('daily_rate', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'comment' => '日額レンタル料金'
        ]);
        
        $table->addColumn('weekly_rate', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'comment' => '週額レンタル料金'
        ]);
        
        $table->addColumn('monthly_rate', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'comment' => '月額レンタル料金'
        ]);
        
        $table->addColumn('max_rental_days', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'default' => 30,
            'comment' => '最大レンタル日数'
        ]);
        
        $table->addColumn('min_rental_days', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'default' => 1,
            'comment' => '最小レンタル日数'
        ]);
        
        $table->addColumn('stock_quantity', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'default' => 0,
            'comment' => 'レンタル在庫数'
        ]);
        
        $table->addColumn('deposit_amount', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'default' => '0.00',
            'comment' => '保証金額'
        ]);
        
        $table->addColumn('terms_of_service', 'text', [
            'notnull' => false,
            'comment' => 'レンタル利用規約'
        ]);
        
        $table->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        
        $table->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id'], 'UNIQ_RENTAL_PRODUCT_ID');
        $table->addIndex(['rental_enabled'], 'IDX_RENTAL_PRODUCT_ENABLED');
        $table->addIndex(['product_id', 'rental_enabled'], 'IDX_RENTAL_PRODUCT_SEARCH');
    }

    /**
     * レンタル注文テーブル作成
     *
     * @param Schema $schema
     */
    private function createRentalOrderTable(Schema $schema): void
    {
        $table = $schema->createTable('plg_rental_order');
        
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'レンタル注文ID'
        ]);
        
        $table->addColumn('order_id', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'comment' => 'EC-CUBE注文ID'
        ]);
        
        $table->addColumn('customer_id', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'comment' => '顧客ID'
        ]);
        
        $table->addColumn('rental_product_id', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'レンタル商品設定ID'
        ]);
        
        $table->addColumn('rental_code', 'string', [
            'length' => 50,
            'notnull' => true,
            'comment' => 'レンタル注文番号'
        ]);
        
        $table->addColumn('quantity', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'default' => 1,
            'comment' => 'レンタル数量'
        ]);
        
        $table->addColumn('rental_start_date', 'date', [
            'notnull' => true,
            'comment' => 'レンタル開始日'
        ]);
        
        $table->addColumn('rental_end_date', 'date', [
            'notnull' => true,
            'comment' => 'レンタル終了日'
        ]);
        
        $table->addColumn('actual_return_date', 'date', [
            'notnull' => false,
            'comment' => '実際の返却日'
        ]);
        
        $table->addColumn('rental_fee', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => true,
            'comment' => 'レンタル料金'
        ]);
        
        $table->addColumn('deposit_fee', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'default' => '0.00',
            'comment' => '保証金'
        ]);
        
        $table->addColumn('overdue_fee', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'default' => '0.00',
            'comment' => '延滞料金'
        ]);
        
        $table->addColumn('total_amount', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => true,
            'comment' => '合計金額'
        ]);
        
        $table->addColumn('status', 'string', [
            'length' => 20,
            'notnull' => true,
            'default' => 'reserved',
            'comment' => 'ステータス(reserved/renting/returned/overdue/cancelled)'
        ]);
        
        $table->addColumn('customer_name', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '顧客名'
        ]);
        
        $table->addColumn('customer_email', 'string', [
            'length' => 255,
            'notnull' => false,
            'comment' => '顧客メールアドレス'
        ]);
        
        $table->addColumn('customer_phone', 'string', [
            'length' => 20,
            'notnull' => false,
            'comment' => '顧客電話番号'
        ]);
        
        $table->addColumn('delivery_address', 'text', [
            'notnull' => false,
            'comment' => '配送先住所'
        ]);
        
        $table->addColumn('notes', 'text', [
            'notnull' => false,
            'comment' => '備考'
        ]);
        
        $table->addColumn('reminder_sent', 'boolean', [
            'notnull' => true,
            'default' => false,
            'comment' => 'リマインダー送信済みフラグ'
        ]);
        
        $table->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        
        $table->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['rental_code'], 'UNIQ_RENTAL_ORDER_CODE');
        $table->addIndex(['order_id'], 'IDX_RENTAL_ORDER_ORDER_ID');
        $table->addIndex(['customer_id'], 'IDX_RENTAL_ORDER_CUSTOMER_ID');
        $table->addIndex(['rental_product_id'], 'IDX_RENTAL_ORDER_PRODUCT_ID');
        $table->addIndex(['status'], 'IDX_RENTAL_ORDER_STATUS');
        $table->addIndex(['rental_start_date'], 'IDX_RENTAL_ORDER_START_DATE');
        $table->addIndex(['rental_end_date'], 'IDX_RENTAL_ORDER_END_DATE');
        
        // 複合インデックス（パフォーマンス最適化）
        $table->addIndex(['customer_id', 'status'], 'IDX_RENTAL_ORDER_CUSTOMER_STATUS');
        $table->addIndex(['rental_start_date', 'rental_end_date'], 'IDX_RENTAL_ORDER_PERIOD');
        $table->addIndex(['status', 'rental_end_date'], 'IDX_RENTAL_ORDER_OVERDUE_CHECK');
        $table->addIndex(['rental_product_id', 'rental_start_date', 'rental_end_date'], 'IDX_RENTAL_ORDER_PRODUCT_PERIOD');
    }

    /**
     * レンタルカートテーブル作成
     *
     * @param Schema $schema
     */
    private function createRentalCartTable(Schema $schema): void
    {
        $table = $schema->createTable('plg_rental_cart');
        
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'レンタルカートID'
        ]);
        
        $table->addColumn('session_id', 'string', [
            'length' => 128,
            'notnull' => false,
            'comment' => 'セッションID'
        ]);
        
        $table->addColumn('customer_id', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'comment' => '顧客ID'
        ]);
        
        $table->addColumn('product_id', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'comment' => '商品ID'
        ]);
        
        $table->addColumn('quantity', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'default' => 1,
            'comment' => 'レンタル数量'
        ]);
        
        $table->addColumn('rental_start_date', 'date', [
            'notnull' => true,
            'comment' => 'レンタル開始日'
        ]);
        
        $table->addColumn('rental_end_date', 'date', [
            'notnull' => true,
            'comment' => 'レンタル終了日'
        ]);
        
        $table->addColumn('rental_fee', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => true,
            'comment' => 'レンタル料金'
        ]);
        
        $table->addColumn('deposit_fee', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'notnull' => false,
            'default' => '0.00',
            'comment' => '保証金'
        ]);
        
        $table->addColumn('options', 'text', [
            'notnull' => false,
            'comment' => 'オプション情報（JSON）'
        ]);
        
        $table->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        
        $table->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        
        $table->setPrimaryKey(['id']);
        $table->addIndex(['session_id'], 'IDX_RENTAL_CART_SESSION_ID');
        $table->addIndex(['customer_id'], 'IDX_RENTAL_CART_CUSTOMER_ID');
        $table->addIndex(['product_id'], 'IDX_RENTAL_CART_PRODUCT_ID');
        $table->addIndex(['create_date'], 'IDX_RENTAL_CART_CREATE_DATE');
        
        // 複合インデックス
        $table->addIndex(['session_id', 'customer_id'], 'IDX_RENTAL_CART_SESSION_CUSTOMER');
        $table->addIndex(['product_id', 'rental_start_date', 'rental_end_date'], 'IDX_RENTAL_CART_PRODUCT_PERIOD');
    }

    /**
     * レンタルログテーブル作成
     *
     * @param Schema $schema
     */
    private function createRentalLogTable(Schema $schema): void
    {
        $table = $schema->createTable('plg_rental_log');
        
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'ログID'
        ]);
        
        $table->addColumn('rental_order_id', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'comment' => 'レンタル注文ID'
        ]);
        
        $table->addColumn('log_type', 'string', [
            'length' => 50,
            'notnull' => true,
            'comment' => 'ログタイプ'
        ]);
        
        $table->addColumn('log_level', 'string', [
            'length' => 20,
            'notnull' => true,
            'default' => 'INFO',
            'comment' => 'ログレベル'
        ]);
        
        $table->addColumn('message', 'text', [
            'notnull' => true,
            'comment' => 'ログメッセージ'
        ]);
        
        $table->addColumn('context', 'text', [
            'notnull' => false,
            'comment' => 'コンテキスト情報（JSON）'
        ]);
        
        $table->addColumn('user_id', 'integer', [
            'notnull' => false,
            'unsigned' => true,
            'comment' => 'ユーザーID'
        ]);
        
        $table->addColumn('ip_address', 'string', [
            'length' => 45,
            'notnull' => false,
            'comment' => 'IPアドレス'
        ]);
        
        $table->addColumn('user_agent', 'text', [
            'notnull' => false,
            'comment' => 'ユーザーエージェント'
        ]);
        
        $table->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        
        $table->setPrimaryKey(['id']);
        $table->addIndex(['rental_order_id'], 'IDX_RENTAL_LOG_ORDER_ID');
        $table->addIndex(['log_type'], 'IDX_RENTAL_LOG_TYPE');
        $table->addIndex(['log_level'], 'IDX_RENTAL_LOG_LEVEL');
        $table->addIndex(['create_date'], 'IDX_RENTAL_LOG_CREATE_DATE');
        
        // 複合インデックス
        $table->addIndex(['create_date', 'log_level'], 'IDX_RENTAL_LOG_DATE_LEVEL');
        $table->addIndex(['rental_order_id', 'log_type'], 'IDX_RENTAL_LOG_ORDER_TYPE');
        $table->addIndex(['user_id', 'create_date'], 'IDX_RENTAL_LOG_USER_DATE');
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