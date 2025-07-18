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

namespace Plugin\Rental\Service;

use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Plugin\Rental\Entity\RentalOrder;
use Plugin\Rental\Entity\RentalProduct;
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Exception\RentalValidationException;

/**
 * レンタル入力検証 Service
 */
class RentalValidationService
{
    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * コンストラクタ
     *
     * @param RentalConfigRepository $configRepository
     */
    public function __construct(RentalConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * レンタル期間を検証
     *
     * @param RentalProduct $rentalProduct レンタル商品
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @throws RentalValidationException
     */
    public function validateRentalPeriod(RentalProduct $rentalProduct, \DateTime $startDate, \DateTime $endDate)
    {
        // 日付の妥当性チェック
        if ($startDate >= $endDate) {
            throw new RentalValidationException('レンタル開始日は終了日より前である必要があります');
        }

        // 過去日チェック
        $today = new \DateTime('today');
        if ($startDate < $today) {
            throw new RentalValidationException('レンタル開始日は今日以降を選択してください');
        }

        // 準備日数チェック
        $preparationDays = $rentalProduct->getPreparationDays();
        if ($preparationDays > 0) {
            $minStartDate = new \DateTime();
            $minStartDate->add(new \DateInterval('P' . $preparationDays . 'D'));
            
            if ($startDate < $minStartDate) {
                throw new RentalValidationException(
                    sprintf('レンタル開始日は%d日後以降を選択してください（準備期間が必要です）', $preparationDays)
                );
            }
        }

        // レンタル期間チェック
        $days = $startDate->diff($endDate)->days + 1;
        
        if ($days < $rentalProduct->getMinRentalDays()) {
            throw new RentalValidationException(
                sprintf('最小レンタル期間は%d日です', $rentalProduct->getMinRentalDays())
            );
        }

        if ($rentalProduct->getMaxRentalDays() && $days > $rentalProduct->getMaxRentalDays()) {
            throw new RentalValidationException(
                sprintf('最大レンタル期間は%d日です', $rentalProduct->getMaxRentalDays())
            );
        }

        // 最大レンタル期間の全体制限
        $maxRentalDays = $this->configRepository->getMaxRentalDays();
        if ($days > $maxRentalDays) {
            throw new RentalValidationException(
                sprintf('レンタル期間は最大%d日までです', $maxRentalDays)
            );
        }

        // 営業日チェック
        if (!$this->configRepository->isHolidayRentalEnabled()) {
            $this->validateBusinessDays($startDate, $endDate);
        }
    }

    /**
     * 営業日を検証
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @throws RentalValidationException
     */
    public function validateBusinessDays(\DateTime $startDate, \DateTime $endDate)
    {
        $businessDays = $this->configRepository->getBusinessDays();
        
        // 開始日の営業日チェック
        $startDayOfWeek = $startDate->format('N'); // 1=月曜日, 7=日曜日
        if (!in_array($startDayOfWeek, $businessDays)) {
            throw new RentalValidationException('レンタル開始日は営業日を選択してください');
        }

        // 終了日の営業日チェック
        $endDayOfWeek = $endDate->format('N');
        if (!in_array($endDayOfWeek, $businessDays)) {
            throw new RentalValidationException('レンタル終了日は営業日を選択してください');
        }
    }

    /**
     * 数量を検証
     *
     * @param int $quantity 数量
     * @param int $maxQuantity 最大数量
     * @throws RentalValidationException
     */
    public function validateQuantity($quantity, $maxQuantity = null)
    {
        if (!is_int($quantity) || $quantity <= 0) {
            throw new RentalValidationException('数量は1以上の整数を入力してください');
        }

        if ($maxQuantity !== null && $quantity > $maxQuantity) {
            throw new RentalValidationException(
                sprintf('数量は最大%d個までです', $maxQuantity)
            );
        }
    }

    /**
     * 商品がレンタル可能かを検証
     *
     * @param Product $product 商品
     * @param RentalProduct|null $rentalProduct レンタル商品設定
     * @throws RentalValidationException
     */
    public function validateProductRentability(Product $product, RentalProduct $rentalProduct = null)
    {
        if (!$rentalProduct) {
            throw new RentalValidationException('この商品はレンタル対象外です');
        }

        if (!$rentalProduct->getIsRentalEnabled()) {
            throw new RentalValidationException('この商品は現在レンタル停止中です');
        }

        if (!$rentalProduct->hasPricingSetting()) {
            throw new RentalValidationException('この商品の料金設定が不正です');
        }
    }

    /**
     * 顧客がレンタル可能かを検証
     *
     * @param Customer $customer 顧客
     * @throws RentalValidationException
     */
    public function validateCustomerRentability(Customer $customer)
    {
        // 顧客のステータスチェック（今後の拡張用）
        if (!$customer) {
            throw new RentalValidationException('顧客情報が見つかりません');
        }

        // 延滞履歴チェック（今後の拡張用）
        // $overdueCount = $this->getCustomerOverdueCount($customer);
        // if ($overdueCount > $this->configRepository->getInt('max_overdue_count', 3)) {
        //     throw new RentalValidationException('延滞履歴が多いため、レンタルできません');
        // }
    }

    /**
     * 注文ステータスを検証
     *
     * @param RentalOrder $order 注文
     * @param array $allowedStatuses 許可されたステータス配列
     * @throws RentalValidationException
     */
    public function validateOrderStatus(RentalOrder $order, array $allowedStatuses)
    {
        if (!in_array($order->getStatus(), $allowedStatuses)) {
            $statusNames = [];
            foreach ($allowedStatuses as $status) {
                $statusNames[] = RentalOrder::STATUS_NAMES[$status] ?? $status;
            }
            
            throw new RentalValidationException(
                sprintf(
                    'この操作は注文ステータスが「%s」の場合のみ実行できます。現在のステータス：%s',
                    implode('」または「', $statusNames),
                    $order->getStatusName()
                )
            );
        }
    }

    /**
     * 金額を検証
     *
     * @param string $amount 金額
     * @param string $fieldName フィールド名
     * @throws RentalValidationException
     */
    public function validateAmount($amount, $fieldName = '金額')
    {
        if (!is_numeric($amount)) {
            throw new RentalValidationException($fieldName . 'は数値で入力してください');
        }

        if (bccomp($amount, '0', 2) < 0) {
            throw new RentalValidationException($fieldName . 'は0以上で入力してください');
        }

        // 最大金額チェック（999,999,999円）
        if (bccomp($amount, '999999999', 2) > 0) {
            throw new RentalValidationException($fieldName . 'が上限を超えています');
        }
    }

    /**
     * メールアドレスを検証
     *
     * @param string $email メールアドレス
     * @throws RentalValidationException
     */
    public function validateEmail($email)
    {
        if (empty($email)) {
            throw new RentalValidationException('メールアドレスを入力してください');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RentalValidationException('正しいメールアドレスを入力してください');
        }

        if (strlen($email) > 254) {
            throw new RentalValidationException('メールアドレスが長すぎます');
        }
    }

    /**
     * 電話番号を検証
     *
     * @param string $phone 電話番号
     * @throws RentalValidationException
     */
    public function validatePhone($phone)
    {
        if (empty($phone)) {
            throw new RentalValidationException('電話番号を入力してください');
        }

        // 数字、ハイフン、括弧のみ許可
        if (!preg_match('/^[0-9\-\(\)]+$/', $phone)) {
            throw new RentalValidationException('電話番号は数字、ハイフン、括弧のみで入力してください');
        }

        // 数字のみ抽出して桁数チェック
        $numbersOnly = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($numbersOnly) < 10 || strlen($numbersOnly) > 11) {
            throw new RentalValidationException('電話番号は10桁または11桁で入力してください');
        }
    }

