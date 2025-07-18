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
 * レンタル管理プラグイン インデックス・制約追加
 */
class Version20250515000000 extends AbstractMigration
{
    /**
     * マイグレーション実行
     *
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // 1. パフォーマンス向上のための複合インデックス追加
        
        // レンタル注文テーブル
        $rentalOrder = $schema->getTable('plg_rental_order');
        
        // 期間検索用複合インデックス
        if (!$rentalOrder->hasIndex('IDX_RENTAL_ORDER_PERIOD')) {
            $rentalOrder->addIndex(['rental_start_date', 'rental_end_date'], 'IDX_RENTAL_ORDER_PERIOD');
        }
        
        // 顧客・ステータス検索用複合インデックス
        if (!$rentalOrder->hasIndex('IDX_RENTAL_ORDER_CUSTOMER_STATUS')) {
            $rentalOrder->addIndex(['customer_id', 'status'], 'IDX_RENTAL_ORDER_CUSTOMER_STATUS');
        }
        
        // 商品・期間検索用複合インデックス
        if (!$rentalOrder->hasIndex('IDX_RENTAL_ORDER_PRODUCT_PERIOD')) {
            $rentalOrder->addIndex(['rental_product_id', 'rental_start_date', 'rental_end_date'], 'IDX_RENTAL_ORDER_PRODUCT_PERIOD');
        }
        
        // 延滞チェック用複合インデックス
        if (!$rentalOrder->hasIndex('IDX_RENTAL_ORDER_OVERDUE_CHECK')) {
            $rentalOrder->addIndex(['status', 'rental_end_date'], 'IDX_RENTAL_ORDER_OVERDUE_CHECK');
        }
        
        // 作成日・更新日検索用インデックス
        if (!$rentalOrder->hasIndex('IDX_RENTAL_ORDER_CREATE_DATE')) {
            $rentalOrder->addIndex(['create_date'], 'IDX_RENTAL_ORDER_CREATE_DATE');
        }
        
        // レンタルカートテーブル
        $rentalCart = $schema->getTable('plg_rental_cart');
        
        // セッション・顧客検索用複合インデックス
        if (!$rentalCart->hasIndex('IDX_RENTAL_CART_SESSION_CUSTOMER')) {
            $rentalCart->addIndex(['session_id', 'customer_id'], 'IDX_RENTAL_CART_SESSION_CUSTOMER');
        }
        
        // 商品・期間検索用複合インデックス
        if (!$rentalCart->hasIndex('IDX_RENTAL_CART_PRODUCT_PERIOD')) {
            $rentalCart->addIndex(['product_id', 'rental_start_date', 'rental_end_date'], 'IDX_RENTAL_CART_PRODUCT_PERIOD');
        }

        // レンタルログテーブル
        $rentalLog = $schema->getTable('plg_rental_log');
        
        // 日付・レベル検索用複合インデックス
        if (!$rentalLog->hasIndex('IDX_RENTAL_LOG_DATE_LEVEL')) {
            $rentalLog->addIndex(['create_date', 'log_level'], 'IDX_RENTAL_LOG_DATE_LEVEL');
        }
        
        // 注文・タイプ検索用複合インデックス
        if (!$rentalLog->hasIndex('IDX_RENTAL_LOG_ORDER_TYPE')) {
            $rentalLog->addIndex(['rental_order_id', 'log_type'], 'IDX_RENTAL_LOG_ORDER_TYPE');
        }

        // 2. データ整合性チェック関数を追加（SQLiteではトリガーで実装）
        
        // レンタル期間の論理チェック
        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_order_period_check
            BEFORE INSERT ON plg_rental_order
            FOR EACH ROW
            BEGIN
                SELECT CASE
                    WHEN NEW.rental_start_date >= NEW.rental_end_date THEN
                        RAISE(ABORT, 'レンタル開始日は終了日より前である必要があります')
                END;
            END
        ");

        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_order_period_check_update
            BEFORE UPDATE ON plg_rental_order
            FOR EACH ROW
            BEGIN
                SELECT CASE
                    WHEN NEW.rental_start_date >= NEW.rental_end_date THEN
                        RAISE(ABORT, 'レンタル開始日は終了日より前である必要があります')
                END;
            END
        ");

        // 金額の非負チェック
        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_order_amount_check
            BEFORE INSERT ON plg_rental_order
            FOR EACH ROW
            BEGIN
                SELECT CASE
                    WHEN NEW.total_amount < 0 THEN
                        RAISE(ABORT, '合計金額は0以上である必要があります')
                    WHEN NEW.deposit_amount < 0 THEN
                        RAISE(ABORT, '保証金額は0以上である必要があります')
                    WHEN NEW.overdue_fee < 0 THEN
                        RAISE(ABORT, '延滞料金は0以上である必要があります')
                END;
            END
        ");

        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_order_amount_check_update
            BEFORE UPDATE ON plg_rental_order
            FOR EACH ROW
            BEGIN
                SELECT CASE
                    WHEN NEW.total_amount < 0 THEN
                        RAISE(ABORT, '合計金額は0以上である必要があります')
                    WHEN NEW.deposit_amount < 0 THEN
                        RAISE(ABORT, '保証金額は0以上である必要があります')
                    WHEN NEW.overdue_fee < 0 THEN
                        RAISE(ABORT, '延滞料金は0以上である必要があります')
                END;
            END
        ");

        // 数量の正の整数チェック
        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_order_quantity_check
            BEFORE INSERT ON plg_rental_order
            FOR EACH ROW
            BEGIN
                SELECT CASE
                    WHEN NEW.quantity <= 0 THEN
                        RAISE(ABORT, '数量は1以上である必要があります')
                END;
            END
        ");

        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_order_quantity_check_update
            BEFORE UPDATE ON plg_rental_order
            FOR EACH ROW
            BEGIN
                SELECT CASE
                    WHEN NEW.quantity <= 0 THEN
                        RAISE(ABORT, '数量は1以上である必要があります')
                END;
            END
        ");

        // レンタル商品の料金チェック
        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_product_price_check
            BEFORE INSERT ON plg_rental_product
            FOR EACH ROW
            BEGIN
                SELECT CASE
                    WHEN NEW.daily_price < 0 THEN
                        RAISE(ABORT, '日額料金は0以上である必要があります')
                    WHEN NEW.weekly_price < 0 THEN
                        RAISE(ABORT, '週額料金は0以上である必要があります')
                    WHEN NEW.monthly_price < 0 THEN
                        RAISE(ABORT, '月額料金は0以上である必要があります')
                    WHEN NEW.min_rental_days <= 0 THEN
                        RAISE(ABORT, '最小レンタル日数は1以上である必要があります')
                    WHEN NEW.max_rental_days IS NOT NULL AND NEW.max_rental_days < NEW.min_rental_days THEN
                        RAISE(ABORT, '最大レンタル日数は最小レンタル日数以上である必要があります')
                END;
            END
        ");

        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_product_price_check_update
            BEFORE UPDATE ON plg_rental_product
            FOR EACH ROW
            BEGIN
                SELECT CASE
                    WHEN NEW.daily_price < 0 THEN
                        RAISE(ABORT, '日額料金は0以上である必要があります')
                    WHEN NEW.weekly_price < 0 THEN
                        RAISE(ABORT, '週額料金は0以上である必要があります')
                    WHEN NEW.monthly_price < 0 THEN
                        RAISE(ABORT, '月額料金は0以上である必要があります')
                    WHEN NEW.min_rental_days <= 0 THEN
                        RAISE(ABORT, '最小レンタル日数は1以上である必要があります')
                    WHEN NEW.max_rental_days IS NOT NULL AND NEW.max_rental_days < NEW.min_rental_days THEN
                        RAISE(ABORT, '最大レンタル日数は最小レンタル日数以上である必要があります')
                END;
            END
        ");

        // 在庫数の非負チェック
        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_inventory_quantity_check
            BEFORE INSERT ON plg_rental_inventory
            FOR EACH ROW
            BEGIN
                SELECT CASE
                    WHEN NEW.available_quantity < 0 THEN
                        RAISE(ABORT, '利用可能数量は0以上である必要があります')
                    WHEN NEW.reserved_quantity < 0 THEN
                        RAISE(ABORT, '予約中数量は0以上である必要があります')
                    WHEN NEW.rented_quantity < 0 THEN
                        RAISE(ABORT, 'レンタル中数量は0以上である必要があります')
                    WHEN NEW.maintenance_quantity < 0 THEN
                        RAISE(ABORT, 'メンテナンス中数量は0以上である必要があります')
                END;
            END
        ");

        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_inventory_quantity_check_update
            BEFORE UPDATE ON plg_rental_inventory
            FOR EACH ROW
            BEGIN
                SELECT CASE
                    WHEN NEW.available_quantity < 0 THEN
                        RAISE(ABORT, '利用可能数量は0以上である必要があります')
                    WHEN NEW.reserved_quantity < 0 THEN
                        RAISE(ABORT, '予約中数量は0以上である必要があります')
                    WHEN NEW.rented_quantity < 0 THEN
                        RAISE(ABORT, 'レンタル中数量は0以上である必要があります')
                    WHEN NEW.maintenance_quantity < 0 THEN
                        RAISE(ABORT, 'メンテナンス中数量は0以上である必要があります')
                END;
            END
        ");

        // 3. パフォーマンス向上のためのView作成
        
        // レンタル注文のサマリービュー
        $this->addSql("
            CREATE VIEW IF NOT EXISTS rental_order_summary AS
            SELECT 
                ro.id,
                ro.order_no,
                ro.customer_id,
                ro.rental_start_date,
                ro.rental_end_date,
                ro.actual_return_date,
                ro.status,
                ro.total_amount,
                ro.deposit_amount,
                ro.overdue_fee,
                ro.quantity,
                rp.daily_price,
                rp.weekly_price,
                rp.monthly_price,
                CASE 
                    WHEN ro.status IN (3, 5) AND ro.actual_return_date IS NULL AND datetime('now') > ro.rental_end_date THEN 1
                    ELSE 0
                END as is_overdue,
                CASE 
                    WHEN ro.actual_return_date IS NULL THEN 
                        CAST((julianday(datetime('now')) - julianday(ro.rental_end_date)) AS INTEGER)
                    ELSE 
                        CAST((julianday(ro.actual_return_date) - julianday(ro.rental_end_date)) AS INTEGER)
                END as overdue_days,
                CAST((julianday(ro.rental_end_date) - julianday(ro.rental_start_date)) AS INTEGER) + 1 as rental_days
            FROM plg_rental_order ro
            LEFT JOIN plg_rental_product rp ON ro.rental_product_id = rp.id
        ");

        // 在庫状況のサマリービュー
        $this->addSql("
            CREATE VIEW IF NOT EXISTS rental_inventory_summary AS
            SELECT 
                ri.product_id,
                ri.available_quantity,
                ri.reserved_quantity,
                ri.rented_quantity,
                ri.maintenance_quantity,
                ri.available_quantity + ri.reserved_quantity + ri.rented_quantity + ri.maintenance_quantity as total_quantity,
                ri.available_quantity - ri.reserved_quantity as actual_available,
                ri.last_updated
            FROM plg_rental_inventory ri
        ");

        // 月次レポート用ビュー
        $this->addSql("
            CREATE VIEW IF NOT EXISTS rental_monthly_report AS
            SELECT 
                strftime('%Y-%m', ro.create_date) as month,
                COUNT(*) as total_orders,
                SUM(ro.total_amount) as total_revenue,
                SUM(ro.deposit_amount) as total_deposit,
                SUM(ro.overdue_fee) as total_overdue_fee,
                COUNT(CASE WHEN ro.status = 4 THEN 1 END) as completed_orders,
                COUNT(CASE WHEN ro.status = 5 THEN 1 END) as overdue_orders,
                COUNT(CASE WHEN ro.status = 6 THEN 1 END) as cancelled_orders,
                AVG(CAST((julianday(ro.rental_end_date) - julianday(ro.rental_start_date)) AS INTEGER) + 1) as avg_rental_days
            FROM plg_rental_order ro
            GROUP BY strftime('%Y-%m', ro.create_date)
        ");

        // 4. 古いデータの自動削除用設定
        $this->addSql("INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES 
            ('cleanup_old_cart_days', '7', 'カート自動削除日数', datetime('now'), datetime('now')),
            ('cleanup_old_log_days', '90', 'ログ自動削除日数', datetime('now'), datetime('now')),
            ('cleanup_old_notification_days', '30', '通知自動削除日数', datetime('now'), datetime('now')),
            ('enable_auto_cleanup', '1', '自動削除機能有効', datetime('now'), datetime('now'))
        ");

        // 5. 統計情報更新用のトリガー
        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_inventory_update_timestamp
            AFTER UPDATE ON plg_rental_inventory
            FOR EACH ROW
            BEGIN
                UPDATE plg_rental_inventory 
                SET last_updated = datetime('now'), update_date = datetime('now')
                WHERE id = NEW.id;
            END
        ");

        // 6. 最終的なデータ整合性チェック
        $this->addSql("
            CREATE TRIGGER IF NOT EXISTS rental_order_status_change_log
            AFTER UPDATE OF status ON plg_rental_order
            FOR EACH ROW
            WHEN OLD.status != NEW.status
            BEGIN
                INSERT INTO plg_rental_log (rental_order_id, log_type, log_level, message, create_date)
                VALUES (NEW.id, 'STATUS_CHANGE', 'INFO', 
                    'ステータス変更: ' || OLD.status || ' → ' || NEW.status, 
                    datetime('now'));
            END
        ");
    }

    /**
     * ロールバック処理
     *
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // 1. ビューの削除
        $this->addSql("DROP VIEW IF EXISTS rental_monthly_report");
        $this->addSql("DROP VIEW IF EXISTS rental_inventory_summary");
        $this->addSql("DROP VIEW IF EXISTS rental_order_summary");

        // 2. トリガーの削除
        $this->addSql("DROP TRIGGER IF EXISTS rental_order_status_change_log");
        $this->addSql("DROP TRIGGER IF EXISTS rental_inventory_update_timestamp");
        $this->addSql("DROP TRIGGER IF EXISTS rental_inventory_quantity_check_update");
        $this->addSql("DROP TRIGGER IF EXISTS rental_inventory_quantity_check");
        $this->addSql("DROP TRIGGER IF EXISTS rental_product_price_check_update");
        $this->addSql("DROP TRIGGER IF EXISTS rental_product_price_check");
        $this->addSql("DROP TRIGGER IF EXISTS rental_order_quantity_check_update");
        $this->addSql("DROP TRIGGER IF EXISTS rental_order_quantity_check");
        $this->addSql("DROP TRIGGER IF EXISTS rental_order_amount_check_update");
        $this->addSql("DROP TRIGGER IF EXISTS rental_order_amount_check");
        $this->addSql("DROP TRIGGER IF EXISTS rental_order_period_check_update");
        $this->addSql("DROP TRIGGER IF EXISTS rental_order_period_check");

        // 3. インデックスの削除
        $rentalOrder = $schema->getTable('plg_rental_order');
        $orderIndexesToRemove = [
            'IDX_RENTAL_ORDER_PERIOD',
            'IDX_RENTAL_ORDER_CUSTOMER_STATUS',
            'IDX_RENTAL_ORDER_PRODUCT_PERIOD',
            'IDX_RENTAL_ORDER_OVERDUE_CHECK',
            'IDX_RENTAL_ORDER_CREATE_DATE'
        ];

        foreach ($orderIndexesToRemove as $index) {
            if ($rentalOrder->hasIndex($index)) {
                $rentalOrder->dropIndex($index);
            }
        }

        $rentalCart = $schema->getTable('plg_rental_cart');
        $cartIndexesToRemove = [
            'IDX_RENTAL_CART_SESSION_CUSTOMER',
            'IDX_RENTAL_CART_PRODUCT_PERIOD'
        ];

        foreach ($cartIndexesToRemove as $index) {
            if ($rentalCart->hasIndex($index)) {
                $rentalCart->dropIndex($index);
            }
        }

        $rentalLog = $schema->getTable('plg_rental_log');
        $logIndexesToRemove = [
            'IDX_RENTAL_LOG_DATE_LEVEL',
            'IDX_RENTAL_LOG_ORDER_TYPE'
        ];

        foreach ($logIndexesToRemove as $index) {
            if ($rentalLog->hasIndex($index)) {
                $rentalLog->dropIndex($index);
            }
        }

        // 4. 設定の削除
        $this->addSql("DELETE FROM plg_rental_config WHERE config_key IN ('cleanup_old_cart_days', 'cleanup_old_log_days', 'cleanup_old_notification_days', 'enable_auto_cleanup')");
    }

    /**
     * マイグレーションの説明を取得
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'レンタル管理プラグイン - インデックス・制約・ビュー・トリガー追加';
    }
}