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

use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Plugin\Rental\Entity\RentalOrder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * レンタル注文検索フォーム
 */
class RentalSearchType extends AbstractType
{
    /**
     * フォーム構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // 基本検索
        $builder
            ->add('order_no', TextType::class, [
                'label' => '注文番号',
                'required' => false,
                'attr' => [
                    'placeholder' => '注文番号で検索...',
                ],
            ])
            ->add('customer_name', TextType::class, [
                'label' => '顧客名',
                'required' => false,
                'attr' => [
                    'placeholder' => '顧客名で検索...',
                ],
            ])
            ->add('customer_email', TextType::class, [
                'label' => '顧客メールアドレス',
                'required' => false,
                'attr' => [
                    'placeholder' => 'メールアドレスで検索...',
                ],
            ])
            ->add('product_name', TextType::class, [
                'label' => '商品名',
                'required' => false,
                'attr' => [
                    'placeholder' => '商品名で検索...',
                ],
            ]);

        // ステータス検索
        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'ステータス',
                'choices' => array_merge(['-- すべて --' => ''], RentalOrder::STATUS_NAMES),
                'required' => false,
                'placeholder' => false,
            ])
            ->add('payment_status', ChoiceType::class, [
                'label' => '支払いステータス',
                'choices' => [
                    '-- すべて --' => '',
                    '未払い' => 1,
                    '支払い済み' => 2,
                    '部分支払い' => 3,
                    '返金済み' => 4,
                    'キャンセル' => 5,
                ],
                'required' => false,
                'placeholder' => false,
            ]);

        // 期間検索
        $builder
            ->add('create_date_start', DateType::class, [
                'label' => '注文日（開始）',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
            ])
            ->add('create_date_end', DateType::class, [
                'label' => '注文日（終了）',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
            ])
            ->add('rental_start_date_start', DateType::class, [
                'label' => 'レンタル開始日（開始）',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
            ])
            ->add('rental_start_date_end', DateType::class, [
                'label' => 'レンタル開始日（終了）',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
            ])
            ->add('rental_end_date_start', DateType::class, [
                'label' => 'レンタル終了日（開始）',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
            ])
            ->add('rental_end_date_end', DateType::class, [
                'label' => 'レンタル終了日（終了）',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
            ]);

        // 金額検索
        $builder
            ->add('total_amount_min', MoneyType::class, [
                'label' => '合計金額（最小）',
                'currency' => 'JPY',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'placeholder' => '最小金額',
                ],
            ])
            ->add('total_amount_max', MoneyType::class, [
                'label' => '合計金額（最大）',
                'currency' => 'JPY',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'placeholder' => '最大金額',
                ],
            ]);

        // 数量検索
        $builder
            ->add('quantity_min', IntegerType::class, [
                'label' => '数量（最小）',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'placeholder' => '最小数量',
                ],
            ])
            ->add('quantity_max', IntegerType::class, [
                'label' => '数量（最大）',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'placeholder' => '最大数量',
                ],
            ]);

        // 配送関連検索
        $builder
            ->add('delivery_pref', TextType::class, [
                'label' => '配送先都道府県',
                'required' => false,
                'attr' => [
                    'placeholder' => '都道府県で検索...',
                ],
            ])
            ->add('tracking_number', TextType::class, [
                'label' => '追跡番号',
                'required' => false,
                'attr' => [
                    'placeholder' => '追跡番号で検索...',
                ],
            ]);

        // 延滞検索
        $builder
            ->add('overdue_only', ChoiceType::class, [
                'label' => '延滞のみ',
                'choices' => [
                    '全て' => '',
                    '延滞のみ' => 'overdue',
                    '期限内のみ' => 'not_overdue',
                ],
                'required' => false,
                'expanded' => false,
            ])
            ->add('overdue_days_min', IntegerType::class, [
                'label' => '延滞日数（最小）',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'placeholder' => '最小延滞日数',
                ],
            ])
            ->add('overdue_days_max', IntegerType::class, [
                'label' => '延滞日数（最大）',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'placeholder' => '最大延滞日数',
                ],
            ]);

        // 並び順
        $builder
            ->add('sort_key', ChoiceType::class, [
                'label' => '並び順',
                'choices' => [
                    '作成日' => 'create_date',
                    '注文番号' => 'order_no',
                    'レンタル開始日' => 'rental_start_date',
                    'レンタル終了日' => 'rental_end_date',
                    '合計金額' => 'total_amount',
                    'ステータス' => 'status',
                    '顧客名' => 'customer_name',
                ],
                'required' => false,
                'data' => 'create_date',
            ])
            ->add('sort_direction', ChoiceType::class, [
                'label' => '順序',
                'choices' => [
                    '降順（新しい順）' => 'DESC',
                    '昇順（古い順）' => 'ASC',
                ],
                'required' => false,
                'data' => 'DESC',
            ]);

        // 優先度検索
        $builder
            ->add('priority_level', ChoiceType::class, [
                'label' => '優先度レベル',
                'choices' => [
                    '-- すべて --' => '',
                    '低' => 1,
                    '通常' => 2,
                    '高' => 3,
                    '緊急' => 4,
                ],
                'required' => false,
                'placeholder' => false,
            ]);

        // 支払い方法検索
        $builder
            ->add('payment_method', ChoiceType::class, [
                'label' => '支払い方法',
                'choices' => [
                    '-- すべて --' => '',
                    'クレジットカード' => 'credit',
                    '銀行振込' => 'bank',
                    'コンビニ決済' => 'convenience',
                    '現金' => 'cash',
                    '代金引換' => 'cod',
                ],
                'required' => false,
                'placeholder' => false,
            ]);

        // 特殊検索条件
        $builder
            ->add('has_damage_fee', ChoiceType::class, [
                'label' => '損害料金',
                'choices' => [
                    '-- すべて --' => '',
                    '損害料金あり' => 'yes',
                    '損害料金なし' => 'no',
                ],
                'required' => false,
                'placeholder' => false,
            ])
            ->add('has_cleaning_fee', ChoiceType::class, [
                'label' => 'クリーニング料金',
                'choices' => [
                    '-- すべて --' => '',
                    'クリーニング料金あり' => 'yes',
                    'クリーニング料金なし' => 'no',
                ],
                'required' => false,
                'placeholder' => false,
            ])
            ->add('has_extension', ChoiceType::class, [
                'label' => '延長',
                'choices' => [
                    '-- すべて --' => '',
                    '延長あり' => 'yes',
                    '延長なし' => 'no',
                ],
                'required' => false,
                'placeholder' => false,
            ]);

        // レンタル期間検索
        $builder
            ->add('rental_days_min', IntegerType::class, [
                'label' => 'レンタル日数（最小）',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'placeholder' => '最小日数',
                ],
            ])
            ->add('rental_days_max', IntegerType::class, [
                'label' => 'レンタル日数（最大）',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'placeholder' => '最大日数',
                ],
            ]);

        // フリーワード検索
        $builder
            ->add('keyword', TextType::class, [
                'label' => 'フリーワード',
                'required' => false,
                'attr' => [
                    'placeholder' => '注文番号、顧客名、商品名、メモなどで検索...',
                ],
                'help' => '注文番号、顧客名、商品名、各種メモ欄を横断検索します',
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
        return 'rental_search';
    }
}