    /**
     * 郵便番号を検証
     *
     * @param string $postalCode 郵便番号
     * @throws RentalValidationException
     */
    public function validatePostalCode($postalCode)
    {
        if (empty($postalCode)) {
            throw new RentalValidationException('郵便番号を入力してください');
        }

        // 数字とハイフンのみ許可
        if (!preg_match('/^[0-9\-]+$/', $postalCode)) {
            throw new RentalValidationException('郵便番号は数字とハイフンのみで入力してください');
        }

        // 7桁の数字（ハイフンなし）または3-4桁の形式
        $numbersOnly = preg_replace('/[^0-9]/', '', $postalCode);
        if (strlen($numbersOnly) !== 7) {
            throw new RentalValidationException('郵便番号は7桁で入力してください');
        }

        // 形式チェック（123-4567 または 1234567）
        if (!preg_match('/^\d{3}-?\d{4}$/', $postalCode)) {
            throw new RentalValidationException('郵便番号の形式が正しくありません（例：123-4567）');
        }
    }

    /**
     * 住所を検証
     *
     * @param string $address 住所
     * @param string $fieldName フィールド名
     * @throws RentalValidationException
     */
    public function validateAddress($address, $fieldName = '住所')
    {
        if (empty($address)) {
            throw new RentalValidationException($fieldName . 'を入力してください');
        }

        if (strlen($address) > 255) {
            throw new RentalValidationException($fieldName . 'は255文字以内で入力してください');
        }
    }

