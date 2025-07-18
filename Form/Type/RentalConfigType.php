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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * レンタル設定フォーム
 */
class RentalConfigType extends AbstractType
{
    /**
     * フォーム構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // 基本設定
        $builder
            ->add('auto_approval', CheckboxType::class, [
                'label' => '自動承認',
                'help' => '有効にすると注文が自動的に承認されます',
                'required' => false,
            ])
            ->add('max_rental_days', IntegerType::class, [
                'label' => '最大レンタル日数',
                'help' => '1回のレンタルで許可される最大日数',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 1, 'max' => 365]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 365,
                ],
            ])
            ->add('min_rental_days', IntegerType::class, [
                'label' => '最小レンタル日数',
                'help' => '1回のレンタルで必要な最小日数',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 1, 'max' => 30]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 30,
                ],
            ])
            ->add('reminder_days', IntegerType::class, [
                'label' => 'リマインダー日数',
                'help' => '返却期限の何日前に通知するか',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 0, 'max' => 30]),
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 30,
                ],
            ])
            ->add('overdue_fee_rate', NumberType::class, [
                'label' => '延滞料金率',
                'help' => '延滞1日あたりの料金率（0.1 = 10%）',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 0, 'max' => 1]),
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.01,
                ],
                'scale' => 2,
            ]);

        // 保証金・支払い設定
        $builder
            ->add('deposit_required', CheckboxType::class, [
                'label' => '保証金必須',
                'help' => '有効にするとレンタル時に保証金が必要になります',
                'required' => false,
            ])
            ->add('payment_methods', ChoiceType::class, [
                'label' => '利用可能決済方法',
                'choices' => [
                    'クレジットカード' => 'credit',
                    '銀行振込' => 'bank',
                    'コンビニ決済' => 'convenience',
                    '現金' => 'cash',
                    '代金引換' => 'cod',
                ],
                'multiple' => true,
                'expanded' => true,
                'help' => 'レンタルで利用できる決済方法を選択してください',
            ])
            ->add('refund_policy', IntegerType::class, [
                'label' => '返金ポリシー（日数）',
                'help' => 'キャンセル時の返金期限（日）',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 0, 'max' => 30]),
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 30,
                ],
            ]);

        // 営業日・休日設定
        $builder
            ->add('business_days', ChoiceType::class, [
                'label' => '営業日',
                'choices' => [
                    '月曜日' => 1,
                    '火曜日' => 2,
                    '水曜日' => 3,
                    '木曜日' => 4,
                    '金曜日' => 5,
                    '土曜日' => 6,
                    '日曜日' => 7,
                ],
                'multiple' => true,
                'expanded' => true,
                'help' => 'レンタル可能な営業日を選択してください',
                'constraints' => [
                    new Assert\Count(['min' => 1, 'minMessage' => '営業日を最低1日は選択してください']),
                ],
            ])
            ->add('holiday_rental', CheckboxType::class, [
                'label' => '休日レンタル許可',
                'help' => '有効にすると営業日以外でもレンタル開始・終了が可能になります',
                'required' => false,
            ]);

        // 通知設定
        $builder
            ->add('notification_email', EmailType::class, [
                'label' => '通知メールアドレス',
                'help' => '管理者通知の送信先メールアドレス',
                'required' => false,
                'constraints' => [
                    new Assert\Email(['message' => '正しいメールアドレスを入力してください']),
                ],
            ]);

        // 配送設定
        $builder
            ->add('default_delivery_fee', MoneyType::class, [
                'label' => 'デフォルト配送料金',
                'help' => '標準的な配送料金',
                'currency' => 'JPY',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 0]),
                ],
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('free_delivery_amount', MoneyType::class, [
                'label' => '送料無料金額',
                'help' => 'この金額以上で送料無料',
                'currency' => 'JPY',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 0]),
                ],
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('delivery_company', TextType::class, [
                'label' => '配送会社',
                'help' => 'メインで利用する配送会社名',
                'constraints' => [
                    new Assert\Length(['max' => 100]),
                ],
            ])
            ->add('return_delivery_required', CheckboxType::class, [
                'label' => '返却配送必須',
                'help' => '有効にすると返却時に配送が必要になります',
                'required' => false,
            ])
            ->add('auto_tracking', CheckboxType::class, [
                'label' => '自動追跡機能',
                'help' => '有効にすると配送状況の自動追跡を行います',
                'required' => false,
            ]);

        // 機能設定
        $builder
            ->add('insurance_enabled', CheckboxType::class, [
                'label' => '保険機能有効',
                'help' => 'レンタル保険の利用を許可します',
                'required' => false,
            ])
            ->add('early_return_discount_enabled', CheckboxType::class, [
                'label' => '早期返却割引有効',
                'help' => '予定より早く返却した場合の割引機能',
                'required' => false,
            ])
            ->add('extension_enabled', CheckboxType::class, [
                'label' => '延長機能有効',
                'help' => 'レンタル期間の延長を許可します',
                'required' => false,
            ])
            ->add('replacement_enabled', CheckboxType::class, [
                'label' => '交換機能有効',
                'help' => 'レンタル中の商品交換を許可します',
                'required' => false,
            ])
            ->add('contract_required', CheckboxType::class, [
                'label' => '契約書必須',
                'help' => '有効にするとレンタル契約書の作成が必要になります',
                'required' => false,
            ])
            ->add('inspection_required', CheckboxType::class, [
                'label' => '検品必須',
                'help' => '返却時の検品を必須にします',
                'required' => false,
            ])
            ->add('priority_management', CheckboxType::class, [
                'label' => '優先度管理有効',
                'help' => '注文の優先度管理機能を有効にします',
                'required' => false,
            ])
            ->add('default_priority_level', ChoiceType::class, [
                'label' => 'デフォルト優先度レベル',
                'choices' => [
                    '低' => 1,
                    '通常' => 2,
                    '高' => 3,
                    '緊急' => 4,
                ],
                'help' => '新規注文のデフォルト優先度',
            ]);

        // システム設定
        $builder
            ->add('cleanup_old_cart_days', IntegerType::class, [
                'label' => 'カート自動削除日数',
                'help' => '古いカートデータを自動削除する日数',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 1, 'max' => 30]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 30,
                ],
            ])
            ->add('cleanup_old_log_days', IntegerType::class, [
                'label' => 'ログ自動削除日数',
                'help' => '古いログデータを自動削除する日数',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 30, 'max' => 365]),
                ],
                'attr' => [
                    'min' => 30,
                    'max' => 365,
                ],
            ])
            ->add('cleanup_old_notification_days', IntegerType::class, [
                'label' => '通知自動削除日数',
                'help' => '古い通知データを自動削除する日数',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 7, 'max' => 90]),
                ],
                'attr' => [
                    'min' => 7,
                    'max' => 90,
                ],
            ])
            ->add('enable_auto_cleanup', CheckboxType::class, [
                'label' => '自動削除機能有効',
                'help' => '古いデータの自動削除機能を有効にします',
                'required' => false,
            ]);

        // 利用規約
        $builder
            ->add('terms_of_service', TextareaType::class, [
                'label' => 'レンタル利用規約',
                'help' => 'レンタル時に表示される利用規約',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'レンタル利用規約を入力してください...',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 5000]),
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
            'data_class' => null,
        ]);
    }

    /**
     * フォーム名を取得
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'rental_config';
    }
}