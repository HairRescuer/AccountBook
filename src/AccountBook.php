<?php

namespace HairRescuer\AccountBook;

class AccountBook
{
    /**
     * @var DataAccessInterface
     */
    protected $dataAccess;

    public function __construct(DataAccessInterface $dataAccess)
    {
        $this->dataAccess = $dataAccess;
    }

    public function createAccount(array $extraData = []): Account
    {
        return $this->dataAccess->createAccount($extraData);
    }

    public function getAccountByConditions(array $conditions = []): Account
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

    public function recalculateBalance(Account $account = null): bool
    {
        return $this->dataAccess->recalculateBalance(empty($account) ? null : $account->getAccountId());
    }
}
