<?php

namespace HairRescuer\AccountBook;

interface DataAccessInterface
{
    public function updateBalance($accountId, int $v, bool $overwrite = false): bool;

    public function calculateBalance($accountId): int;

    public function getBalance($accountId): int;

    public function createAccount(array $extraData = []): Account;

    public function findAccountByConditions(array $conditions): Account;

    public function findAccountById($accountId): Account;

    public function createTransaction($accountId, $oppositeAccountId, int $amount, array $extraData = []): Transaction;

    public function findTransaction($transactionId, $accountId = null): Transaction;

    public function isDBTransactionSupported(): bool;

    public function isInDBTransaction(): bool;

    public function beginDBTransaction();

    public function commitDBTransaction();

    public function rollbackDBTransaction();
}
