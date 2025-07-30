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
use Plugin\Rental\Repository\RentalOrderRepository;
use Plugin\Rental\Exception\RentalValidationException;

/**
 * レンタル入力検証 Service (完全版)
 */
class RentalValidationService
{
    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * @var RentalOrderRepository
     */
    private $orderRepository;

    /**
     * コンストラクタ
     *
     * @param RentalConfigRepository $configRepository
     * @param RentalOrderRepository $orderRepository
     */
    public function __construct(
        RentalConfigRepository $configRepository,
        RentalOrderRepository $orderRepository
    ) {
        $this->configRepository = $configRepository;
        $this->orderRepository = $orderRepository;
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
        $preparationDays = $rentalProduct->getPreparationDays() ?? 0;
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

        // 将来の制限日チェック
        $maxFutureDays = $this->configRepository->getInt('max_future_booking_days', 365);
        $maxFutureDate = new \DateTime();
        $maxFutureDate->add(new \DateInterval('P' . $maxFutureDays . 'D'));
        
        if ($startDate > $maxFutureDate) {
            throw new RentalValidationException(
                sprintf('レンタル開始日は%d日先までしか予約できません', $maxFutureDays)
            );
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

        $maxQuantityLimit = $this->configRepository->getInt('max_quantity_per_order', 10);
        if ($quantity > $maxQuantityLimit) {
            throw new RentalValidationException(
                sprintf('1注文あたりの最大数量は%d個です', $maxQuantityLimit)
            );
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

        if (!$product->getStatus()) {
            throw new RentalValidationException('この商品は現在販売停止中です');
        }

        // 料金設定の検証
        if (!$rentalProduct->getRentalPrice() || bccomp($rentalProduct->getRentalPrice(), '0', 2) <= 0) {
            throw new RentalValidationException('この商品の料金設定が不正です');
        }

        // 在庫設定の検証
        if (!$rentalProduct->getStockUnlimited() && $rentalProduct->getStock() <= 0) {
            throw new RentalValidationException('この商品は在庫切れです');
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
        if (!$customer) {
            throw new RentalValidationException('顧客情報が見つかりません');
        }

        // 顧客のステータスチェック
        if ($customer->getStatus() !== 1) { // 仮登録・退会顧客はレンタル不可
            throw new RentalValidationException('現在の顧客ステータスではレンタルできません');
        }

        // 延滞履歴チェック
        $overdueCount = $this->getCustomerOverdueCount($customer);
        $maxOverdueCount = $this->configRepository->getInt('max_overdue_count', 3);
        
        if ($overdueCount > $maxOverdueCount) {
            throw new RentalValidationException(
                sprintf('延滞履歴が%d回を超えているため、レンタルできません', $maxOverdueCount)
            );
        }

        // アクティブなレンタル数チェック
        $activeRentalCount = $this->getCustomerActiveRentalCount($customer);
        $maxActiveRentals = $this->configRepository->getInt('max_active_rentals_per_customer', 5);
        
        if ($activeRentalCount >= $maxActiveRentals) {
            throw new RentalValidationException(
                sprintf('同時レンタル可能数は%d件までです', $maxActiveRentals)
            );
        }

        // 未払い料金チェック
        $unpaidAmount = $this->getCustomerUnpaidAmount($customer);
        $maxUnpaidAmount = $this->configRepository->getFloat('max_unpaid_amount', 50000);
        
        if (bccomp($unpaidAmount, (string)$maxUnpaidAmount, 2) > 0) {
            throw new RentalValidationException(
                sprintf('未払い料金が%s円を超えているため、新規レンタルできません', number_format($maxUnpaidAmount))
            );
        }
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
                $statusNames[] = $this->getStatusName($status);
            }
            
            throw new RentalValidationException(
                sprintf(
                    'この操作は注文ステータスが「%s」の場合のみ実行できます。現在のステータス：%s',
                    implode('」または「', $statusNames),
                    $this->getStatusName($order->getStatus())
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
            throw new RentalValidationException($fieldName . 'は999,999,999円以下で入力してください');
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
            throw new RentalValidationException('メールアドレスは必須です');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RentalValidationException('有効なメールアドレスを入力してください');
        }

        if (strlen($email) > 254) {
            throw new RentalValidationException('メールアドレスは254文字以下で入力してください');
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
            throw new RentalValidationException('電話番号は必須です');
        }

        // 日本の電話番号形式をチェック
        if (!preg_match('/^0[0-9]{1,4}-?[0-9]{1,4}-?[0-9]{3,4}$/', $phone)) {
            throw new RentalValidationException('有効な電話番号を入力してください（例：03-1234-5678）');
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
            throw new RentalValidationException('郵便番号は必須です');
        }

        // 日本の郵便番号形式をチェック（例：123-4567）
        if (!preg_match('/^[0-9]{3}-?[0-9]{4}$/', $postalCode)) {
            throw new RentalValidationException('有効な郵便番号を入力してください（例：123-4567）');
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
            throw new RentalValidationException($fieldName . 'は必須です');
        }

        if (strlen($address) > 255) {
            throw new RentalValidationException($fieldName . 'は255文字以下で入力してください');
        }
    }

    /**
     * 名前を検証
     *
     * @param string $name01 姓
     * @param string $name02 名
     * @throws RentalValidationException
     */
    public function validateName($name01, $name02)
    {
        if (empty($name01)) {
            throw new RentalValidationException('姓は必須です');
        }

        if (empty($name02)) {
            throw new RentalValidationException('名は必須です');
        }

        if (strlen($name01) > 50) {
            throw new RentalValidationException('姓は50文字以下で入力してください');
        }

        if (strlen($name02) > 50) {
            throw new RentalValidationException('名は50文字以下で入力してください');
        }
    }

    /**
     * フリガナを検証
     *
     * @param string $kana01 セイ
     * @param string $kana02 メイ
     * @throws RentalValidationException
     */
    public function validateKana($kana01, $kana02)
    {
        if (empty($kana01)) {
            throw new RentalValidationException('セイ（カナ）は必須です');
        }

        if (empty($kana02)) {
            throw new RentalValidationException('メイ（カナ）は必須です');
        }

        // カタカナチェック
        if (!preg_match('/^[ァ-ヶー]+$/u', $kana01)) {
            throw new RentalValidationException('セイはカタカナで入力してください');
        }

        if (!preg_match('/^[ァ-ヶー]+$/u', $kana02)) {
            throw new RentalValidationException('メイはカタカナで入力してください');
        }
    }

    /**
     * 配送時間を検証
     *
     * @param string $deliveryTime 配送時間
     * @throws RentalValidationException
     */
    public function validateDeliveryTime($deliveryTime)
    {
        $allowedTimes = [
            '09:00-12:00',
            '12:00-14:00',
            '14:00-16:00',
            '16:00-18:00',
            '18:00-20:00',
            '20:00-21:00'
        ];

        if (!empty($deliveryTime) && !in_array($deliveryTime, $allowedTimes)) {
            throw new RentalValidationException('有効な配送時間を選択してください');
        }
    }

    /**
     * 特殊文字を検証
     *
     * @param string $input 入力値
     * @param string $fieldName フィールド名
     * @throws RentalValidationException
     */
    public function validateSpecialCharacters($input, $fieldName)
    {
        // SQLインジェクション対策
        $dangerousPatterns = [
            '/[<>"\']/',  // HTMLタグ関連
            '/union\s+select/i',  // SQLインジェクション
            '/script\s*:/i',  // JavaScriptプロトコル
            '/javascript\s*:/i'  // JavaScriptプロトコル
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new RentalValidationException($fieldName . 'に不正な文字が含まれています');
            }
        }
    }

    /**
     * ファイルアップロードを検証
     *
     * @param array $file アップロードファイル情報
     * @param array $allowedTypes 許可されるMIMEタイプ
     * @param int $maxSize 最大ファイルサイズ（バイト）
     * @throws RentalValidationException
     */
    public function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = 5242880)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RentalValidationException('ファイルのアップロードに失敗しました');
        }

        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 1);
            throw new RentalValidationException("ファイルサイズは{$maxSizeMB}MB以下にしてください");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new RentalValidationException('許可されていないファイル形式です');
        }
    }

    /**
     * 顧客の延滞回数を取得
     *
     * @param Customer $customer 顧客
     * @return int
     */
    private function getCustomerOverdueCount(Customer $customer)
    {
        return count($this->orderRepository->findByCustomerAndStatus($customer, RentalOrder::STATUS_OVERDUE));
    }

    /**
     * 顧客のアクティブなレンタル数を取得
     *
     * @param Customer $customer 顧客
     * @return int
     */
    private function getCustomerActiveRentalCount(Customer $customer)
    {
        $activeStatuses = [
            RentalOrder::STATUS_RESERVED,
            RentalOrder::STATUS_ACTIVE,
            RentalOrder::STATUS_OVERDUE
        ];

        $count = 0;
        foreach ($activeStatuses as $status) {
            $count += count($this->orderRepository->findByCustomerAndStatus($customer, $status));
        }

        return $count;
    }

    /**
     * 顧客の未払い金額を取得
     *
     * @param Customer $customer 顧客
     * @return string
     */
    private function getCustomerUnpaidAmount(Customer $customer)
    {
        // 実装では PaymentRepository を使用して未払い金額を計算
        // ここでは簡易実装
        return '0';
    }

    /**
     * ステータス名を取得
     *
     * @param int $status ステータス
     * @return string
     */
    private function getStatusName($status)
    {
        $statusNames = [
            0 => '仮注文',
            1 => '確定',
            2 => '予約中',
            3 => 'レンタル中',
            4 => '返却済み',
            5 => '延滞中',
            6 => 'キャンセル'
        ];

        return $statusNames[$status] ?? '不明';
    }

    /**
     * バッチ検証（複数項目の一括検証）
     *
     * @param array $data 検証データ
     * @param array $rules 検証ルール
     * @return array エラー配列
     */
    public function validateBatch(array $data, array $rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            try {
                $value = $data[$field] ?? null;

                if (isset($rule['required']) && $rule['required'] && empty($value)) {
                    $errors[$field] = ($rule['label'] ?? $field) . 'は必須です';
                    continue;
                }

                if (!empty($value)) {
                    switch ($rule['type']) {
                        case 'email':
                            $this->validateEmail($value);
                            break;
                        case 'phone':
                            $this->validatePhone($value);
                            break;
                        case 'postal_code':
                            $this->validatePostalCode($value);
                            break;
                        case 'amount':
                            $this->validateAmount($value, $rule['label'] ?? $field);
                            break;
                        case 'special_chars':
                            $this->validateSpecialCharacters($value, $rule['label'] ?? $field);
                            break;
                    }

                    if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                        $errors[$field] = ($rule['label'] ?? $field) . 'は' . $rule['max_length'] . '文字以下で入力してください';
                    }

                    if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                        $errors[$field] = ($rule['label'] ?? $field) . 'は' . $rule['min_length'] . '文字以上で入力してください';
                    }

                    if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                        $errors[$field] = ($rule['label'] ?? $field) . 'の形式が正しくありません';
                    }
                }

            } catch (RentalValidationException $e) {
                $errors[$field] = $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * 営業時間内かチェック
     *
     * @param \DateTime $dateTime 日時
     * @throws RentalValidationException
     */
    public function validateBusinessHours(\DateTime $dateTime)
    {
        $businessHourStart = $this->configRepository->get('business_hour_start', '09:00');
        $businessHourEnd = $this->configRepository->get('business_hour_end', '18:00');

        $timeString = $dateTime->format('H:i');

        if ($timeString < $businessHourStart || $timeString > $businessHourEnd) {
            throw new RentalValidationException(
                sprintf('営業時間外です。営業時間：%s～%s', $businessHourStart, $businessHourEnd)
            );
        }
    }

    /**
     * 休日かチェック
     *
     * @param \DateTime $date 日付
     * @throws RentalValidationException
     */
    public function validateHoliday(\DateTime $date)
    {
        // 基本的な祝日チェック（簡易実装）
        $holidays = [
            '01-01', // 元日
            '05-03', // 憲法記念日
            '05-04', // みどりの日
            '05-05', // こどもの日
            '12-31'  // 大晦日
        ];

        $dateString = $date->format('m-d');
        
        if (in_array($dateString, $holidays)) {
            throw new RentalValidationException('祝日はレンタル開始できません');
        }
    }

    /**
     * 年齢制限を検証
     *
     * @param Customer $customer 顧客
     * @param RentalProduct $rentalProduct レンタル商品
     * @throws RentalValidationException
     */
    public function validateAgeRestriction(Customer $customer, RentalProduct $rentalProduct)
    {
        $minAge = $rentalProduct->getMinAge() ?? 0;
        
        if ($minAge > 0 && $customer->getBirth()) {
            $age = $customer->getBirth()->diff(new \DateTime())->y;
            
            if ($age < $minAge) {
                throw new RentalValidationException(
                    sprintf('この商品は%d歳以上の方のみレンタル可能です', $minAge)
                );
            }
        }
    }

    /**
     * レンタル可能地域を検証
     *
     * @param string $postalCode 郵便番号
     * @throws RentalValidationException
     */
    public function validateRentalArea($postalCode)
    {
        $allowedAreas = $this->configRepository->getArray('allowed_postal_codes', []);
        
        if (!empty($allowedAreas)) {
            $isAllowed = false;
            
            foreach ($allowedAreas as $allowedArea) {
                if (strpos($postalCode, $allowedArea) === 0) {
                    $isAllowed = true;
                    break;
                }
            }
            
            if (!$isAllowed) {
                throw new RentalValidationException('お住まいの地域はレンタル対象外です');
            }
        }
    }
}