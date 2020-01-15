<?php

namespace HairRescuer\AccountBook;

class AccountBook
{
    protected static $dataAccess;

    public static function setDataAccess(DataAccessInterface $dataAccess)
    {
        self::$dataAccess = $dataAccess;
    }

    public static function createAccount(array $extraData = []): Account
    {
        return self::$dataAccess->createAccount($extraData);
    }

    public static function getAccountByConditions(array $conditions = []): Account
    {
        return self::$dataAccess->findAccountByConditions($conditions);
    }

    public static function getAccountById($accountId): Account
    {
        return self::$dataAccess->findAccountById($accountId);
    }

    public static function getTransaction($transactionId): Transaction
    {
        return self::$dataAccess->findTransaction($transactionId, null);
    }
}
