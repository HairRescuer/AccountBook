<?php


namespace HairRescuer\AccountBook;


class Transaction
{
    protected $dataAccess;

    protected $transactionId;
    protected $accountId;
    protected $oppositeAccountId;
    protected $amount;
    protected $date;

    public function __construct($transactionId, $accountId, $oppositeAccountId, int $amount, int $date, DataAccessInterface $dataAccess)
    {
        $this->dataAccess = $dataAccess;

        $this->transactionId = $transactionId;
        $this->accountId = $accountId;
        $this->oppositeAccountId = $oppositeAccountId;
        $this->amount = $amount;
        $this->date = $date;
    }

    public function revert($extraData = [])
    {
        $this->dataAccess->beginDBTransaction();
        try {
            $transaction = $this->dataAccess->createTransaction($this->accountId, empty($this->oppositeAccountId) ? null : $this->oppositeAccountId, -($this->amount), $extraData);
            $this->dataAccess->updateBalance($this->accountId, -($this->amount));
            if (!empty($this->oppositeAccountId)) {
                $oppositeTransaction = $this->dataAccess->createTransaction($this->oppositeAccountId, $this->accountId, $this->amount, $extraData);
                $this->dataAccess->updateBalance($this->oppositeAccountId, $this->amount);
            }
            $this->dataAccess->commitDBTransaction();
            return $transaction;
        } catch (\Exception $e) {
            $this->dataAccess->rollbackDBTransaction();
            throw $e;
        }
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function getAccountId()
    {
        return $this->accountId;
    }

    public function getOppositeAccountId()
    {
        return $this->oppositeAccountId;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getDate($format = null)
    {
        if (empty($format)) {
            return $this->date;
        }
        return date($format, $this->date);
    }
}