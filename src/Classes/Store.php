<?php

namespace Rutatiina\CreditNote\Classes;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Rutatiina\CreditNote\Models\CreditNoteItemTax;
use Rutatiina\CreditNote\Models\CreditNoteLedger;
use Rutatiina\CreditNote\Models\CreditNote;
use Rutatiina\CreditNote\Models\CreditNoteItem;
use Rutatiina\CreditNote\Traits\Init as TxnTraitsInit;
use Rutatiina\CreditNote\Traits\TxnItemsContactsIdsLedgers as TxnTraitsTxnItemsContactsIdsLedgers;
use Rutatiina\CreditNote\Traits\TxnItemsJournalLedgers as TxnTraitsTxnItemsJournalLedgers;
use Rutatiina\CreditNote\Traits\TxnTypeBasedSpecifics as TxnTraitsTxnTypeBasedSpecifics;
use Rutatiina\CreditNote\Traits\Validate as TxnTraitsValidate;
use Rutatiina\CreditNote\Traits\AccountBalanceUpdate as TxnTraitsAccountBalanceUpdate;
use Rutatiina\CreditNote\Traits\ContactBalanceUpdate as TxnTraitsContactBalanceUpdate;
use Rutatiina\CreditNote\Traits\Approve as TxnTraitsApprove;

class Store
{
    use TxnTraitsInit;
    use TxnTraitsTxnItemsContactsIdsLedgers;
    use TxnTraitsTxnItemsJournalLedgers;
    use TxnTraitsTxnTypeBasedSpecifics;
    use TxnTraitsValidate;
    use TxnTraitsAccountBalanceUpdate;
    use TxnTraitsContactBalanceUpdate;
    use TxnTraitsApprove;

    public function __construct()
    {}

    public function run()
    {
        //print_r($this->txnInsertData); exit;

        $verifyWebData = $this->validate();
        if ($verifyWebData === false) return false;

        //check if inventory is affected and if its available
        //for the mean time inventory functions are disabled
        //$inventoryAvailability = $this->inventoryAvailability();
        //if ($inventoryAvailability === false) return false;

		//Log::info($this->txn);
        //var_dump($this->txn); exit;
        //print_r($this->txn); exit;
        //echo json_encode($this->txn); exit;

        //print_r($this->txn); exit; //$this->txn, $this->txn['items'], $this->txn[number], $this->txn[ledgers], $this->txn[recurring]

        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try {

            //print_r($this->txn); exit;
            $Txn = new CreditNote;
            $Txn->tenant_id = $this->txn['tenant_id'];
            $Txn->document_name = $this->txn['document_name'];
            $Txn->number_prefix = $this->txn['number_prefix'];
            $Txn->number = $this->txn['number'];
            $Txn->number_length = $this->txn['number_length'];
            $Txn->number_postfix = $this->txn['number_postfix'];
            $Txn->date = $this->txn['date'];
            $Txn->debit_financial_account_code = $this->txn['debit_financial_account_code'];
            $Txn->credit_financial_account_code = $this->txn['credit_financial_account_code'];
            $Txn->debit_contact_id = $this->txn['debit_contact_id'];
            $Txn->credit_contact_id = $this->txn['credit_contact_id'];
            $Txn->contact_name = $this->txn['contact_name'];
            $Txn->contact_address = $this->txn['contact_address'];
            $Txn->reference = $this->txn['reference'];
            $Txn->base_currency = $this->txn['base_currency'];
            $Txn->quote_currency = $this->txn['quote_currency'];
            $Txn->exchange_rate = $this->txn['exchange_rate'];
            $Txn->taxable_amount = $this->txn['taxable_amount'];
            $Txn->total = $this->txn['total'];
            $Txn->balance = $this->txn['balance'];
            $Txn->branch_id = $this->txn['branch_id'];
            $Txn->store_id = $this->txn['store_id'];
            $Txn->due_date = $this->txn['due_date'];
            $Txn->expiry_date = $this->txn['expiry_date'];
            $Txn->terms_and_conditions = $this->txn['terms_and_conditions'];
            $Txn->external_ref = $this->txn['external_ref'];
            $Txn->payment_mode = $this->txn['payment_mode'];
            $Txn->payment_terms = $this->txn['payment_terms'];
            $Txn->status = $this->txn['status'];

            $Txn->save();
            $this->txn['id'] = $Txn->id;

            foreach($this->txn['items'] as &$item)
            {
                $item['credit_note_id'] = $this->txn['id'];

                $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
                unset($item['taxes']);

                $itemModel = CreditNoteItem::create($item);

                foreach ($itemTaxes as $tax)
                {
                    //save the taxes attached to the item
                    $itemTax = new CreditNoteItemTax;
                    $itemTax->tenant_id = $item['tenant_id'];
                    $itemTax->credit_note_id = $item['credit_note_id'];
                    $itemTax->credit_note_item_id = $itemModel->id;
                    $itemTax->tax_code = $tax['code'];
                    $itemTax->amount = $tax['total'];
                    $itemTax->inclusive = $tax['inclusive'];
                    $itemTax->exclusive = $tax['exclusive'];
                    $itemTax->save();
                }
                unset($tax);
            }

            unset($item);


            //print_r($this->txn['items']); exit;

            foreach($this->txn['ledgers'] as &$ledger)
            {
                $ledger['credit_note_id'] = $this->txn['id'];
                CreditNoteLedger::create($ledger);
            }
            unset($ledger);

            $this->approve();

            DB::connection('tenant')->commit();

            return (object) [
                'id' => $this->txn['id'],
            ];

        } catch (\Exception $e) {

            DB::connection('tenant')->rollBack();
            //print_r($e); exit;
            if (App::environment('local')) {
                $this->errors[] = 'Error: Failed to save transaction to database.';
                $this->errors[] = 'File: '. $e->getFile();
                $this->errors[] = 'Line: '. $e->getLine();
                $this->errors[] = 'Message: ' . $e->getMessage();
            } else {
                $this->errors[] = 'Fatal Internal Error: Failed to save transaction to database. Please contact Admin';
			}

            return false;
        }

    }

}
