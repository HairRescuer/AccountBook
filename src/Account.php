<?php


namespace HairRescuer\AccountBook;


class Account
{
    protected $dataAccess;

    protected $accountId;

    public function __construct($accountId, DataAccessInterface $dataAccess)
    {
        $this->dataAccess = $dataAccess;
    }

    public function getAccountId()
    {
        return $this->accountId;
    }

    public function income(float $amount, $extraData = [])
    {
        $this->dataAccess->createTransaction($this->accountId, $amount, $extraData);
    }

    public function expense(float $amount, $extraData = [])
    {
        $this->dataAccess->createTransaction($this->accountId, -($amount), $extraData);
    }

    public function transfer(Account $oppositeAccount, float $amount, $extraData = [])
    {
        $this->expense($amount, $extraData);
        $oppositeAccount->income($amount, $extraData);
    }

    public function revert($transactionId, $extraData = [])
    {

    }

    public function getBalance()
    {
        $this->dataAccess->getBalance($this->accountId);
    }

    public function calculateBalance()
    {
        $this->dataAccess->calculateBalance($this->accountId);
    }
}