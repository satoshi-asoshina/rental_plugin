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

namespace Plugin\Rental\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Form\Type\RentalConfigType;
use Plugin\Rental\Exception\RentalValidationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * レンタル設定管理コントローラー
 * 
 * @Route("/%eccube_admin_route%/rental/config")
 */
class RentalConfigController extends AbstractController
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
     * 設定一覧・編集画面
     *
     * @Route("", name="admin_rental_config", methods={"GET", "POST"})
     * @Template("@Rental/admin/config.twig")
     * 
     * @param Request $request
     * @return array|Response
     */
    public function index(Request $request)
    {
        // 現在の設定値を取得
        $configData = $this->getCurrentConfigData();
        
        // フォーム作成
        $form = $this->createForm(RentalConfigType::class, $configData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formData = $form->getData();
                $this->saveConfigData($formData);

                $this->addSuccess('設定を保存しました。', 'admin');
                
                return $this->redirectToRoute('admin_rental_config');
                
            } catch (RentalValidationException $e) {
                $this->addError($e->getMessage(), 'admin');
            } catch (\Exception $e) {
                $this->addError('設定の保存に失敗しました。', 'admin');
                log_error('レンタル設定保存エラー', ['error' => $e->getMessage()]);
            }
        }

        return [
            'form' => $form->createView(),
            'config_data' => $configData,
            'statistics' => $this->getConfigStatistics(),
        ];
    }

    /**
     * 設定リセット
     *
     * @Route("/reset", name="admin_rental_config_reset", methods={"POST"})
     * 
     * @param Request $request
     * @return Response
     */
    public function reset(Request $request)
    {
        $this->isTokenValid();

        try {
            $this->resetToDefaultConfig();
            $this->addSuccess('設定をデフォルト値にリセットしました。', 'admin');
        } catch (\Exception $e) {
            $this->addError('設定のリセットに失敗しました。', 'admin');
            log_error('レンタル設定リセットエラー', ['error' => $e->getMessage()]);
        }

        return $this->redirectToRoute('admin_rental_config');
    }

    /**
     * 設定エクスポート
     *
     * @Route("/export", name="admin_rental_config_export", methods={"GET"})
     * 
     * @return Response
     */
    public function export()
    {
        try {
            $configs = $this->configRepository->backup();
            $json = json_encode($configs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $response = new Response($json);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename="rental_config_' . date('Y-m-d_H-i-s') . '.json"');

            return $response;
            
        } catch (\Exception $e) {
            $this->addError('設定のエクスポートに失敗しました。', 'admin');
            log_error('レンタル設定エクスポートエラー', ['error' => $e->getMessage()]);
            
            return $this->redirectToRoute('admin_rental_config');
        }
    }

    /**
     * 設定インポート
     *
     * @Route("/import", name="admin_rental_config_import", methods={"POST"})
     * 
     * @param Request $request
     * @return Response
     */
    public function import(Request $request)
    {
        $this->isTokenValid();

        $uploadedFile = $request->files->get('config_file');
        
        if (!$uploadedFile || !$uploadedFile->isValid()) {
            $this->addError('有効なファイルを選択してください。', 'admin');
            return $this->redirectToRoute('admin_rental_config');
        }

        try {
            $content = file_get_contents($uploadedFile->getPathname());
            $configs = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('JSONファイルの形式が正しくありません。');
            }

            $this->validateImportData($configs);
            $this->configRepository->restore($configs);

            $this->addSuccess('設定をインポートしました。', 'admin');
            
        } catch (\Exception $e) {
            $this->addError('設定のインポートに失敗しました: ' . $e->getMessage(), 'admin');
            log_error('レンタル設定インポートエラー', ['error' => $e->getMessage()]);
        }

        return $this->redirectToRoute('admin_rental_config');
    }

    /**
     * 設定値チェック
     *
     * @Route("/validate", name="admin_rental_config_validate", methods={"POST"})
     * 
     * @param Request $request
     * @return Response
     */
    public function validate(Request $request)
    {
        $this->isTokenValid();

        try {
            $validationResults = $this->validateAllConfigs();
            
            if (empty($validationResults['errors'])) {
                $this->addSuccess('すべての設定が正常です。', 'admin');
            } else {
                foreach ($validationResults['errors'] as $error) {
                    $this->addError($error, 'admin');
                }
            }

            return $this->json([
                'success' => empty($validationResults['errors']),
                'results' => $validationResults,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => '設定の検証に失敗しました。',
            ], 500);
        }
    }

    /**
     * 現在の設定データを取得
     *
     * @return array
     */
    private function getCurrentConfigData()
    {
        return [
            'auto_approval' => $this->configRepository->getBoolean('auto_approval', false),
            'max_rental_days' => $this->configRepository->getInt('max_rental_days', 30),
            'min_rental_days' => $this->configRepository->getInt('min_rental_days', 1),
            'reminder_days' => $this->configRepository->getInt('reminder_days', 3),
            'overdue_fee_rate' => $this->configRepository->getFloat('overdue_fee_rate', 0.1),
            'deposit_required' => $this->configRepository->getBoolean('deposit_required', false),
            'business_days' => $this->configRepository->getArray('business_days', [1,2,3,4,5]),
            'holiday_rental' => $this->configRepository->getBoolean('holiday_rental', true),
            'notification_email' => $this->configRepository->get('notification_email', ''),
            'terms_of_service' => $this->configRepository->get('terms_of_service', ''),
            'default_delivery_fee' => $this->configRepository->getFloat('default_delivery_fee', 500),
            'free_delivery_amount' => $this->configRepository->getFloat('free_delivery_amount', 5000),
            'delivery_company' => $this->configRepository->get('delivery_company', 'ヤマト運輸'),
            'return_delivery_required' => $this->configRepository->getBoolean('return_delivery_required', true),
            'auto_tracking' => $this->configRepository->getBoolean('auto_tracking', false),
            'insurance_enabled' => $this->configRepository->getBoolean('insurance_enabled', true),
            'early_return_discount_enabled' => $this->configRepository->getBoolean('early_return_discount_enabled', true),
            'extension_enabled' => $this->configRepository->getBoolean('extension_enabled', true),
            'replacement_enabled' => $this->configRepository->getBoolean('replacement_enabled', true),
            'contract_required' => $this->configRepository->getBoolean('contract_required', false),
            'inspection_required' => $this->configRepository->getBoolean('inspection_required', true),
            'priority_management' => $this->configRepository->getBoolean('priority_management', true),
            'payment_methods' => $this->configRepository->getArray('payment_methods', []),
            'refund_policy' => $this->configRepository->getInt('refund_policy', 7),
            'default_priority_level' => $this->configRepository->getInt('default_priority_level', 1),
            'cleanup_old_cart_days' => $this->configRepository->getInt('cleanup_old_cart_days', 7),
            'cleanup_old_log_days' => $this->configRepository->getInt('cleanup_old_log_days', 90),
            'cleanup_old_notification_days' => $this->configRepository->getInt('cleanup_old_notification_days', 30),
            'enable_auto_cleanup' => $this->configRepository->getBoolean('enable_auto_cleanup', true),
        ];
    }

    /**
     * 設定データを保存
     *
     * @param array $configData
     * @throws RentalValidationException
     */
    private function saveConfigData(array $configData)
    {
        // バリデーション
        $this->validateConfigData($configData);

        // 特別な処理が必要な設定
        $processedData = $this->processConfigData($configData);

        // 一括保存
        $this->configRepository->setMultiple($processedData);
        
        log_info('レンタル設定保存完了', ['admin_user' => $this->getUser()->getId()]);
    }

    /**
     * 設定データを検証
     *
     * @param array $configData
     * @throws RentalValidationException
     */
    private function validateConfigData(array $configData)
    {
        // 最大・最小レンタル日数の論理チェック
        if ($configData['max_rental_days'] < $configData['min_rental_days']) {
            throw new RentalValidationException('最大レンタル日数は最小レンタル日数以上である必要があります。');
        }

        // リマインダー日数のチェック
        if ($configData['reminder_days'] < 0 || $configData['reminder_days'] > 30) {
            throw new RentalValidationException('リマインダー日数は0から30の間で設定してください。');
        }

        // 延滞料金率のチェック
        if ($configData['overdue_fee_rate'] < 0 || $configData['overdue_fee_rate'] > 1) {
            throw new RentalValidationException('延滞料金率は0から1の間で設定してください。');
        }

        // 営業日のチェック
        if (empty($configData['business_days'])) {
            throw new RentalValidationException('営業日を最低1日は選択してください。');
        }

        // メールアドレスのチェック
        if (!empty($configData['notification_email']) && !filter_var($configData['notification_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RentalValidationException('通知メールアドレスの形式が正しくありません。');
        }

        // 配送料金のチェック
        if (!is_numeric($configData['default_delivery_fee']) || $configData['default_delivery_fee'] < 0) {
            throw new RentalValidationException('配送料金は0以上の数値で設定してください。');
        }

        if (!is_numeric($configData['free_delivery_amount']) || $configData['free_delivery_amount'] < 0) {
            throw new RentalValidationException('送料無料金額は0以上の数値で設定してください。');
        }
    }

    /**
     * 設定データを処理
     *
     * @param array $configData
     * @return array
     */
    private function processConfigData(array $configData)
    {
        $processed = [];

        foreach ($configData as $key => $value) {
            switch ($key) {
                case 'business_days':
                    $processed[$key] = is_array($value) ? implode(',', $value) : $value;
                    break;
                
                case 'payment_methods':
                    $processed[$key] = is_array($value) ? json_encode($value) : $value;
                    break;
                
                default:
                    $processed[$key] = is_bool($value) ? ($value ? '1' : '0') : (string)$value;
                    break;
            }
        }

        return $processed;
    }

    /**
     * デフォルト設定にリセット
     */
    private function resetToDefaultConfig()
    {
        $defaultConfigs = [
            'auto_approval' => '0',
            'max_rental_days' => '30',
            'min_rental_days' => '1',
            'reminder_days' => '3',
            'overdue_fee_rate' => '0.1',
            'deposit_required' => '0',
            'business_days' => '1,2,3,4,5',
            'holiday_rental' => '1',
            'notification_email' => '',
            'terms_of_service' => '',
            'default_delivery_fee' => '500',
            'free_delivery_amount' => '5000',
            'delivery_company' => 'ヤマト運輸',
            'return_delivery_required' => '1',
            'auto_tracking' => '0',
            'insurance_enabled' => '1',
            'early_return_discount_enabled' => '1',
            'extension_enabled' => '1',
            'replacement_enabled' => '1',
            'contract_required' => '0',
            'inspection_required' => '1',
            'priority_management' => '1',
            'payment_methods' => '{"cash":"現金決済","bank":"銀行振込","convenience":"コンビニ決済"}',
            'refund_policy' => '7',
            'default_priority_level' => '1',
            'cleanup_old_cart_days' => '7',
            'cleanup_old_log_days' => '90',
            'cleanup_old_notification_days' => '30',
            'enable_auto_cleanup' => '1',
        ];

        $this->configRepository->setMultiple($defaultConfigs);
    }

    /**
     * インポートデータを検証
     *
     * @param array $configs
     * @throws \InvalidArgumentException
     */
    private function validateImportData($configs)
    {
        if (!is_array($configs)) {
            throw new \InvalidArgumentException('設定データの形式が正しくありません。');
        }

        $requiredKeys = ['auto_approval', 'max_rental_days', 'min_rental_days'];
        foreach ($requiredKeys as $key) {
            if (!isset($configs[$key])) {
                throw new \InvalidArgumentException("必須設定項目 '{$key}' が見つかりません。");
            }
        }
    }

    /**
     * 全設定の検証
     *
     * @return array
     */
    private function validateAllConfigs()
    {
        $results = [
            'valid' => [],
            'errors' => [],
            'warnings' => [],
        ];

        $configData = $this->getCurrentConfigData();

        try {
            $this->validateConfigData($configData);
            $results['valid'][] = '基本設定チェック完了';
        } catch (RentalValidationException $e) {
            $results['errors'][] = $e->getMessage();
        }

        // 追加のビジネスロジックチェック
        if ($configData['max_rental_days'] > 365) {
            $results['warnings'][] = '最大レンタル日数が1年を超えています。';
        }

        if (empty($configData['notification_email'])) {
            $results['warnings'][] = '通知メールアドレスが設定されていません。';
        }

        return $results;
    }

    /**
     * 設定統計情報を取得
     *
     * @return array
     */
    private function getConfigStatistics()
    {
        return [
            'total_configs' => $this->configRepository->count([]),
            'last_updated' => $this->configRepository->getLastUpdated(),
            'auto_approval_enabled' => $this->configRepository->getBoolean('auto_approval', false),
            'payment_methods_count' => count($this->configRepository->getArray('payment_methods', [])),
        ];
    }
}