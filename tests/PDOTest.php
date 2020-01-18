<?php

namespace HairRescuer\AccountBook\Tests;

use Exception;
use HairRescuer\AccountBook\Account;
use HairRescuer\AccountBook\Implementation\PDO\DataAccess;
use HairRescuer\AccountBook\SchemeConfig;
use PHPUnit\Framework\TestCase;

class PDOTest extends TestCase
{
    protected static $pdo;
    protected static $dao;
    protected static $userId;

    public static function setUpBeforeClass()
    {
        self::$pdo = new \PDO("mysql:host=127.0.0.1;dbname={$_ENV['dbname']}", $_ENV['dbuser'], $_ENV['dbpassword']);
//        self::$pdo = new \PDO("sqlite:" . __DIR__ . "/data.db");
        self::$dao = new DataAccess(self::$pdo, new SchemeConfig());
        self::$userId = random_int(1000, 9999);
    }

    public function testCreateAccount()
    {
        //create account
        $account = self::$dao->createAccount();
        $this->assertNotEmpty($account);

        return $account;
    }

    public function testCreateAccountWithExtraData()
    {
        //create account with extra data
        $account = self::$dao->createAccount(['user_id' => self::$userId, 'account_name' => 'test' . strval(self::$userId)]);
        $this->assertNotEmpty($account);

        return $account;
    }

    /**
     * @depends testCreateAccount
     * @depends testCreateAccountWithExtraData
     *
     * @param Account $account1
     * @param Account $account2
     * @throws Exception
     */
    public function testFindAccount(Account $account1, Account $account2)
    {
        //find account by id
        $accountCopy1 = self::$dao->findAccountById($account1->getAccountId());
        $this->assertEquals($account1->getAccountId(), $accountCopy1->getAccountId());
        //find account by conditions
        $accountCopy2 = self::$dao->findAccountByConditions(['user_id' => self::$userId, 'account_name' => 'test' . strval(self::$userId)]);
        $this->assertEquals($account2->getAccountId(), $accountCopy2->getAccountId());
    }

    /**
     * @depends testCreateAccount
     * @depends testCreateAccountWithExtraData
     *
     * @param Account $account1
     * @param Account $account2
     * @throws Exception
     */
    public function testTransaction(Account $account1, Account $account2)
    {
        $transaction1 = self::$dao->createTransaction($account1->getAccountId(), null, 15);
        $this->assertNotEmpty($transaction1);
        $transaction2 = self::$dao->createTransaction($account1->getAccountId(), $account2->getAccountId(), -8);
        $this->assertNotEmpty($transaction2);
        $transaction3 = self::$dao->createTransaction($account2->getAccountId(), $account1->getAccountId(), 8);
        $this->assertNotEmpty($transaction3);
        $transaction4 = self::$dao->createTransaction($account2->getAccountId(), null, -7, ['attach' => 'test' . strval(self::$userId)]);
        $this->assertNotEmpty($transaction4);

        $transactionCopy2 = self::$dao->findTransaction($transaction2->getTransactionId(), $account1->getAccountId());
        $transactionCopy3 = self::$dao->findTransaction($transaction3->getTransactionId());
        $this->assertEquals($transaction2, $transactionCopy2);
        $this->assertEquals($transactionCopy2->getAccountId(), $transactionCopy3->getOppositeAccountId());
        $this->assertEquals(-($transactionCopy2->getAmount()), $transactionCopy3->getAmount());
    }

    /**
     * @depends testCreateAccount
     * @depends testCreateAccountWithExtraData
     * @depends testTransaction
     *
     * @param Account $account1
     * @param Account $account2
     * @throws Exception
     */
    public function testBalance(Account $account1, Account $account2)
    {
        //balance
        $this->assertTrue(self::$dao->updateBalance($account2->getAccountId(), 10));
        $this->assertEquals(10, self::$dao->getBalance($account2->getAccountId()));
        $this->assertTrue(self::$dao->updateBalance($account2->getAccountId(), 0, true));
        $this->assertEquals(0, self::$dao->getBalance($account2->getAccountId()));
        $this->assertTrue(self::$dao->recalculateBalance($account2->getAccountId()));
        $this->assertEquals(1, self::$dao->getBalance($account2->getAccountId()));

        $this->assertTrue(self::$dao->updateBalance($account2->getAccountId(), -10));
        $this->assertEquals(-9, self::$dao->getBalance($account2->getAccountId()));
        $this->assertTrue(self::$dao->recalculateBalance());
        $this->assertEquals(7, self::$dao->getBalance($account1->getAccountId()));
        $this->assertEquals(1, self::$dao->getBalance($account2->getAccountId()));
    }
}