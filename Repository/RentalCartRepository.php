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

namespace Plugin\Rental\Repository;

use Eccube\Repository\AbstractRepository;
use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Plugin\Rental\Entity\RentalCart;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * レンタルカートデータアクセス Repository
 */
class RentalCartRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RentalCart::class);
    }

    /**
     * セッションIDでカート商品を取得
     *
     * @param string $sessionId セッションID
     * @return RentalCart[]
     */
    public function findBySessionId($sessionId)
    {
        return $this->createQueryBuilder('rc')
            ->where('rc.session_id = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('rc.create_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 顧客のカート商品を取得
     *
     * @param Customer $customer 顧客エンティティ
     * @return RentalCart[]
     */
    public function findByCustomer(Customer $customer)
    {
        return $this->createQueryBuilder('rc')
            ->where('rc.Customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('rc.create_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * セッションIDまたは顧客でカート商品を取得
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return RentalCart[]
     */
    public function findBySessionOrCustomer($sessionId, Customer $customer = null)
    {
        $qb = $this->createQueryBuilder('rc');

        if ($customer) {
            $qb->where('rc.Customer = :customer OR rc.session_id = :sessionId')
               ->setParameter('customer', $customer)
               ->setParameter('sessionId', $sessionId);
        } else {
            $qb->where('rc.session_id = :sessionId')
               ->setParameter('sessionId', $sessionId);
        }

        return $qb->orderBy('rc.create_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 商品と期間でカート商品を検索
     *
     * @param Product $product 商品エンティティ
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return RentalCart|null
     */
    public function findByProductAndPeriod(Product $product, \DateTime $startDate, \DateTime $endDate, $sessionId, Customer $customer = null)
    {
        $qb = $this->createQueryBuilder('rc')
            ->where('rc.Product = :product')
            ->andWhere('rc.rental_start_date = :startDate')
            ->andWhere('rc.rental_end_date = :endDate')
            ->setParameter('product', $product)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($customer) {
            $qb->andWhere('rc.Customer = :customer OR rc.session_id = :sessionId')
               ->setParameter('customer', $customer)
               ->setParameter('sessionId', $sessionId);
        } else {
            $qb->andWhere('rc.session_id = :sessionId')
               ->setParameter('sessionId', $sessionId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * カート商品を追加または更新
     *
     * @param Product $product 商品エンティティ
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param int $quantity 数量
     * @param string $calculatedPrice 計算価格
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @param string|null $note 備考
     * @return RentalCart
     */
    public function addOrUpdate(Product $product, \DateTime $startDate, \DateTime $endDate, $quantity, $calculatedPrice, $sessionId, Customer $customer = null, $note = null)
    {
        $cartItem = $this->findByProductAndPeriod($product, $startDate, $endDate, $sessionId, $customer);

        if ($cartItem) {
            // 既存のカート商品を更新
            $cartItem->setQuantity($cartItem->getQuantity() + $quantity);
            $cartItem->setCalculatedPrice($calculatedPrice);
            $cartItem->setUpdateDate(new \DateTime());
        } else {
            // 新規カート商品を作成
            $cartItem = new RentalCart();
            $cartItem->setProduct($product);
            $cartItem->setRentalStartDate($startDate);
            $cartItem->setRentalEndDate($endDate);
            $cartItem->setQuantity($quantity);
            $cartItem->setCalculatedPrice($calculatedPrice);
            $cartItem->setSessionId($sessionId);
            
            if ($customer) {
                $cartItem->setCustomer($customer);
            }
            
            if ($note) {
                $cartItem->setNote($note);
            }
        }

        $this->getEntityManager()->persist($cartItem);
        $this->getEntityManager()->flush();

        return $cartItem;
    }

    /**
     * カート商品数量を更新
     *
     * @param RentalCart $cartItem カート商品エンティティ
     * @param int $quantity 数量
     * @return void
     */
    public function updateQuantity(RentalCart $cartItem, $quantity)
    {
        if ($quantity <= 0) {
            $this->getEntityManager()->remove($cartItem);
        } else {
            $cartItem->setQuantity($quantity);
            $this->getEntityManager()->persist($cartItem);
        }
        
        $this->getEntityManager()->flush();
    }

    /**
     * カート商品を削除
     *
     * @param RentalCart $cartItem カート商品エンティティ
     * @return void
     */
    public function remove(RentalCart $cartItem)
    {
        $this->getEntityManager()->remove($cartItem);
        $this->getEntityManager()->flush();
    }

    /**
     * セッションIDのカートを全削除
     *
     * @param string $sessionId セッションID
     * @return void
     */
    public function clearBySessionId($sessionId)
    {
        $cartItems = $this->findBySessionId($sessionId);
        
        foreach ($cartItems as $cartItem) {
            $this->getEntityManager()->remove($cartItem);
        }
        
        $this->getEntityManager()->flush();
    }

    /**
     * 顧客のカートを全削除
     *
     * @param Customer $customer 顧客エンティティ
     * @return void
     */
    public function clearByCustomer(Customer $customer)
    {
        $cartItems = $this->findByCustomer($customer);
        
        foreach ($cartItems as $cartItem) {
            $this->getEntityManager()->remove($cartItem);
        }
        
        $this->getEntityManager()->flush();
    }

    /**
     * カートの合計金額を取得
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return string
     */
    public function getTotalAmount($sessionId, Customer $customer = null)
    {
        $cartItems = $this->findBySessionOrCustomer($sessionId, $customer);
        $total = '0';
        
        foreach ($cartItems as $cartItem) {
            $total = bcadd($total, $cartItem->getCalculatedPrice(), 2);
        }
        
        return $total;
    }

    /**
     * カートの商品数を取得
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return int
     */
    public function getItemCount($sessionId, Customer $customer = null)
    {
        $cartItems = $this->findBySessionOrCustomer($sessionId, $customer);
        return count($cartItems);
    }

    /**
     * カートの商品総数量を取得
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return int
     */
    public function getTotalQuantity($sessionId, Customer $customer = null)
    {
        $cartItems = $this->findBySessionOrCustomer($sessionId, $customer);
        $totalQuantity = 0;
        
        foreach ($cartItems as $cartItem) {
            $totalQuantity += $cartItem->getQuantity();
        }
        
        return $totalQuantity;
    }

    /**
     * 期間重複チェック
     *
     * @param Product $product 商品エンティティ
     * @param \DateTime $startDate 開始日
     * @param \DateTime $endDate 終了日
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return bool
     */
    public function hasConflictingPeriod(Product $product, \DateTime $startDate, \DateTime $endDate, $sessionId, Customer $customer = null)
    {
        $qb = $this->createQueryBuilder('rc')
            ->where('rc.Product = :product')
            ->andWhere('rc.rental_start_date < :endDate')
            ->andWhere('rc.rental_end_date > :startDate')
            ->setParameter('product', $product)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($customer) {
            $qb->andWhere('rc.Customer = :customer OR rc.session_id = :sessionId')
               ->setParameter('customer', $customer)
               ->setParameter('sessionId', $sessionId);
        } else {
            $qb->andWhere('rc.session_id = :sessionId')
               ->setParameter('sessionId', $sessionId);
        }

        return $qb->getQuery()->getOneOrNullResult() !== null;
    }

    /**
     * 古いカートデータを削除
     *
     * @param int $days 削除対象日数
     * @return int 削除件数
     */
    public function deleteOldCart($days = 7)
    {
        $targetDate = new \DateTime();
        $targetDate->sub(new \DateInterval('P' . $days . 'D'));

        return $this->createQueryBuilder('rc')
            ->delete()
            ->where('rc.create_date < :targetDate')
            ->setParameter('targetDate', $targetDate)
            ->getQuery()
            ->execute();
    }

    /**
     * セッションIDを顧客IDに移行
     *
     * @param string $sessionId セッションID
     * @param Customer $customer 顧客エンティティ
     * @return void
     */
    public function migrateSessionToCustomer($sessionId, Customer $customer)
    {
        // 既存の顧客カートを取得
        $existingCartItems = $this->findByCustomer($customer);
        $existingProducts = [];
        
        foreach ($existingCartItems as $item) {
            $key = $item->getProduct()->getId() . '_' . $item->getRentalStartDate()->format('Y-m-d') . '_' . $item->getRentalEndDate()->format('Y-m-d');
            $existingProducts[$key] = $item;
        }

        // セッションカートを取得
        $sessionCartItems = $this->findBySessionId($sessionId);
        
        foreach ($sessionCartItems as $sessionItem) {
            $key = $sessionItem->getProduct()->getId() . '_' . $sessionItem->getRentalStartDate()->format('Y-m-d') . '_' . $sessionItem->getRentalEndDate()->format('Y-m-d');
            
            if (isset($existingProducts[$key])) {
                // 既存の商品がある場合は数量を加算
                $existingProducts[$key]->setQuantity($existingProducts[$key]->getQuantity() + $sessionItem->getQuantity());
                $this->getEntityManager()->persist($existingProducts[$key]);
                $this->getEntityManager()->remove($sessionItem);
            } else {
                // 新規商品の場合は顧客を設定
                $sessionItem->setCustomer($customer);
                $this->getEntityManager()->persist($sessionItem);
            }
        }
        
        $this->getEntityManager()->flush();
    }

    /**
     * カートが空かどうかチェック
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return bool
     */
    public function isEmpty($sessionId, Customer $customer = null)
    {
        return $this->getItemCount($sessionId, $customer) === 0;
    }

    /**
     * カートの商品詳細を取得
     *
     * @param string $sessionId セッションID
     * @param Customer|null $customer 顧客エンティティ
     * @return array
     */
    public function getCartDetails($sessionId, Customer $customer = null)
    {
        $cartItems = $this->findBySessionOrCustomer($sessionId, $customer);
        $details = [];
        
        foreach ($cartItems as $cartItem) {
            $details[] = [
                'cart_item' => $cartItem,
                'product' => $cartItem->getProduct(),
                'quantity' => $cartItem->getQuantity(),
                'price' => $cartItem->getCalculatedPrice(),
                'rental_days' => $cartItem->getRentalStartDate()->diff($cartItem->getRentalEndDate())->days + 1,
                'start_date' => $cartItem->getRentalStartDate(),
                'end_date' => $cartItem->getRentalEndDate(),
                'note' => $cartItem->getNote(),
            ];
        }
        
        return $details;
    }
}