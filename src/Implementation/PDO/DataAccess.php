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
        if ($overwrite) {
            $sql = "UPDATE {$this->schemeConfig->accountScheme} SET `{$this->schemeConfig->accountBalanceAttribute}` = :v WHERE {$this->schemeConfig->accountIdAttribute} = :accountId";
        } else {
            $sql = "UPDATE {$this->schemeConfig->accountScheme} SET `{$this->schemeConfig->accountBalanceAttribute}` = `{$this->schemeConfig->accountBalanceAttribute}` + :v WHERE {$this->schemeConfig->accountIdAttribute} = :accountId";
        }
        $ps = $this->pdoInstance->prepare($sql);
        $ps->bindValue(':v', $v, \PDO::PARAM_INT);
        $ps->bindValue(':accountId', $accountId);
        return $ps->execute();
    }

    public function calculateBalance($accountId): int
    {
        $sql = "SELECT SUM(`{$this->schemeConfig->transactionAmountAttribute}`) FROM `{$this->schemeConfig->transactionScheme}` WHERE `{$this->schemeConfig->relatedAccountAttribute}` = '{$accountId}'";
        $ps = $this->pdoInstance->query($sql);
        $balance = intval($ps->fetchColumn());
        $this->updateBalance($accountId, $balance, true);
        return $balance;
    }

    public function getBalance($accountId): int
    {
        $sql = "SELECT * FROM {$this->schemeConfig->accountScheme} WHERE {$this->schemeConfig->accountIdAttribute} = '{$accountId}' LIMIT 1";
        $ps = $this->pdoInstance->query($sql);
        $account = empty($ps) ? null : $ps->fetch();
        return (empty($account) || empty($account[$this->schemeConfig->accountBalanceAttribute])) ? 0 : intval($account[$this->schemeConfig->accountBalanceAttribute]);
    }

    public function createAccount($extraData = []): Account
    {
        $data = [
            'balance' => 0
        ];
        $data = array_merge($data, $extraData);
        $sql = "INSERT INTO `{$this->schemeConfig->accountScheme}` ({$this->implodeColumnNames(array_keys($data))}) VALUES ({$this->implodeColumnBindNames(array_keys($data))})";
        $ps = $this->pdoInstance->prepare($sql);
        foreach ($data as $k => $v) {
            $ps->bindValue(':' . $k, $v);
        }

        $ps->execute();
        return $this->findAccountById($this->pdoInstance->lastInsertId());
    }

    public function findAccountByConditions(array $conditions): Account
    {
        if (empty($conditions)) {
            throw new AccountNotFoundException();
        }
        $conditionArray = [];
        foreach ($conditions as $k => $v) {
            $conditionArray[] = " `$k` = '$v' ";
        }
        $conditionString = implode('AND', $conditionArray);
        $sql = "SELECT * FROM {$this->schemeConfig->accountScheme} WHERE {$conditionString} LIMIT 1";
        $ps = $this->pdoInstance->query($sql);
        $account = empty($ps) ? null : $ps->fetch();
        if (empty($account)) {
            throw new AccountNotFoundException();
        }
        return new Account($account[$this->schemeConfig->accountIdAttribute], $this);
    }

    public function findAccountById($accountId): Account
    {
        return $this->findAccountByConditions([$this->schemeConfig->accountIdAttribute => $accountId]);
    }

    public function createTransaction($accountId, $oppositeAccountId, int $amount, $extraData = []): Transaction
    {
        $data = [
            $this->schemeConfig->relatedAccountAttribute => $accountId,
            $this->schemeConfig->transactionAmountAttribute => $amount,
            $this->schemeConfig->dateAttribute => time()
        ];
        if (!empty($oppositeAccountId)) {
            $data[$this->schemeConfig->oppositeAccountAttribute] = $oppositeAccountId;
        }
        $data = array_merge($data, $extraData);
        $sql = "INSERT INTO `{$this->schemeConfig->transactionScheme}` ({$this->implodeColumnNames(array_keys($data))}) VALUES ({$this->implodeColumnBindNames(array_keys($data))})";
        $ps = $this->pdoInstance->prepare($sql);
        foreach ($data as $k => $v) {
            $ps->bindValue(':' . $k, $v);
        }

        $ps->execute();
        return $this->findTransaction($this->pdoInstance->lastInsertId());
    }

    public function findTransaction($transactionId, $accountId = null): Transaction
    {
        if (empty($transactionId)) {
            throw new TransactionNotFoundException();
        }
        $conditionString = "`{$this->schemeConfig->transactionIdAttribute}` = :transactionId";
        if (!empty($accountId)) {
            $conditionString .= " AND `{$this->schemeConfig->relatedAccountAttribute}` = :accountId";
        }
        $sql = "SELECT * FROM `{$this->schemeConfig->transactionScheme}` WHERE {$conditionString} LIMIT 1";
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
            $transaction[$this->schemeConfig->transactionIdAttribute],
            $transaction[$this->schemeConfig->relatedAccountAttribute],
            $transaction[$this->schemeConfig->oppositeAccountAttribute],
            $transaction[$this->schemeConfig->transactionAmountAttribute],
            $transaction[$this->schemeConfig->dateAttribute],
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