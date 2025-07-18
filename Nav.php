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

use Eccube\Common\EccubeNav;

/**
 * レンタル管理プラグイン 管理画面ナビゲーション設定
 */
class Nav implements EccubeNav
{
    /**
     * 管理画面ナビゲーション設定を取得
     *
     * @return array
     */
    public static function getNav()
    {
        return [
            'rental' => [
                'name' => 'レンタル管理',
                'icon' => 'fa-calendar-alt',
                'children' => [
                    'rental_order' => [
                        'name' => 'レンタル注文管理',
                        'url' => 'admin_rental_order',
                        'children' => [
                            'rental_order_list' => [
                                'name' => '注文一覧',
                                'url' => 'admin_rental_order',
                            ],
                            'rental_order_overdue' => [
                                'name' => '延滞管理',
                                'url' => 'admin_rental_order_overdue',
                            ],
                            'rental_order_reminder' => [
                                'name' => 'リマインダー',
                                'url' => 'admin_rental_order_reminder',
                            ],
                        ],
                    ],
                    'rental_report' => [
                        'name' => 'レポート・分析',
                        'url' => 'admin_rental_report',
                        'children' => [
                            'rental_report_sales' => [
                                'name' => '売上レポート',
                                'url' => 'admin_rental_report_sales',
                            ],
                            'rental_report_products' => [
                                'name' => '商品レポート',
                                'url' => 'admin_rental_report_products',
                            ],
                            'rental_report_customers' => [
                                'name' => '顧客レポート',
                                'url' => 'admin_rental_report_customers',
                            ],
                            'rental_report_risk' => [
                                'name' => 'リスク管理',
                                'url' => 'admin_rental_report_risk',
                            ],
                            'rental_analytics' => [
                                'name' => 'データ分析',
                                'url' => 'admin_rental_analytics',
                            ],
                        ],
                    ],
                    'rental_config' => [
                        'name' => 'レンタル設定',
                        'url' => 'admin_rental_config',
                    ],
                ],
            ],
        ];
    }
}