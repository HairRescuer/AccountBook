<?php


namespace HairRescuer\AccountBook;


class AccountBook
{
    protected $dataAccess;

    public function __construct(DataAccessInterface $dataAccess)
    {
        $this->dataAccess = $dataAccess;
    }

    public function createAccount($extraData = []): Account
    {
        return $this->dataAccess->createAccount($extraData);
    }

    public function getAccountByConditions($conditions = []): Account
    {
        return $this->dataAccess->findAccountByConditions($conditions);
    }

    public function getAccountById($accountId): Account
    {
        return $this->dataAccess->findAccountById($accountId);
    }

    public function getTransaction($transactionId): Transaction
    {
        return $this->dataAccess->findTransaction($transactionId, null);
    }
}