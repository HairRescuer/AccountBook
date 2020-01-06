<?php


namespace HairRescuer\AccountBook;


use HairRescuer\AccountBook\Exceptions\AccountNotFoundException;

class AccountBook
{
    protected $dataAccess;

    public function __construct(DataAccessInterface $dataAccess)
    {
        $this->dataAccess = $dataAccess;
    }

    public function createAccount($extraData = []): Account
    {
        $accountId = $this->dataAccess->createAccount($extraData);
        return $this->getAccountById($accountId);
    }

    public function getAccountByCondition($conditions = []): Account
    {
        $accountId = $this->dataAccess->findAccount($conditions);
        return $this->getAccountById($accountId);
    }

    public function getAccountById($accountId): Account
    {
        $isAccountExisted = $this->dataAccess->isAccountExisted($accountId);
        if (!$isAccountExisted) {
            throw new AccountNotFoundException();
        }
        return new Account($accountId, $this->dataAccess);
    }
}