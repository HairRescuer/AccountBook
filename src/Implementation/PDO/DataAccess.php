<?php

namespace HairRescuer\AccountBook\Implementation\PDO;

use HairRescuer\AccountBook\Account;
use HairRescuer\AccountBook\DataAccessInterface;
use HairRescuer\AccountBook\Exceptions\AccountNotFoundException;
use HairRescuer\AccountBook\Exceptions\TransactionNotFoundException;
use HairRescuer\AccountBook\SchemeConfig;
use HairRescuer\AccountBook\Transaction;

class DataAccess implements DataAccessInterface
{
    protected $schemeConfig;
    protected $pdoInstance;

    public function __construct(\PDO $pdoInstance, SchemeConfig $schemeConfig)
    {
        if (empty($pdoInstance)) {
            throw new \Exception('the PDO instance is null');
        }
        if (empty($schemeConfig)) {
            throw new \Exception('the scheme config is null');
        }
        $this->pdoInstance = $pdoInstance;
        $this->schemeConfig = $schemeConfig;
    }

    public function updateBalance($accountId, int $v, bool $overwrite = false): bool
    {
        $sc = $this->schemeConfig;
        if ($overwrite) {
            $sql = "UPDATE {$sc->accountScheme} SET `{$sc->accountBalanceAttribute}` = :v "
                . "WHERE {$sc->accountIdAttribute} = :accountId";
        } else {
            $sql = "UPDATE {$sc->accountScheme} SET "
                . "`{$sc->accountBalanceAttribute}` = `{$sc->accountBalanceAttribute}` + :v "
                . "WHERE {$sc->accountIdAttribute} = :accountId";
        }
        $ps = $this->pdoInstance->prepare($sql);
        $ps->bindValue(':v', $v, \PDO::PARAM_INT);
        $ps->bindValue(':accountId', $accountId);
        return $ps->execute();
    }

    public function calculateBalance($accountId): int
    {
        $sc = $this->schemeConfig;
        $sql = "SELECT SUM(`{$sc->transactionAmountAttribute}`) FROM `{$sc->transactionScheme}` "
            . "WHERE `{$sc->relatedAccountAttribute}` = '{$accountId}'";
        $ps = $this->pdoInstance->query($sql);
        $balance = intval($ps->fetchColumn());
        $this->updateBalance($accountId, $balance, true);
        return $balance;
    }

    public function getBalance($accountId): int
    {
        $sc = $this->schemeConfig;
        $sql = "SELECT * FROM {$sc->accountScheme} "
            . "WHERE {$sc->accountIdAttribute} = '{$accountId}' LIMIT 1";
        $ps = $this->pdoInstance->query($sql);
        $account = empty($ps) ? null : $ps->fetch();
        return (empty($account) || empty($account[$sc->accountBalanceAttribute])) ?
            0 : intval($account[$sc->accountBalanceAttribute]);
    }

    public function createAccount($extraData = []): Account
    {
        $sc = $this->schemeConfig;
        $data = [
            'balance' => 0
        ];
        $data = array_merge($data, $extraData);
        $sql = "INSERT INTO `{$sc->accountScheme}` ({$this->implodeColumnNames(array_keys($data))}) "
            . "VALUES ({$this->implodeColumnBindNames(array_keys($data))})";
        $ps = $this->pdoInstance->prepare($sql);
        foreach ($data as $k => $v) {
            $ps->bindValue(':' . $k, $v);
        }

        $ps->execute();
        return $this->findAccountById($this->pdoInstance->lastInsertId());
    }

    public function findAccountByConditions(array $conditions): Account
    {
        $sc = $this->schemeConfig;
        if (empty($conditions)) {
            throw new AccountNotFoundException();
        }
        $conditionArray = [];
        foreach ($conditions as $k => $v) {
            $conditionArray[] = " `$k` = '$v' ";
        }
        $conditionString = implode('AND', $conditionArray);
        $sql = "SELECT * FROM {$sc->accountScheme} WHERE {$conditionString} LIMIT 1";
        $ps = $this->pdoInstance->query($sql);
        $account = empty($ps) ? null : $ps->fetch();
        if (empty($account)) {
            throw new AccountNotFoundException();
        }
        return new Account($account[$sc->accountIdAttribute], $this);
    }

    public function findAccountById($accountId): Account
    {
        return $this->findAccountByConditions([$this->schemeConfig->accountIdAttribute => $accountId]);
    }

    public function createTransaction($accountId, $oppositeAccountId, int $amount, $extraData = []): Transaction
    {
        $sc = $this->schemeConfig;
        $data = [
            $sc->relatedAccountAttribute => $accountId,
            $sc->transactionAmountAttribute => $amount,
            $sc->dateAttribute => time()
        ];
        if (!empty($oppositeAccountId)) {
            $data[$sc->oppositeAccountAttribute] = $oppositeAccountId;
        }
        $data = array_merge($data, $extraData);
        $sql = "INSERT INTO `{$sc->transactionScheme}` ({$this->implodeColumnNames(array_keys($data))}) "
            . "VALUES ({$this->implodeColumnBindNames(array_keys($data))})";
        $ps = $this->pdoInstance->prepare($sql);
        foreach ($data as $k => $v) {
            $ps->bindValue(':' . $k, $v);
        }

        $ps->execute();
        return $this->findTransaction($this->pdoInstance->lastInsertId());
    }

    public function findTransaction($transactionId, $accountId = null): Transaction
    {
        $sc = $this->schemeConfig;
        if (empty($transactionId)) {
            throw new TransactionNotFoundException();
        }
        $conditionString = "`{$sc->transactionIdAttribute}` = :transactionId";
        if (!empty($accountId)) {
            $conditionString .= " AND `{$sc->relatedAccountAttribute}` = :accountId";
        }
        $sql = "SELECT * FROM `{$sc->transactionScheme}` WHERE {$conditionString} LIMIT 1";
        $ps = $this->pdoInstance->prepare($sql);
        $ps->bindValue(':transactionId', $transactionId);
        if (!empty($accountId)) {
            $ps->bindValue(':accountId', $accountId);
        }
        $ps->execute();
        $transaction = $ps->fetch();
        if (empty($transaction)) {
            throw new TransactionNotFoundException();
        }
        return new Transaction(
            $transaction[$sc->transactionIdAttribute],
            $transaction[$sc->relatedAccountAttribute],
            $transaction[$sc->oppositeAccountAttribute],
            $transaction[$sc->transactionAmountAttribute],
            $transaction[$sc->dateAttribute],
            $this
        );
    }

    public function isDBTransactionSupported(): bool
    {
        return true;
    }

    public function isInDBTransaction(): bool
    {
        return $this->pdoInstance->inTransaction();
    }

    public function beginDBTransaction()
    {
        $this->pdoInstance->beginTransaction();
    }

    public function commitDBTransaction()
    {
        $this->pdoInstance->commit();
    }

    public function rollbackDBTransaction()
    {
        $this->pdoInstance->rollBack();
    }

    private function implodeColumnNames($keys)
    {
        $columnNames = implode('`,`', $keys);
        if (!empty($columnNames)) {
            $columnNames = '`' . $columnNames . '`';
        }
        return $columnNames;
    }

    private function implodeColumnBindNames($keys)
    {
        $columnBindNames = implode(',:', $keys);
        if (!empty($columnBindNames)) {
            $columnBindNames = ':' . $columnBindNames;
        }
        return $columnBindNames;
    }
}