    /**
     * 名前を検証
     *
     * @param string $name 名前
     * @param string $fieldName フィールド名
     * @throws RentalValidationException
     */
    public function validateName($name, $fieldName = '名前')
    {
        if (empty($name)) {
            throw new RentalValidationException($fieldName . 'を入力してください');
        }

        if (strlen($name) > 100) {
            throw new RentalValidationException($fieldName . 'は100文字以内で入力してください');
        }
    }

    /**
     * 文字列長を検証
     *
     * @param string $value 値
     * @param int $maxLength 最大長
     * @param string $fieldName フィールド名
     * @param bool $required 必須かどうか
     * @throws RentalValidationException
     */
    public function validateStringLength($value, $maxLength, $fieldName, $required = false)
    {
        if ($required && empty($value)) {
            throw new RentalValidationException($fieldName . 'を入力してください');
        }

        if (!empty($value) && strlen($value) > $maxLength) {
            throw new RentalValidationException(
                sprintf('%sは%d文字以内で入力してください', $fieldName, $maxLength)
            );
        }
    }

    /**
     * 日付範囲を検証
     *
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param string $fieldName フィールド名
     * @throws RentalValidationException
     */
    public function validateDateRange(\DateTime $startDate, \DateTime $endDate, $fieldName = '期間')
    {
        if ($startDate >= $endDate) {
            throw new RentalValidationException($fieldName . 'の開始日は終了日より前である必要があります');
        }

        // 未来すぎる日付のチェック（5年後まで）
        $maxDate = new \DateTime();
        $maxDate->add(new \DateInterval('P5Y'));
        
        if ($startDate > $maxDate || $endDate > $maxDate) {
            throw new RentalValidationException($fieldName . 'は5年以内で設定してください');
        }
    }

    /**
     * 配列の要素数を検証
     *
     * @param array $array 配列
     * @param int $maxCount 最大要素数
     * @param string $fieldName フィールド名
     * @throws RentalValidationException
     */
    public function validateArrayCount(array $array, $maxCount, $fieldName)
    {
        if (count($array) > $maxCount) {
            throw new RentalValidationException(
                sprintf('%sは最大%d件まで選択できます', $fieldName, $maxCount)
            );
        }
    }

    /**
     * ファイルサイズを検証
     *
     * @param int $fileSize ファイルサイズ（バイト）
     * @param int $maxSize 最大サイズ（バイト）
     * @param string $fieldName フィールド名
     * @throws RentalValidationException
     */
    public function validateFileSize($fileSize, $maxSize, $fieldName = 'ファイル')
    {
        if ($fileSize > $maxSize) {
            $maxSizeMb = round($maxSize / 1024 / 1024, 1);
            throw new RentalValidationException(
                sprintf('%sのサイズは%sMB以下にしてください', $fieldName, $maxSizeMb)
            );
        }
    }

    /**
     * ファイル拡張子を検証
     *
     * @param string $filename ファイル名
     * @param array $allowedExtensions 許可された拡張子
     * @param string $fieldName フィールド名
     * @throws RentalValidationException
     */
    public function validateFileExtension($filename, array $allowedExtensions, $fieldName = 'ファイル')
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new RentalValidationException(
                sprintf(
                    '%sの形式が正しくありません。許可されている形式：%s',
                    $fieldName,
                    implode(', ', $allowedExtensions)
                )
            );
        }
    }

    /**
     * バリデーションエラーメッセージをまとめて処理
     *
     * @param array $validations バリデーション配列
     * @throws RentalValidationException
     */
    public function validateMultiple(array $validations)
    {
        $errors = [];

        foreach ($validations as $validation) {
            try {
                $validation();
            } catch (RentalValidationException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new RentalValidationException(implode("\n", $errors));
        }
    }
}