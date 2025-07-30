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
use Eccube\Service\MailService;
use Plugin\Rental\Entity\RentalOrder;
use Plugin\Rental\Repository\RentalConfigRepository;
use Plugin\Rental\Repository\RentalLogRepository;
use Psr\Log\LoggerInterface;
use Twig\Environment;

/**
 * レンタル通知サービス
 */
class RentalNotificationService
{
    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @var RentalConfigRepository
     */
    private $configRepository;

    /**
     * @var RentalLogRepository
     */
    private $logRepository;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * コンストラクタ
     */
    public function __construct(
        MailService $mailService,
        RentalConfigRepository $configRepository,
        RentalLogRepository $logRepository,
        Environment $twig,
        LoggerInterface $logger
    ) {
        $this->mailService = $mailService;
        $this->configRepository = $configRepository;
        $this->logRepository = $logRepository;
        $this->twig = $twig;
        $this->logger = $logger;
    }

    /**
     * 注文作成通知を送信
     *
     * @param RentalOrder $order レンタル注文
     * @param Customer $customer 顧客
     */
    public function sendOrderCreatedNotification(RentalOrder $order, Customer $customer)
    {
        try {
            // 顧客向けメール
            $this->sendCustomerEmail(
                $customer,
                'レンタル注文確認',
                '@Rental/email/rental_order_confirm.twig',
                ['order' => $order]
            );

            // 管理者向けメール
            $adminEmail = $this->configRepository->getNotificationEmail();
            if ($adminEmail) {
                $this->sendAdminEmail(
                    $adminEmail,
                    '新規レンタル注文',
                    '@Rental/email/admin_new_order.twig',
                    ['order' => $order, 'customer' => $customer]
                );
            }

            // ログ記録
            $this->logRepository->log(
                'notification_sent',
                '注文作成通知を送信しました: ' . $order->getOrderNo(),
                'info',
                ['order_id' => $order->getId(), 'email' => $customer->getEmail()],
                $customer,
                $order
            );

        } catch (\Exception $e) {
            $this->logger->error('注文作成通知送信エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 注文承認通知を送信
     *
     * @param RentalOrder $order レンタル注文
     */
    public function sendOrderApprovedNotification(RentalOrder $order)
    {
        try {
            $customer = $order->getCustomer();
            
            $this->sendCustomerEmail(
                $customer,
                'レンタル注文承認のお知らせ',
                '@Rental/email/rental_order_approved.twig',
                ['order' => $order]
            );

            $this->logRepository->log(
                'notification_sent',
                '注文承認通知を送信しました: ' . $order->getOrderNo(),
                'info',
                ['order_id' => $order->getId()],
                $customer,
                $order
            );

        } catch (\Exception $e) {
            $this->logger->error('注文承認通知送信エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * レンタル開始通知を送信
     *
     * @param RentalOrder $order レンタル注文
     */
    public function sendRentalStartedNotification(RentalOrder $order)
    {
        try {
            $customer = $order->getCustomer();
            
            $this->sendCustomerEmail(
                $customer,
                'レンタル開始のお知らせ',
                '@Rental/email/rental_started.twig',
                ['order' => $order]
            );

            $this->logRepository->log(
                'notification_sent',
                'レンタル開始通知を送信しました: ' . $order->getOrderNo(),
                'info',
                ['order_id' => $order->getId()],
                $customer,
                $order
            );

        } catch (\Exception $e) {
            $this->logger->error('レンタル開始通知送信エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 返却完了通知を送信
     *
     * @param RentalOrder $order レンタル注文
     */
    public function sendReturnCompletedNotification(RentalOrder $order)
    {
        try {
            $customer = $order->getCustomer();
            
            $this->sendCustomerEmail(
                $customer,
                'レンタル返却完了のお知らせ',
                '@Rental/email/rental_return_completed.twig',
                ['order' => $order]
            );

            $this->logRepository->log(
                'notification_sent',
                '返却完了通知を送信しました: ' . $order->getOrderNo(),
                'info',
                ['order_id' => $order->getId()],
                $customer,
                $order
            );

        } catch (\Exception $e) {
            $this->logger->error('返却完了通知送信エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 延滞返却通知を送信
     *
     * @param RentalOrder $order レンタル注文
     * @param int $overdueDays 延滞日数
     */
    public function sendOverdueReturnNotification(RentalOrder $order, $overdueDays)
    {
        try {
            $customer = $order->getCustomer();
            
            $this->sendCustomerEmail(
                $customer,
                'レンタル延滞返却のお知らせ',
                '@Rental/email/rental_overdue_return.twig',
                [
                    'order' => $order,
                    'overdue_days' => $overdueDays,
                    'overdue_fee' => $order->getOverdueFee()
                ]
            );

            $this->logRepository->log(
                'notification_sent',
                '延滞返却通知を送信しました: ' . $order->getOrderNo(),
                'warning',
                [
                    'order_id' => $order->getId(),
                    'overdue_days' => $overdueDays
                ],
                $customer,
                $order
            );

        } catch (\Exception $e) {
            $this->logger->error('延滞返却通知送信エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 注文キャンセル通知を送信
     *
     * @param RentalOrder $order レンタル注文
     * @param string $reason キャンセル理由
     */
    public function sendOrderCancelledNotification(RentalOrder $order, $reason)
    {
        try {
            $customer = $order->getCustomer();
            
            $this->sendCustomerEmail(
                $customer,
                'レンタル注文キャンセルのお知らせ',
                '@Rental/email/rental_order_cancelled.twig',
                [
                    'order' => $order,
                    'cancel_reason' => $reason
                ]
            );

            $this->logRepository->log(
                'notification_sent',
                '注文キャンセル通知を送信しました: ' . $order->getOrderNo(),
                'info',
                [
                    'order_id' => $order->getId(),
                    'reason' => $reason
                ],
                $customer,
                $order
            );

        } catch (\Exception $e) {
            $this->logger->error('注文キャンセル通知送信エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * レンタル延長通知を送信
     *
     * @param RentalOrder $order レンタル注文
     * @param \DateTime $originalEndDate 元の終了日
     */
    public function sendRentalExtendedNotification(RentalOrder $order, \DateTime $originalEndDate)
    {
        try {
            $customer = $order->getCustomer();
            
            $this->sendCustomerEmail(
                $customer,
                'レンタル期間延長のお知らせ',
                '@Rental/email/rental_extended.twig',
                [
                    'order' => $order,
                    'original_end_date' => $originalEndDate,
                    'new_end_date' => $order->getRentalEndDate()
                ]
            );

            $this->logRepository->log(
                'notification_sent',
                'レンタル延長通知を送信しました: ' . $order->getOrderNo(),
                'info',
                ['order_id' => $order->getId()],
                $customer,
                $order
            );

        } catch (\Exception $e) {
            $this->logger->error('レンタル延長通知送信エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 返却リマインダー通知を送信
     *
     * @param RentalOrder $order レンタル注文
     * @param int $daysUntilReturn 返却まで日数
     */
    public function sendReturnReminderNotification(RentalOrder $order, $daysUntilReturn)
    {
        try {
            $customer = $order->getCustomer();
            
            $this->sendCustomerEmail(
                $customer,
                'レンタル返却日のリマインダー',
                '@Rental/email/rental_reminder.twig',
                [
                    'order' => $order,
                    'days_until_return' => $daysUntilReturn
                ]
            );

            $this->logRepository->log(
                'notification_sent',
                '返却リマインダー通知を送信しました: ' . $order->getOrderNo(),
                'info',
                [
                    'order_id' => $order->getId(),
                    'days_until_return' => $daysUntilReturn
                ],
                $customer,
                $order
            );

        } catch (\Exception $e) {
            $this->logger->error('返却リマインダー通知送信エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 延滞警告通知を送信
     *
     * @param RentalOrder $order レンタル注文
     * @param int $overdueDays 延滞日数
     */
    public function sendOverdueWarningNotification(RentalOrder $order, $overdueDays)
    {
        try {
            $customer = $order->getCustomer();
            
            $this->sendCustomerEmail(
                $customer,
                'レンタル延滞のお知らせ',
                '@Rental/email/rental_overdue.twig',
                [
                    'order' => $order,
                    'overdue_days' => $overdueDays
                ]
            );

            // 管理者にも通知
            $adminEmail = $this->configRepository->getNotificationEmail();
            if ($adminEmail) {
                $this->sendAdminEmail(
                    $adminEmail,
                    'レンタル延滞発生',
                    '@Rental/email/admin_overdue_alert.twig',
                    [
                        'order' => $order,
                        'customer' => $customer,
                        'overdue_days' => $overdueDays
                    ]
                );
            }

            $this->logRepository->log(
                'notification_sent',
                '延滞警告通知を送信しました: ' . $order->getOrderNo(),
                'warning',
                [
                    'order_id' => $order->getId(),
                    'overdue_days' => $overdueDays
                ],
                $customer,
                $order
            );

        } catch (\Exception $e) {
            $this->logger->error('延滞警告通知送信エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 在庫アラート通知を送信
     *
     * @param array $alertData 在庫アラートデータ
     */
    public function sendInventoryAlertNotification(array $alertData)
    {
        try {
            $adminEmail = $this->configRepository->getNotificationEmail();
            if (!$adminEmail) {
                return;
            }

            $this->sendAdminEmail(
                $adminEmail,
                '在庫アラート',
                '@Rental/email/admin_inventory_alert.twig',
                ['alerts' => $alertData]
            );

            $this->logRepository->log(
                'notification_sent',
                '在庫アラート通知を送信しました',
                'warning',
                ['alert_count' => count($alertData)]
            );

        } catch (\Exception $e) {
            $this->logger->error('在庫アラート通知送信エラー', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 決済失敗通知を送信
     *
     * @param RentalOrder $order レンタル注文
     * @param string $errorMessage エラーメッセージ
     */
    public function sendPaymentFailedNotification(RentalOrder $order, $errorMessage)
    {
        try {
            $customer = $order->getCustomer();
            
            $this->sendCustomerEmail(
                $customer,
                '決済エラーのお知らせ',
                '@Rental/email/payment_failed.twig',
                [
                    'order' => $order,
                    'error_message' => $errorMessage
                ]
            );

            $this->logRepository->log(
                'notification_sent',
                '決済失敗通知を送信しました: ' . $order->getOrderNo(),
                'error',
                [
                    'order_id' => $order->getId(),
                    'error_message' => $errorMessage
                ],
                $customer,
                $order
            );

        } catch (\Exception $e) {
            $this->logger->error('決済失敗通知送信エラー', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 顧客向けメール送信
     *
     * @param Customer $customer 顧客
     * @param string $subject 件名
     * @param string $template テンプレート
     * @param array $context コンテキスト
     */
    private function sendCustomerEmail(Customer $customer, $subject, $template, array $context = [])
    {
        $context['customer'] = $customer;
        
        $body = $this->twig->render($template, $context);
        
        $message = (new \Swift_Message())
            ->setSubject($subject)
            ->setFrom($this->configRepository->get('mail_from_address', 'noreply@example.com'))
            ->setTo($customer->getEmail())
            ->setBody($body, 'text/html');

        $this->mailService->send($message);
    }

    /**
     * 管理者向けメール送信
     *
     * @param string $adminEmail 管理者メールアドレス
     * @param string $subject 件名
     * @param string $template テンプレート
     * @param array $context コンテキスト
     */
    private function sendAdminEmail($adminEmail, $subject, $template, array $context = [])
    {
        $body = $this->twig->render($template, $context);
        
        $message = (new \Swift_Message())
            ->setSubject('[レンタル管理] ' . $subject)
            ->setFrom($this->configRepository->get('mail_from_address', 'noreply@example.com'))
            ->setTo($adminEmail)
            ->setBody($body, 'text/html');

        $this->mailService->send($message);
    }

    /**
     * SMS通知を送信（将来の拡張用）
     *
     * @param string $phoneNumber 電話番号
     * @param string $message メッセージ
     */
    public function sendSMSNotification($phoneNumber, $message)
    {
        // SMS送信の実装（外部サービス連携）
        // 現在は実装せず、ログのみ記録
        $this->logger->info('SMS通知送信（未実装）', [
            'phone' => $phoneNumber,
            'message' => $message
        ]);
    }

    /**
     * プッシュ通知を送信（将来の拡張用）
     *
     * @param Customer $customer 顧客
     * @param string $title タイトル
     * @param string $message メッセージ
     */
    public function sendPushNotification(Customer $customer, $title, $message)
    {
        // プッシュ通知の実装（Firebase等）
        // 現在は実装せず、ログのみ記録
        $this->logger->info('プッシュ通知送信（未実装）', [
            'customer_id' => $customer->getId(),
            'title' => $title,
            'message' => $message
        ]);
    }

    /**
     * 一括リマインダー送信
     *
     * @param array $orders レンタル注文配列
     */
    public function sendBulkReminders(array $orders)
    {
        $successCount = 0;
        $errorCount = 0;

        foreach ($orders as $order) {
            try {
                $daysUntilReturn = (new \DateTime())->diff($order->getRentalEndDate())->days;
                $this->sendReturnReminderNotification($order, $daysUntilReturn);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->logger->error('一括リマインダー送信エラー', [
                    'order_id' => $order->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('一括リマインダー送信完了', [
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);
    }
}