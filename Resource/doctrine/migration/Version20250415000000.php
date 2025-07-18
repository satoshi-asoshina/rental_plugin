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
 * レンタル管理プラグイン カート・在庫テーブル作成
 */
class Version20250415000000 extends AbstractMigration
{
    /**
     * マイグレーション実行
     *
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // 既存テーブルチェック（冪等性確保）
        if ($schema->hasTable('plg_rental_cart')) {
            return;
        }

        // 1. レンタルカートテーブル
        $rentalCart = $schema->createTable('plg_rental_cart');
        $rentalCart->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'comment' => 'レンタルカートID'
        ]);
        $rentalCart->addColumn('customer_id', 'integer', [
            'notnull' => false,
            'comment' => '顧客ID'
        ]);
        $rentalCart->addColumn('product_id', 'integer', [
            'notnull' => true,
            'comment' => '商品ID'
        ]);
        $rentalCart->addColumn('session_id', 'string', [
            'length' => 255,
            'notnull' => true,
            'comment' => 'セッションID'
        ]);
        $rentalCart->addColumn('quantity', 'integer', [
            'notnull' => true,
            'default' => 1,
            'comment' => '数量'
        ]);
        $rentalCart->addColumn('rental_start_date', 'datetime', [
            'notnull' => true,
            'comment' => 'レンタル開始日'
        ]);
        $rentalCart->addColumn('rental_end_date', 'datetime', [
            'notnull' => true,
            'comment' => 'レンタル終了日'
        ]);
        $rentalCart->addColumn('calculated_price', 'decimal', [
            'notnull' => true,
            'comment' => '計算された価格'
        ]);
        $rentalCart->addColumn('note', 'text', [
            'notnull' => false,
            'comment' => '備考'
        ]);
        $rentalCart->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalCart->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalCart->setPrimaryKey(['id']);
        $rentalCart->addIndex(['customer_id'], 'IDX_RENTAL_CART_CUSTOMER');
        $rentalCart->addIndex(['product_id'], 'IDX_RENTAL_CART_PRODUCT');
        $rentalCart->addIndex(['session_id'], 'IDX_RENTAL_CART_SESSION');
        $rentalCart->addIndex(['create_date'], 'IDX_RENTAL_CART_CREATE_DATE');

        // 2. レンタル在庫テーブル
        $rentalInventory = $schema->createTable('plg_rental_inventory');
        $rentalInventory->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'comment' => 'レンタル在庫ID'
        ]);
        $rentalInventory->addColumn('product_id', 'integer', [
            'notnull' => true,
            'comment' => '商品ID'
        ]);
        $rentalInventory->addColumn('available_quantity', 'integer', [
            'notnull' => true,
            'default' => 0,
            'comment' => '利用可能数量'
        ]);
        $rentalInventory->addColumn('reserved_quantity', 'integer', [
            'notnull' => true,
            'default' => 0,
            'comment' => '予約中数量'
        ]);
        $rentalInventory->addColumn('rented_quantity', 'integer', [
            'notnull' => true,
            'default' => 0,
            'comment' => 'レンタル中数量'
        ]);
        $rentalInventory->addColumn('maintenance_quantity', 'integer', [
            'notnull' => true,
            'default' => 0,
            'comment' => 'メンテナンス中数量'
        ]);
        $rentalInventory->addColumn('last_updated', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '最終更新日時'
        ]);
        $rentalInventory->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalInventory->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalInventory->setPrimaryKey(['id']);
        $rentalInventory->addUniqueIndex(['product_id'], 'UNIQ_RENTAL_INVENTORY_PRODUCT');
        $rentalInventory->addIndex(['available_quantity'], 'IDX_RENTAL_INVENTORY_AVAILABLE');
        $rentalInventory->addIndex(['last_updated'], 'IDX_RENTAL_INVENTORY_UPDATED');

        // 3. レンタル通知テーブル
        $rentalNotification = $schema->createTable('plg_rental_notification');
        $rentalNotification->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'comment' => '通知ID'
        ]);
        $rentalNotification->addColumn('rental_order_id', 'integer', [
            'notnull' => true,
            'comment' => 'レンタル注文ID'
        ]);
        $rentalNotification->addColumn('notification_type', 'integer', [
            'notnull' => true,
            'comment' => '通知タイプ'
        ]);
        $rentalNotification->addColumn('notification_date', 'datetime', [
            'notnull' => true,
            'comment' => '通知日時'
        ]);
        $rentalNotification->addColumn('is_sent', 'boolean', [
            'notnull' => true,
            'default' => false,
            'comment' => '送信済みフラグ'
        ]);
        $rentalNotification->addColumn('sent_date', 'datetime', [
            'notnull' => false,
            'comment' => '送信日時'
        ]);
        $rentalNotification->addColumn('message', 'text', [
            'notnull' => false,
            'comment' => '通知メッセージ'
        ]);
        $rentalNotification->addColumn('create_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '作成日時'
        ]);
        $rentalNotification->addColumn('update_date', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => '更新日時'
        ]);
        $rentalNotification->setPrimaryKey(['id']);
        $rentalNotification->addIndex(['rental_order_id'], 'IDX_RENTAL_NOTIFICATION_ORDER');
        $rentalNotification->addIndex(['notification_type'], 'IDX_RENTAL_NOTIFICATION_TYPE');
        $rentalNotification->addIndex(['notification_date'], 'IDX_RENTAL_NOTIFICATION_DATE');
        $rentalNotification->addIndex(['is_sent'], 'IDX_RENTAL_NOTIFICATION_SENT');

        // 4. レンタルログテーブル
        $rentalLog = $schema->createTable('plg_rental_log');
        $rentalLog->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'comment' => 'ログID'
        ]);
        $rentalLog->addColumn('rental_order_id', 'integer', [
            'notnull' => false,
            'comment' => 'レンタル注文ID'
        ]);
        $rentalLog->addColumn('customer_id', 'integer', [
            'notnull' => false,
            'comment' => '顧客ID'
        ]);
        $rentalLog->addColumn('admin_user_id', 'integer', [
            'notnull' => false,
            'comment' => '管理者ユーザーID'
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
            'comment' => 'コンテキスト情報'
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
        $rentalLog->addIndex(['rental_order_id'], 'IDX_RENTAL_LOG_ORDER');
        $rentalLog->addIndex(['customer_id'], 'IDX_RENTAL_LOG_CUSTOMER');
        $rentalLog->addIndex(['log_type'], 'IDX_RENTAL_LOG_TYPE');
        $rentalLog->addIndex(['log_level'], 'IDX_RENTAL_LOG_LEVEL');
        $rentalLog->addIndex(['create_date'], 'IDX_RENTAL_LOG_CREATE_DATE');
    }

    /**
     * ロールバック処理
     *
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // テーブル削除（逆順で削除）
        if ($schema->hasTable('plg_rental_log')) {
            $schema->dropTable('plg_rental_log');
        }
        
        if ($schema->hasTable('plg_rental_notification')) {
            $schema->dropTable('plg_rental_notification');
        }
        
        if ($schema->hasTable('plg_rental_inventory')) {
            $schema->dropTable('plg_rental_inventory');
        }
        
        if ($schema->hasTable('plg_rental_cart')) {
            $schema->dropTable('plg_rental_cart');
        }
    }

    /**
     * マイグレーションの説明を取得
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'レンタル管理プラグイン - カート・在庫・通知・ログテーブル作成';
    }
}