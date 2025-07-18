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

namespace Plugin\Rental\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * レンタルレポートフォーム
 */
class RentalReportType extends AbstractType
{
    /**
     * フォーム構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // 期間設定
        $builder
            ->add('start_date', DateType::class, [
                'label' => '開始日',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => '開始日を選択してください']),
                    new Assert\LessThanOrEqual([
                        'propertyPath' => 'parent.all[end_date].data',
                        'message' => '開始日は終了日以前を選択してください'
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
            ])
            ->add('end_date', DateType::class, [
                'label' => '終了日',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => '終了日を選択してください']),
                    new Assert\GreaterThanOrEqual([
                        'propertyPath' => 'parent.all[start_date].data',
                        'message' => '終了日は開始日以降を選択してください'
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
            ]);

        // レポートタイプ
        $builder
            ->add('report_type', ChoiceType::class, [
                'label' => 'レポートタイプ',
                'choices' => [
                    '売上レポート' => 'sales',
                    '商品別レポート' => 'products',
                    '顧客別レポート' => 'customers',
                    '延滞レポート' => 'overdue',
                    '在庫レポート' => 'inventory',
                ],
                'required' => false,
                'placeholder' => '-- レポートタイプを選択 --',
            ]);

        // 集計単位
        $builder
            ->add('aggregation_unit', ChoiceType::class, [
                'label' => '集計単位',
                'choices' => [
                    '日別' => 'daily',
                    '週別' => 'weekly',
                    '月別' => 'monthly',
                    '年別' => 'yearly',
                ],
                'required' => false,
                'data' => 'daily',
            ]);

        // ステータスフィルター
        $builder
            ->add('status_filter', ChoiceType::class, [
                'label' => 'ステータスフィルター',
                'choices' => [
                    '全て' => '',
                    '申込中' => 1,
                    '予約中' => 2,
                    'レンタル中' => 3,
                    '返却済み' => 4,
                    '返却遅延' => 5,
                    'キャンセル' => 6,
                    '損傷' => 7,
                    '紛失' => 8,
                ],
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control select2',
                    'multiple' => 'multiple',
                ],
            ]);

        // 金額範囲
        $builder
            ->add('amount_min', IntegerType::class, [
                'label' => '最小金額',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'placeholder' => '最小金額',
                ],
            ])
            ->add('amount_max', IntegerType::class, [
                'label' => '最大金額',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'placeholder' => '最大金額',
                ],
            ]);

        // 商品フィルター
        $builder
            ->add('product_filter', ChoiceType::class, [
                'label' => '商品フィルター',
                'choices' => [
                    '全商品' => '',
                    '人気商品のみ' => 'popular',
                    '新商品のみ' => 'new',
                    '高額商品のみ' => 'expensive',
                ],
                'required' => false,
                'placeholder' => false,
            ]);

        // 顧客フィルター
        $builder
            ->add('customer_filter', ChoiceType::class, [
                'label' => '顧客フィルター',
                'choices' => [
                    '全顧客' => '',
                    '新規顧客のみ' => 'new',
                    'リピート顧客のみ' => 'repeat',
                    'VIP顧客のみ' => 'vip',
                ],
                'required' => false,
                'placeholder' => false,
            ]);

        // 地域フィルター
        $builder
            ->add('region_filter', ChoiceType::class, [
                'label' => '地域フィルター',
                'choices' => [
                    '全地域' => '',
                    '北海道・東北' => 'hokkaido_tohoku',
                    '関東' => 'kanto',
                    '中部' => 'chubu',
                    '関西' => 'kansai',
                    '中国・四国' => 'chugoku_shikoku',
                    '九州・沖縄' => 'kyushu_okinawa',
                ],
                'required' => false,
                'placeholder' => false,
            ]);

        // 出力形式
        $builder
            ->add('output_format', ChoiceType::class, [
                'label' => '出力形式',
                'choices' => [
                    'Web表示' => 'web',
                    'CSV' => 'csv',
                    'Excel' => 'excel',
                    'PDF' => 'pdf',
                ],
                'required' => false,
                'data' => 'web',
            ]);

        // 詳細設定
        $builder
            ->add('include_cancelled', ChoiceType::class, [
                'label' => 'キャンセル注文を含む',
                'choices' => [
                    '含まない' => false,
                    '含む' => true,
                ],
                'required' => false,
                'expanded' => true,
                'data' => false,
            ])
            ->add('include_overdue_fee', ChoiceType::class, [
                'label' => '延滞料金を含む',
                'choices' => [
                    '含まない' => false,
                    '含む' => true,
                ],
                'required' => false,
                'expanded' => true,
                'data' => true,
            ])
            ->add('include_deposit', ChoiceType::class, [
                'label' => '保証金を含む',
                'choices' => [
                    '含まない' => false,
                    '含む' => true,
                ],
                'required' => false,
                'expanded' => true,
                'data' => false,
            ]);

        // 表示件数
        $builder
            ->add('limit', ChoiceType::class, [
                'label' => '表示件数',
                'choices' => [
                    '10件' => 10,
                    '20件' => 20,
                    '50件' => 50,
                    '100件' => 100,
                    '全件' => 0,
                ],
                'required' => false,
                'data' => 20,
            ]);

        // ソート設定
        $builder
            ->add('sort_key', ChoiceType::class, [
                'label' => 'ソートキー',
                'choices' => [
                    '日付' => 'date',
                    '金額' => 'amount',
                    '件数' => 'count',
                    '商品名' => 'product_name',
                    '顧客名' => 'customer_name',
                ],
                'required' => false,
                'data' => 'date',
            ])
            ->add('sort_direction', ChoiceType::class, [
                'label' => 'ソート順',
                'choices' => [
                    '降順' => 'DESC',
                    '昇順' => 'ASC',
                ],
                'required' => false,
                'data' => 'DESC',
                'expanded' => true,
            ]);

        // 比較設定
        $builder
            ->add('compare_previous_period', ChoiceType::class, [
                'label' => '前期比較',
                'choices' => [
                    '比較しない' => false,
                    '前期と比較' => true,
                ],
                'required' => false,
                'expanded' => true,
                'data' => false,
                'help' => '選択した期間と同じ長さの前期間とのデータを比較表示します',
            ]);

        // グラフ設定
        $builder
            ->add('chart_type', ChoiceType::class, [
                'label' => 'グラフタイプ',
                'choices' => [
                    '表示しない' => '',
                    '棒グラフ' => 'bar',
                    '折れ線グラフ' => 'line',
                    '円グラフ' => 'pie',
                    '面グラフ' => 'area',
                ],
                'required' => false,
                'placeholder' => false,
            ]);

        // 分析オプション
        $builder
            ->add('analysis_options', ChoiceType::class, [
                'label' => '分析オプション',
                'choices' => [
                    '基本統計' => 'basic_stats',
                    'トレンド分析' => 'trend_analysis',
                    '成長率分析' => 'growth_analysis',
                    '季節性分析' => 'seasonal_analysis',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);

        // フィルター詳細
        $builder
            ->add('exclude_test_data', ChoiceType::class, [
                'label' => 'テストデータを除外',
                'choices' => [
                    '除外しない' => false,
                    '除外する' => true,
                ],
                'required' => false,
                'expanded' => true,
                'data' => true,
                'help' => 'テスト用の注文データを集計から除外します',
            ])
            ->add('min_rental_days', IntegerType::class, [
                'label' => '最小レンタル日数',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'placeholder' => '最小日数',
                ],
                'help' => '指定した日数以上のレンタルのみを対象にします',
            ])
            ->add('max_rental_days', IntegerType::class, [
                'label' => '最大レンタル日数',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'placeholder' => '最大日数',
                ],
                'help' => '指定した日数以下のレンタルのみを対象にします',
            ]);
    }

    /**
     * オプション設定
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * フォーム名を取得
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'rental_report';
    }
}