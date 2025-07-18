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

namespace Plugin\Rental\Form\Extension\Admin;

use Eccube\Form\Type\Admin\ProductType;
use Plugin\Rental\Form\Type\RentalProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * 商品フォーム拡張クラス
 * 
 * 管理画面の商品編集フォームにレンタル設定を追加
 */
class ProductTypeExtension extends AbstractTypeExtension
{
    /**
     * フォーム構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // レンタル設定フォームを追加
        $builder->add('rental_setting', RentalProductType::class, [
            'label' => 'レンタル設定',
            'mapped' => false,
            'required' => false,
            'inherit_data' => false,
        ]);
    }

    /**
     * 拡張対象のフォームタイプを指定
     *
     * @return iterable
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }
}