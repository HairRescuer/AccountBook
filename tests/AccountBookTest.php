<?php

namespace HairRescuer\AccountBook\Tests;

use HairRescuer\AccountBook\AccountBook;
use HairRescuer\AccountBook\Implementation\PDO\DataAccess;
use HairRescuer\AccountBook\SchemeConfig;
use PHPUnit\Framework\TestCase;

class AccountBookTest extends TestCase
{
    public function testPDO()
    {
        $pdo = new \PDO("mysql:host=127.0.0.1;dbname={$_ENV['dbname']}", $_ENV['dbuser'], $_ENV['dbpassword']);
        $dao = new DataAccess($pdo, new SchemeConfig());
        $accountbook = new AccountBook($dao);
        $userId = random_int(1000, 9999);

        $account1 = $accountbook->createAccount();
        $account2 = $accountbook->createAccount(['user_id' => $userId, 'account_name' => 'test' . strval($userId)]);
        $this->assertNotNull($account1);
        $this->assertNotNull($account2);

        $accountCopy1 = $accountbook->getAccountById($account1->getAccountId());
        $accountCopy2 = $accountbook->getAccountByConditions(['user_id' => $userId, 'account_name' => 'test' . strval($userId)]);
        $this->assertEquals($account1, $accountCopy1);
        $this->assertEquals($account2, $accountCopy2);

        $transaction1 = $account1->income(23);
        $this->assertNotEmpty($transaction1);
        $this->assertEquals(23, $account1->getBalance());
        list($transaction2, $transaction3) = $account1->transfer($account2, 14, ['attach' => 'test transfer']);
        $this->assertNotEmpty($transaction2);
        $this->assertEquals(9, $account1->getBalance());
        $this->assertEquals(14, $account2->getBalance());
        $transaction4 = $account2->expense(6, ['attach' => 'test expense']);
        $this->assertNotEmpty($transaction2);
        $this->assertEquals(8, $account2->getBalance());


        $transactionCopy4 = $accountbook->getTransaction($transaction4->getTransactionId());
        $this->assertEquals($transaction4, $transactionCopy4);

        $transaction5 = $transaction2->revert(['attach' => 'revert transfer']);
        $this->assertNotNull($transaction5);
        $this->assertEquals(23, $account1->getBalance());
        $this->assertEquals(-6, $account2->getBalance());

        $transactionCopy5 = $account1->getTransaction($transaction5->getTransactionId());
        $this->assertNotNull($transactionCopy5);
        $this->assertEquals($transaction2->getOppositeAccountId(), $transaction5->getOppositeAccountId());
    }
}