<?php

namespace Rutatiina\CreditNote\Services;

use Rutatiina\CreditNote\Models\CreditNoteLedger;

class CreditNoteLedgerService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function store($txn)
    {
        foreach ($data['ledgers'] as &$ledger)
        {
            $ledger['credit_note_id'] = $data['id'];
            CreditNoteLedger::create($ledger);
        }

        $post->comments()->save($comment);

        unset($ledger);

    }

}
