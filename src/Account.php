<?php


namespace HairRescuer\AccountBook;


class Account
{
    protected $dataAccess;

    protected $accountId;

    public function __construct($accountId, DataAccessInterface $dataAccess)
    {
        $this->accountId = $accountId;
        $this->dataAccess = $dataAccess;
    }

    public function getAccountId()
    {
        return $this->accountId;
    }

    public function income(int $amount, $extraData = []): Transaction
    {
        $this->dataAccess->beginDBTransaction();
        try {
            $transaction = $this->dataAccess->createTransaction($this->accountId, $amount, null, $extraData);
            $this->dataAccess->updateBalance($this->accountId, $amount);
            $this->dataAccess->commitDBTransaction();
            return $transaction;
        } catch (\Exception $e) {
            $this->dataAccess->rollbackDBTransaction();
            throw $e;
        }
    }

    public function expense(int $amount, $extraData = [])
    {
        return $this->income(-($amount), $extraData);
    }

    public function transfer(Account $oppositeAccount, int $amount, $extraData = []): Transaction
    {
        $this->dataAccess->beginDBTransaction();
        try {
            $transaction = $this->dataAccess->createTransaction($this->accountId, $amount, $oppositeAccount->getAccountId(), $extraData);
            $this->dataAccess->updateBalance($this->accountId, $amount);
            $oppositeTransaction = $this->dataAccess->createTransaction($oppositeAccount->getAccountId(), $amount, $this->accountId, $extraData);
            $this->dataAccess->updateBalance($oppositeAccount->getAccountId(), -($amount));
            $this->dataAccess->commitDBTransaction();
            return $transaction;
        } catch (\Exception $e) {
            $this->dataAccess->rollbackDBTransaction();
            throw $e;
        }
    }

    public function getTransaction($transactionId): Transaction
    {
        return $this->dataAccess->findTransaction($transactionId, $this->accountId);
    }

    public function getBalance()
    {
        return $this->dataAccess->getBalance($this->accountId);
    }

    public function calculateBalance()
    {
        return $this->dataAccess->calculateBalance($this->accountId);
    }

}