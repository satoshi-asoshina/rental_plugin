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

use Plugin\Rental\Entity\RentalProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * レンタル商品設定フォーム
 */
class RentalProductType extends AbstractType
{
    /**
     * フォーム構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // レンタル有効化
        $builder
            ->add('is_rental_enabled', CheckboxType::class, [
                'label' => 'レンタル機能を有効にする',
                'required' => false,
                'help' => 'チェックを入れるとこの商品がレンタル対象になります',
            ]);

        // 料金設定
        $builder
            ->add('daily_price', MoneyType::class, [
                'label' => '日額料金',
                'currency' => 'JPY',
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => '1日あたりの料金',
                ],
                'help' => '1日あたりのレンタル料金を入力してください',
            ])
            ->add('weekly_price', MoneyType::class, [
                'label' => '週額料金',
                'currency' => 'JPY',
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => '1週間あたりの料金',
                ],
                'help' => '1週間あたりのレンタル料金（日額より安い場合に適用）',
            ])
            ->add('monthly_price', MoneyType::class, [
                'label' => '月額料金',
                'currency' => 'JPY',
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => '1ヶ月あたりの料金',
                ],
                'help' => '1ヶ月あたりのレンタル料金（日額・週額より安い場合に適用）',
            ])
            ->add('deposit_amount', MoneyType::class, [
                'label' => '保証金額',
                'currency' => 'JPY',
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => '保証金額',
                ],
                'help' => 'レンタル時に預かる保証金額（任意）',
            ]);

        // レンタル期間設定
        $builder
            ->add('min_rental_days', IntegerType::class, [
                'label' => '最小レンタル日数',
                'constraints' => [
                    new Assert\NotBlank(['message' => '最小レンタル日数を入力してください']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 365,
                        'minMessage' => '最小レンタル日数は1日以上で入力してください',
                        'maxMessage' => '最小レンタル日数は365日以下で入力してください',
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 365,
                ],
                'data' => 1,
                'help' => 'この商品の最小レンタル期間',
            ])
            ->add('max_rental_days', IntegerType::class, [
                'label' => '最大レンタル日数',
                'required' => false,
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 365,
                        'minMessage' => '最大レンタル日数は1日以上で入力してください',
                        'maxMessage' => '最大レンタル日数は365日以下で入力してください',
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 365,
                    'placeholder' => '制限なしの場合は空白',
                ],
                'help' => 'この商品の最大レンタル期間（空白の場合は制限なし）',
            ])
            ->add('preparation_days', IntegerType::class, [
                'label' => '準備日数',
                'required' => false,
                'constraints' => [
                    new Assert\Range([
                        'min' => 0,
                        'max' => 30,
                        'minMessage' => '準備日数は0日以上で入力してください',
                        'maxMessage' => '準備日数は30日以下で入力してください',
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 30,
                    'placeholder' => '準備が不要な場合は0',
                ],
                'data' => 0,
                'help' => 'レンタル開始前に必要な準備期間',
            ]);

        // 自動承認設定
        $builder
            ->add('auto_approval', CheckboxType::class, [
                'label' => '自動承認',
                'required' => false,
                'help' => 'チェックを入れるとこの商品の注文が自動的に承認されます',
            ]);

        // 拡張料金設定
        $builder
            ->add('insurance_fee', MoneyType::class, [
                'label' => '保険料（日額）',
                'currency' => 'JPY',
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => '1日あたりの保険料',
                ],
                'help' => '1日あたりの保険料（任意）',
            ])
            ->add('early_return_discount', NumberType::class, [
                'label' => '早期返却割引率',
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0, 'max' => 1]),
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.01,
                    'placeholder' => '0.1 = 10%割引',
                ],
                'scale' => 2,
                'help' => '早期返却時の割引率（0.1 = 10%割引）',
            ])
            ->add('extension_fee_rate', NumberType::class, [
                'label' => '延長料金率',
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0, 'max' => 10]),
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 10,
                    'step' => 0.01,
                    'placeholder' => '1.2 = 20%割増',
                ],
                'scale' => 2,
                'help' => '延長時の料金率（1.2 = 20%割増）',
            ])
            ->add('replacement_fee', MoneyType::class, [
                'label' => '交換手数料',
                'currency' => 'JPY',
                'required' => false,
                'constraints' => [
                    new Assert\Range(['min' => 0]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => '交換時の手数料',
                ],
                'help' => 'レンタル中の商品交換時の手数料',
            ]);

        // メンテナンス設定
        $builder
            ->add('maintenance_cycle', IntegerType::class, [
                'label' => 'メンテナンス周期（日）',
                'required' => false,
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 365,
                        'minMessage' => 'メンテナンス周期は1日以上で入力してください',
                        'maxMessage' => 'メンテナンス周期は365日以下で入力してください',
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 365,
                    'placeholder' => 'メンテナンス不要の場合は空白',
                ],
                'help' => '定期メンテナンスが必要な場合の周期',
            ]);

        // オプション設定
        $builder
            ->add('category_options', TextType::class, [
                'label' => 'カテゴリオプション',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 1000]),
                ],
                'attr' => [
                    'placeholder' => 'JSON形式でオプションを設定',
                ],
                'help' => 'カテゴリ別のオプション設定（JSON形式）',
            ])
            ->add('size_options', TextType::class, [
                'label' => 'サイズオプション',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 1000]),
                ],
                'attr' => [
                    'placeholder' => 'JSON形式でサイズオプションを設定',
                ],
                'help' => 'サイズ選択が必要な場合のオプション設定（JSON形式）',
            ])
            ->add('color_options', TextType::class, [
                'label' => 'カラーオプション',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 1000]),
                ],
                'attr' => [
                    'placeholder' => 'JSON形式でカラーオプションを設定',
                ],
                'help' => 'カラー選択が必要な場合のオプション設定（JSON形式）',
            ]);

        // 注意事項・取扱い指示
        $builder
            ->add('rental_note', TextareaType::class, [
                'label' => 'レンタル注意事項',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'この商品のレンタルに関する注意事項を入力...',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 2000]),
                ],
                'help' => '顧客に表示されるレンタル注意事項',
            ])
            ->add('special_instructions', TextareaType::class, [
                'label' => '特別な取扱い指示',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => '管理者向けの特別な取扱い指示を入力...',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 1000]),
                ],
                'help' => '管理者向けの特別な取扱い指示（顧客には表示されません）',
            ]);

        // 料金計算方式設定
        $builder
            ->add('pricing_method', ChoiceType::class, [
                'label' => '料金計算方式',
                'choices' => [
                    '最安値を自動選択' => 'auto',
                    '日額優先' => 'daily',
                    '週額優先' => 'weekly',
                    '月額優先' => 'monthly',
                ],
                'required' => false,
                'data' => 'auto',
                'help' => '複数の料金設定がある場合の計算方式',
            ]);

        // 在庫連携設定
        $builder
            ->add('stock_linked', CheckboxType::class, [
                'label' => '在庫連携',
                'required' => false,
                'help' => 'チェックを入れると通常の在庫とレンタル在庫を連携します',
            ])
            ->add('rental_stock_quantity', IntegerType::class, [
                'label' => 'レンタル専用在庫数',
                'required' => false,
                'constraints' => [
                    new Assert\Range([
                        'min' => 0,
                        'max' => 9999,
                        'minMessage' => 'レンタル在庫数は0以上で入力してください',
                        'maxMessage' => 'レンタル在庫数は9999以下で入力してください',
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 9999,
                    'placeholder' => '在庫連携しない場合の専用在庫数',
                ],
                'help' => '在庫連携しない場合のレンタル専用在庫数',
            ]);

        // 利用制限設定
        $builder
            ->add('age_restriction', ChoiceType::class, [
                'label' => '年齢制限',
                'choices' => [
                    '制限なし' => 0,
                    '18歳以上' => 18,
                    '20歳以上' => 20,
                ],
                'required' => false,
                'data' => 0,
                'help' => 'レンタル可能な最低年齢',
            ])
            ->add('license_required', CheckboxType::class, [
                'label' => '免許・資格必須',
                'required' => false,
                'help' => 'チェックを入れると免許や資格の確認が必要になります',
            ])
            ->add('experience_required', CheckboxType::class, [
                'label' => '経験者限定',
                'required' => false,
                'help' => 'チェックを入れると使用経験のある顧客に限定されます',
            ]);

        // 季節・期間限定設定
        $builder
            ->add('seasonal_available', CheckboxType::class, [
                'label' => '季節・期間限定',
                'required' => false,
                'help' => 'チェックを入れると特定の期間のみレンタル可能になります',
            ])
            ->add('available_start_date', DateType::class, [
                'label' => 'レンタル開始可能日',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
                'help' => '季節・期間限定の場合の開始日',
            ])
            ->add('available_end_date', DateType::class, [
                'label' => 'レンタル終了可能日',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                ],
                'help' => '季節・期間限定の場合の終了日',
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
            'data_class' => RentalProduct::class,
        ]);
    }

    /**
     * フォーム名を取得
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'rental_product';
    }
}