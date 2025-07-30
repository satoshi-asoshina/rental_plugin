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
 * レンタル管理プラグイン 初期設定データ投入（MySQL対応版）
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
        $this->insertBasicConfig();
        $this->insertEmailConfig();
        $this->insertSystemConfig();
        $this->insertDisplayConfig();
        $this->insertApiConfig();
        $this->insertSecurityConfig();
    }

    /**
     * 基本設定データ投入
     */
    private function insertBasicConfig(): void
    {
        $configs = [
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
            [
                'config_key' => 'advance_booking_days',
                'config_value' => '90',
                'config_description' => '事前予約可能日数（何日先まで予約可能か）'
            ],
            [
                'config_key' => 'same_day_rental',
                'config_value' => '1',
                'config_description' => '当日レンタル許可（0:不可, 1:可）'
            ],
            [
                'config_key' => 'rental_time_slot',
                'config_value' => '{"morning": "09:00-12:00", "afternoon": "13:00-17:00"}',
                'config_description' => 'レンタル時間枠設定（JSON形式）'
            ]
        ];

        foreach ($configs as $config) {
            $this->addSql(
                "INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES (?, ?, ?, NOW(), NOW())",
                [$config['config_key'], $config['config_value'], $config['config_description']]
            );
        }
    }

    /**
     * メール設定データ投入
     */
    private function insertEmailConfig(): void
    {
        $configs = [
            [
                'config_key' => 'mail_order_confirm_enabled',
                'config_value' => '1',
                'config_description' => 'レンタル注文確認メール送信設定'
            ],
            [
                'config_key' => 'mail_order_confirm_subject',
                'config_value' => 'レンタル注文確認のお知らせ',
                'config_description' => '注文確認メール件名'
            ],
            [
                'config_key' => 'mail_reminder_enabled',
                'config_value' => '1',
                'config_description' => '返却リマインダーメール送信設定'
            ],
            [
                'config_key' => 'mail_reminder_subject',
                'config_value' => 'レンタル商品返却のリマインダー',
                'config_description' => 'リマインダーメール件名'
            ],
            [
                'config_key' => 'mail_overdue_enabled',
                'config_value' => '1',
                'config_description' => '延滞通知メール送信設定'
            ],
            [
                'config_key' => 'mail_overdue_subject',
                'config_value' => 'レンタル商品返却遅延のお知らせ',
                'config_description' => '延滞通知メール件名'
            ],
            [
                'config_key' => 'mail_return_confirm_enabled',
                'config_value' => '1',
                'config_description' => '返却確認メール送信設定'
            ],
            [
                'config_key' => 'mail_return_confirm_subject',
                'config_value' => 'レンタル商品返却確認のお知らせ',
                'config_description' => '返却確認メール件名'
            ],
            [
                'config_key' => 'mail_from_name',
                'config_value' => 'レンタル管理システム',
                'config_description' => 'メール送信者名'
            ],
            [
                'config_key' => 'mail_auto_send',
                'config_value' => '1',
                'config_description' => 'メール自動送信設定（0:手動, 1:自動）'
            ]
        ];

        foreach ($configs as $config) {
            $this->addSql(
                "INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES (?, ?, ?, NOW(), NOW())",
                [$config['config_key'], $config['config_value'], $config['config_description']]
            );
        }
    }

    /**
     * システム設定データ投入
     */
    private function insertSystemConfig(): void
    {
        $configs = [
            [
                'config_key' => 'cart_enabled',
                'config_value' => '1',
                'config_description' => 'レンタルカート機能有効化設定'
            ],
            [
                'config_key' => 'cart_session_timeout',
                'config_value' => '1800',
                'config_description' => 'カートセッションタイムアウト（秒）'
            ],
            [
                'config_key' => 'inventory_management',
                'config_value' => '1',
                'config_description' => '在庫管理機能有効化設定'
            ],
            [
                'config_key' => 'auto_inventory_update',
                'config_value' => '1',
                'config_description' => '在庫自動更新設定（返却時）'
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
            [
                'config_key' => 'log_retention_days',
                'config_value' => '365',
                'config_description' => 'ログ保持期間（日）'
            ],
            [
                'config_key' => 'backup_enabled',
                'config_value' => '1',
                'config_description' => '自動バックアップ機能有効化'
            ],
            [
                'config_key' => 'backup_frequency',
                'config_value' => 'daily',
                'config_description' => 'バックアップ頻度（daily/weekly/monthly）'
            ],
            [
                'config_key' => 'maintenance_mode',
                'config_value' => '0',
                'config_description' => 'メンテナンスモード（0:通常, 1:メンテナンス中）'
            ]
        ];

        foreach ($configs as $config) {
            $this->addSql(
                "INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES (?, ?, ?, NOW(), NOW())",
                [$config['config_key'], $config['config_value'], $config['config_description']]
            );
        }
    }

    /**
     * 表示設定データ投入
     */
    private function insertDisplayConfig(): void
    {
        $configs = [
            [
                'config_key' => 'date_format',
                'config_value' => 'Y-m-d',
                'config_description' => '日付表示フォーマット'
            ],
            [
                'config_key' => 'datetime_format',
                'config_value' => 'Y-m-d H:i',
                'config_description' => '日時表示フォーマット'
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
            [
                'config_key' => 'thousand_separator',
                'config_value' => ',',
                'config_description' => '3桁区切り文字'
            ],
            [
                'config_key' => 'items_per_page',
                'config_value' => '20',
                'config_description' => '1ページあたりの表示件数'
            ],
            [
                'config_key' => 'calendar_theme',
                'config_value' => 'default',
                'config_description' => 'カレンダーテーマ'
            ],
            [
                'config_key' => 'show_rental_in_product_list',
                'config_value' => '1',
                'config_description' => '商品一覧でレンタル表示（0:非表示, 1:表示）'
            ],
            [
                'config_key' => 'rental_badge_color',
                'config_value' => '#28a745',
                'config_description' => 'レンタルバッジ色'
            ],
            [
                'config_key' => 'enable_rental_search',
                'config_value' => '1',
                'config_description' => 'レンタル商品検索機能有効化'
            ]
        ];

        foreach ($configs as $config) {
            $this->addSql(
                "INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES (?, ?, ?, NOW(), NOW())",
                [$config['config_key'], $config['config_value'], $config['config_description']]
            );
        }
    }

    /**
     * API設定データ投入
     */
    private function insertApiConfig(): void
    {
        $configs = [
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
            [
                'config_key' => 'api_key_required',
                'config_value' => '1',
                'config_description' => 'APIキー認証必須設定'
            ],
            [
                'config_key' => 'api_log_enabled',
                'config_value' => '1',
                'config_description' => 'APIアクセスログ記録設定'
            ],
            [
                'config_key' => 'webhook_enabled',
                'config_value' => '0',
                'config_description' => 'Webhook機能有効化設定'
            ],
            [
                'config_key' => 'webhook_url',
                'config_value' => '',
                'config_description' => 'Webhook送信先URL'
            ],
            [
                'config_key' => 'webhook_events',
                'config_value' => '["order_create", "order_return", "order_overdue"]',
                'config_description' => 'Webhook送信イベント（JSON配列）'
            ]
        ];

        foreach ($configs as $config) {
            $this->addSql(
                "INSERT INTO plg_rental_config (config_key, config_value, config_description, create_date, update_date) VALUES (?, ?, ?, NOW(), NOW())",
                [$config['config_key'], $config['config_value'], $config['config_description']]
            );
        }
    }

    /**
     * セキュリティ設定データ投入
     */
    private function insertSecurityConfig(): void
    {
        $configs = [
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
            [
                'config_key' => 'csrf_protection',
                'config_value' => '1',
                'config_description' => 'CSRF保護有効化設定'
            ],
            [
                'config_key' => 'rate_limiting',
                'config_value' => '1',
                'config_description' => 'レート制限有効化設定'
            ],
            [
                'config_key' => 'max_login_attempts',
                'config_value' => '5',
                'config_description' => '最大ログイン試行回数'
            ],
            [
                'config_key' => 'login_lockout_duration',
                'config_value' => '900',
                'config_description' => 'ログインロックアウト時間（秒）'
            ],
            [
                'config_key' => 'ip_whitelist_enabled',
                'config_value' => '0',
                'config_description' => 'IPホワイトリスト機能有効化'
            ],
            [
                'config_key' => 'ip_whitelist',
                'config_value' => '[]',
                'config_description' => '許可IPアドレス一覧（JSON配列）'
            ],
            [
                'config_key' => 'encryption_enabled',
                'config_value' => '1',
                'config_description' => 'データ暗号化有効化設定'
            ],
            [
                'config_key' => 'audit_trail_enabled',
                'config_value' => '1',
                'config_description' => '監査証跡記録有効化設定'
            ]
        ];

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
        $configKeys = [
            // 基本設定
            'auto_approval', 'max_rental_days', 'min_rental_days', 'reminder_days', 'overdue_fee_rate', 
            'deposit_required', 'guest_rental', 'advance_booking_days', 'same_day_rental', 'rental_time_slot',
            
            // メール設定
            'mail_order_confirm_enabled', 'mail_order_confirm_subject', 'mail_reminder_enabled', 'mail_reminder_subject',
            'mail_overdue_enabled', 'mail_overdue_subject', 'mail_return_confirm_enabled', 'mail_return_confirm_subject',
            'mail_from_name', 'mail_auto_send',
            
            // システム設定
            'cart_enabled', 'cart_session_timeout', 'inventory_management', 'auto_inventory_update',
            'analytics_enabled', 'audit_log_enabled', 'log_retention_days', 'backup_enabled',
            'backup_frequency', 'maintenance_mode',
            
            // 表示設定
            'date_format', 'datetime_format', 'currency_symbol', 'decimal_places', 'thousand_separator',
            'items_per_page', 'calendar_theme', 'show_rental_in_product_list', 'rental_badge_color', 'enable_rental_search',
            
            // API設定
            'api_enabled', 'api_rate_limit', 'api_key_required', 'api_log_enabled',
            'webhook_enabled', 'webhook_url', 'webhook_events',
            
            // セキュリティ設定
            'secure_mode', 'session_timeout', 'csrf_protection', 'rate_limiting', 'max_login_attempts',
            'login_lockout_duration', 'ip_whitelist_enabled', 'ip_whitelist', 'encryption_enabled', 'audit_trail_enabled'
        ];

        $placeholders = str_repeat('?,', count($configKeys) - 1) . '?';
        $this->addSql("DELETE FROM plg_rental_config WHERE config_key IN ({$placeholders})", $configKeys);
    }
}