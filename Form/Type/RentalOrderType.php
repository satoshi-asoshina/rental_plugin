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

namespace Plugin\Rental\Form\Type;

use Eccube\Entity\Customer;
use Plugin\Rental\Entity\RentalOrder;
use Plugin\Rental\Entity\RentalProduct;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * レンタル注文フォーム
 */
class RentalOrderType extends AbstractType
{
    /**
     * フォーム構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // 基本情報
        $builder
            ->add('order_no', TextType::class, [
                'label' => '注文番号',
                'disabled' => true,
                'help' => '注文番号は自動生成されます',
            ])
            ->add('Customer', EntityType::class, [
                'label' => '顧客',
                'class' => Customer::class,
                'choice_label' => function (Customer $customer) {
                    return sprintf('%s %s (%s)', 
                        $customer->getName01(), 
                        $customer->getName02(), 
                        $customer->getEmail()
                    );
                },
                'placeholder' => '-- 顧客を選択 --',
                'constraints' => [
                    new Assert\NotBlank(['message' => '顧客を選択してください']),
                ],
                'attr' => [
                    'class' => 'form-control select2',
                ],
            ])
            ->add('RentalProduct', EntityType::class, [
                'label' => 'レンタル商品',
                'class' => RentalProduct::class,
                'choice_label' => function (RentalProduct $rentalProduct) {
                    return $rentalProduct->getProduct()->getName();
                },
                'placeholder' => '-- レンタル商品を選択 --',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'レンタル商品を選択してください']),
                ],
                'attr' => [
                    'class' => 'form-control select2',
                ],
            ]);

        // レンタル期間
        $builder
            ->add('rental_start_date', DateTimeType::class, [
                'label' => 'レンタル開始日時',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'レンタル開始日時を入力してください']),
                    new Assert\GreaterThan([
                        'value' => 'today',
                        'message' => 'レンタル開始日は今日以降を選択してください'
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control datetimepicker',
                ],
            ])
            ->add('rental_end_date', DateTimeType::class, [
                'label' => 'レンタル終了日時',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'レンタル終了日時を入力してください']),
                ],
                'attr' => [
                    'class' => 'form-control datetimepicker',
                ],
            ])
            ->add('actual_return_date', DateTimeType::class, [
                'label' => '実際の返却日時',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datetimepicker',
                ],
            ]);

        // 数量・金額
        $builder
            ->add('quantity', IntegerType::class, [
                'label' => '数量',
                'constraints' => [
                    new Assert\NotBlank(['message' => '数量を入力してください']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 100,
                        'minMessage' => '数量は1以上で入力してください',
                        'maxMessage' => '数量は100以下で入力してください',
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 100,
                ],
            ])
            ->add('total_amount', MoneyType::class, [
                'label' => '合計金額',
                'currency' => 'JPY',
                'constraints' => [
                    new Assert\NotBlank(['message' => '合計金額を入力してください']),
                    new Assert\Range(['min' => 0]),
                ],
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('deposit_amount', MoneyType::class, [
                'label' => '保証金額',
                'currency' => 'JPY',
                'required' => false,
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('overdue_fee', MoneyType::class, [
                'label' => '延滞料金',
                'currency' => 'JPY',
                'required' => false,
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('extension_fee', MoneyType::class, [
                'label' => '延長料金',
                'currency' => 'JPY',
                'required' => false,
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('damage_fee', MoneyType::class, [
                'label' => '損害料金',
                'currency' => 'JPY',
                'required' => false,
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('cleaning_fee', MoneyType::class, [
                'label' => 'クリーニング料金',
                'currency' => 'JPY',
                'required' => false,
                'attr' => [
                    'min' => 0,
                ],
            ]);

        // ステータス
        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'ステータス',
                'choices' => RentalOrder::STATUS_NAMES,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'ステータスを選択してください']),
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);

        // 配送情報
        $builder
            ->add('delivery_name01', TextType::class, [
                'label' => '配送先名前（姓）',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('delivery_name02', TextType::class, [
                'label' => '配送先名前（名）',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('delivery_postal_code', TextType::class, [
                'label' => '配送先郵便番号',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^\d{3}-?\d{4}$/',
                        'message' => '郵便番号の形式が正しくありません（例：123-4567）'
                    ]),
                ],
                'attr' => [
                    'placeholder' => '123-4567',
                ],
            ])
            ->add('delivery_pref', TextType::class, [
                'label' => '配送先都道府県',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('delivery_addr01', TextType::class, [
                'label' => '配送先住所1',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('delivery_addr02', TextType::class, [
                'label' => '配送先住所2',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('delivery_phone', TextType::class, [
                'label' => '配送先電話番号',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[0-9\-\(\)]+$/',
                        'message' => '電話番号は数字、ハイフン、括弧のみで入力してください'
                    ]),
                ],
                'attr' => [
                    'placeholder' => '03-1234-5678',
                ],
            ]);

        // 配送日時
        $builder
            ->add('delivery_date', DateType::class, [
                'label' => '配送希望日',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
            ])
            ->add('delivery_time', ChoiceType::class, [
                'label' => '配送希望時間',
                'choices' => [
                    '指定なし' => '',
                    '午前中（9:00-12:00）' => 'morning',
                    '14:00-16:00' => '14-16',
                    '16:00-18:00' => '16-18',
                    '18:00-20:00' => '18-20',
                    '20:00-21:00' => '20-21',
                ],
                'required' => false,
                'placeholder' => '-- 時間を選択 --',
            ])
            ->add('delivery_fee', MoneyType::class, [
                'label' => '配送料金',
                'currency' => 'JPY',
                'required' => false,
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('tracking_number', TextType::class, [
                'label' => '追跡番号',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ]);

        // 返却配送情報
        $builder
            ->add('return_delivery_date', DateTimeType::class, [
                'label' => '返却配送日',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datetimepicker',
                ],
            ])
            ->add('return_tracking_number', TextType::class, [
                'label' => '返却追跡番号',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('return_delivery_fee', MoneyType::class, [
                'label' => '返却配送料金',
                'currency' => 'JPY',
                'required' => false,
                'attr' => [
                    'min' => 0,
                ],
            ]);

        // 決済情報
        $builder
            ->add('payment_method', ChoiceType::class, [
                'label' => '支払い方法',
                'choices' => [
                    'クレジットカード' => 'credit',
                    '銀行振込' => 'bank',
                    'コンビニ決済' => 'convenience',
                    '現金' => 'cash',
                    '代金引換' => 'cod',
                ],
                'required' => false,
                'placeholder' => '-- 支払い方法を選択 --',
            ])
            ->add('payment_status', ChoiceType::class, [
                'label' => '支払いステータス',
                'choices' => [
                    '未払い' => 1,
                    '支払い済み' => 2,
                    '部分支払い' => 3,
                    '返金済み' => 4,
                    'キャンセル' => 5,
                ],
                'required' => false,
                'placeholder' => '-- 支払いステータスを選択 --',
            ])
            ->add('payment_date', DateTimeType::class, [
                'label' => '支払い日時',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datetimepicker',
                ],
            ]);

        // メモ・備考
        $builder
            ->add('note', TextareaType::class, [
                'label' => '顧客備考',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => '顧客からの要望・備考を入力...',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 1000]),
                ],
            ])
            ->add('admin_memo', TextareaType::class, [
                'label' => '管理者メモ',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => '管理者メモを入力...',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 1000]),
                ],
            ])
            ->add('product_condition_notes', TextareaType::class, [
                'label' => '商品状態メモ',
                'required' => false,
                'help' => '貸出時・返却時の商品状態を記録',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => '商品の状態を詳しく記録...',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 1000]),
                ],
            ])
            ->add('inspection_notes', TextareaType::class, [
                'label' => '検品メモ',
                'required' => false,
                'help' => '返却時の検品結果を記録',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => '検品結果を記録...',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 1000]),
                ],
            ]);

        // その他
        $builder
            ->add('priority_level', ChoiceType::class, [
                'label' => '優先度レベル',
                'choices' => [
                    '低' => 1,
                    '通常' => 2,
                    '高' => 3,
                    '緊急' => 4,
                ],
                'required' => false,
                'help' => '注文の処理優先度',
            ])
            ->add('contract_file', TextType::class, [
                'label' => '契約書ファイル',
                'required' => false,
                'help' => '契約書ファイルのパス',
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
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
            'data_class' => RentalOrder::class,
        ]);
    }

    /**
     * フォーム名を取得
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'rental_order';
    }
}