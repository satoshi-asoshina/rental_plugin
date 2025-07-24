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
 * レンタル管理プラグイン 設定初期データ投入（MySQL対応版）
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
        // 既存データチェック（冪等性確保）
        $count = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM plg_rental_config WHERE config_key = 'auto_approval'"
        );
        
        if ($count > 0) {
            return; // 既にデータが存在する場合はスキップ
        }

        // 初期設定データ投入
        $configs = [
            // 基本設定
            [
                'config_key' => 'auto_approval',
                'config_value' => '0',
                'config_description' => 'レンタル注文の自動承認設定（0:手動承認, 1:自動承認）'
            ],
            [
                'config_key' => 'max_rental_days',
                'config_value' => '30',
                'config_description' => 'デフォルト最大レンタル日数'
            ],
            [
                'config_key' => 'min_rental_days',
                'config_value' => '1',
                'config_description' => 'デフォルト最小レンタル日数'
            ],
            [
                'config_key' => 'reminder_days',
                'config_value' => '3',
                'config_description' => '返却リマインダー送信日数（返却日の何日前）'
            ],
            [
                'config_key' => 'overdue_fee_rate',
                'config_value' => '0.1',
                'config_description' => '延滞料金率（日額レンタル料金に対する割合）'
            ],
            [
                'config_key' => 'deposit_required',
                'config_value' => '0',
                'config_description' => '保証金必須設定（0:不要, 1:必須）'
            ],
            [
                'config_key' => 'guest_rental',
                'config_value' => '0',
                'config_description' => 'ゲストレンタル許可設定（0:会員のみ, 1:ゲスト可）'
            ],
            
            // メール設定
            [
                'config_key' => 'mail_order_confirm_enabled',
                'config_value' => '1',
                'config_description' => 'レンタル注文確認メール送信設定'
            ],
            [
                'config_key' => 'mail_reminder_enabled',
                'config_value' => '1',
                'config_description' => '返却リマインダーメール送信設定'
            ],
            [
                'config_key' => 'mail_overdue_enabled',
                'config_value' => '1',
                'config_description' => '延滞通知メール送信設定'
            ],
            [
                'config_key' => 'mail_return_confirm_enabled',
                'config_value' => '1',
                'config_description' => '返却確認メール送信設定'
            ],
            
            // システム設定
            [
                'config_key' => 'cart_enabled',
                'config_value' => '1',
                'config_description' => 'レンタルカート機能有効化設定'
            ],
            [
                'config_key' => 'inventory_management',
                'config_value' => '1',
                'config_description' => '在庫管理機能有効化設定'
            ],
            [
                'config_key' => 'analytics_enabled',
                'config_value' => '1',
                'config_description' => '分析機能有効化設定'
            ],
            [
                'config_key' => 'audit_log_enabled',
                'config_value' => '1',
                'config_description' => '監査ログ機能有効化設定'
            ],
            
            // 表示設定
            [
                'config_key' => 'date_format',
                'config_value' => 'Y-m-d',
                'config_description' => '日付表示フォーマット'
            ],
            [
                'config_key' => 'currency_symbol',
                'config_value' => '¥',
                'config_description' => '通貨記号'
            ],
            [
                'config_key' => 'decimal_places',
                'config_value' => '0',
                'config_description' => '金額小数点桁数'
            ],
            
            // API設定
            [
                'config_key' => 'api_enabled',
                'config_value' => '0',
                'config_description' => 'REST API機能有効化設定'
            ],
            [
                'config_key' => 'api_rate_limit',
                'config_value' => '100',
                'config_description' => 'API呼び出し制限（1時間あたり）'
            ],
            
            // セキュリティ設定
            [
                'config_key' => 'secure_mode',
                'config_value' => '1',
                'config_description' => 'セキュリティモード（追加認証など）'
            ],
            [
                'config_key' => 'session_timeout',
                'config_value' => '3600',
                'config_description' => 'セッションタイムアウト（秒）'
            ],
        ];

        // バッチ挿入でパフォーマンス向上
        foreach ($configs as $config) {
            $this->addSql(
                "INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES (?, ?, ?, NOW(), NOW())",
                [$config['config_key'], $config['config_value'], $config['config_description']]
            );
        }
    }

    /**
     * マイグレーション取り消し
     *
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // 設定データ削除
        $this->addSql("DELETE FROM plg_rental_config WHERE config_key IN (
            'auto_approval', 'max_rental_days', 'min_rental_days', 'reminder_days', 'overdue_fee_rate', 'deposit_required', 'guest_rental',
            'mail_order_confirm_enabled', 'mail_reminder_enabled', 'mail_overdue_enabled', 'mail_return_confirm_enabled',
            'cart_enabled', 'inventory_management', 'analytics_enabled', 'audit_log_enabled',
            'date_format', 'currency_symbol', 'decimal_places',
            'api_enabled', 'api_rate_limit',
            'secure_mode', 'session_timeout'
        )");
    }
}