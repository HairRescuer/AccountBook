<?php


namespace HairRescuer\AccountBook;


class SchemeConfig
{
    public $accountScheme = 'account';
    public $transactionScheme = 'transaction';

    public $amountAttribute = 'amount';
    public $relatedAccountAttribute = 'account_id';
    public $oppositeTransactionAttribute = 'opposite_transaction_id';
    public $dateAttribute = 'created_at';

    public $accountBalanceAttribute = 'balance';
}