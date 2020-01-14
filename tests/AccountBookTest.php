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
        AccountBook::setDataAccess($dao);
        $userId = random_int(1000, 9999);

        $account1 = AccountBook::createAccount();
        $account2 = AccountBook::createAccount(['user_id' => $userId, 'account_name' => 'test' . strval($userId)]);
        $this->assertNotNull($account1);
        $this->assertNotNull($account2);

        $accountCopy1 = AccountBook::getAccountById($account1->getAccountId());
        $accountCopy2 = AccountBook::getAccountByConditions(['user_id' => $userId, 'account_name' => 'test' . strval($userId)]);
        $this->assertEquals($account1, $accountCopy1);
        $this->assertEquals($account2, $accountCopy2);

        $transaction1 = $account1->income(23);
        $this->assertNotEmpty($transaction1);
        $this->assertEquals(23, $account1->getBalance());
        $transaction2 = $account1->transfer($account2, 14, ['attach' => 'test transfer']);
        $this->assertNotEmpty($transaction2);
        $this->assertEquals(9, $account1->getBalance());
        $this->assertEquals(14, $account2->getBalance());
        $transaction3 = $account2->expense(6, ['attach' => 'test expense']);
        $this->assertNotEmpty($transaction2);
        $this->assertEquals(8, $account2->getBalance());


        $transactionCopy3 = AccountBook::getTransaction($transaction3->getTransactionId());
        $this->assertEquals($transaction3, $transactionCopy3);

        $transaction4 = $transaction2->revert(['attach' => 'revert transfer']);
        $this->assertNotNull($transaction4);
        $this->assertEquals(23, $account1->getBalance());
        $this->assertEquals(-6, $account2->getBalance());

        $transactionCopy4 = $account1->getTransaction($transaction4->getTransactionId());
        $this->assertNotNull($transactionCopy4);
        $this->assertEquals($transaction2->getOppositeAccountId(), $transaction4->getOppositeAccountId());
    }
}