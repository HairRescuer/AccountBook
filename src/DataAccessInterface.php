<?php


namespace HairRescuer\AccountBook;


interface DataAccessInterface
{
    public function updateBalance($accountId);

    public function calculateBalance($accountId);

    public function getBalance($accountId);

    public function createTransaction($accountId, $amount, $extraData = []);

    public function createAccount($extraData = []);

    public function findAccount($extraData = []);

    public function isAccountExisted($accountId);

    public function isDBTransactionSupported(): bool;

    public function isInDBTransaction(): bool;

    public function beginDBTransaction();

    public function commitDBTransaction();

    public function rollbackDBTransaction();
}