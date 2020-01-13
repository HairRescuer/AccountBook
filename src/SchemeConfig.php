<?php


namespace HairRescuer\AccountBook;


class SchemeConfig
{
    public $accountScheme = 'account';
    public $transactionScheme = 'transaction';

    public $accountIdAttribute = 'id';
    public $transactionAmountAttribute = 'amount';
    public $relatedAccountAttribute = 'account_id';
    public $oppositeAccountAttribute = 'opposite_account_id';
    public $dateAttribute = 'created_at';

    public $transactionIdAttribute = 'id';
    public $accountBalanceAttribute = 'balance';
}