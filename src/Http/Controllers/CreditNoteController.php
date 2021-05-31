<?php

namespace Rutatiina\CreditNote\Http\Controllers;

use Rutatiina\CreditNote\Models\Setting;
use URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Rutatiina\CreditNote\Models\CreditNote;
use Rutatiina\Contact\Traits\ContactTrait;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;
use Yajra\DataTables\Facades\DataTables;

use Rutatiina\CreditNote\Classes\Store as TxnStore;
use Rutatiina\CreditNote\Classes\Approve as TxnApprove;
use Rutatiina\CreditNote\Classes\Read as TxnRead;
use Rutatiina\CreditNote\Classes\Copy as TxnCopy;
use Rutatiina\CreditNote\Classes\Number as TxnNumber;
use Rutatiina\CreditNote\Traits\Item as TxnItem;
use Rutatiina\CreditNote\Classes\Edit as TxnEdit;
use Rutatiina\CreditNote\Classes\Update as TxnUpdate;


//controller not in use
class CreditNoteController extends Controller
{
    use FinancialAccountingTrait;
    use ContactTrait;
    use TxnItem; // >> get the item attributes template << !!important

    private  $txnEntreeSlug = 'credit-note';

    public function __construct()
    {
        $this->middleware('permission:credit-notes.view');
		$this->middleware('permission:credit-notes.create', ['only' => ['create','store']]);
		$this->middleware('permission:credit-notes.update', ['only' => ['edit','update']]);
		$this->middleware('permission:credit-notes.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $query = CreditNote::query();

        if ($request->contact)
        {
            $query->where(function($q) use ($request) {
                $q->where('debit_contact_id', $request->contact);
                $q->orWhere('credit_contact_id', $request->contact);
            });
        }

        $txns = $query->latest()->paginate($request->input('per_page', 20));

        return [
            'tableData' => $txns
        ];
    }

    private function nextNumber()
    {
        $txn = CreditNote::latest()->first();
        $settings = Setting::first();

        return $settings->number_prefix.(str_pad((optional($txn)->number+1), $settings->minimum_number_length, "0", STR_PAD_LEFT)).$settings->number_postfix;
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new CreditNote())->rgGetAttributes();

        $txnAttributes['number'] = $this->nextNumber();

        $txnAttributes['status'] = 'Approved';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['date'] = date('Y-m-d');
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['isRecurring'] = false;
        $txnAttributes['recurring'] = [
            'date_range' => [],
            'day_of_month' => '*',
            'month' => '*',
            'day_of_week' => '*',
        ];
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [$this->itemCreate()];

        unset($txnAttributes['txn_entree_id']); //!important
        unset($txnAttributes['txn_type_id']); //!important
        unset($txnAttributes['debit_contact_id']); //!important
        unset($txnAttributes['credit_contact_id']); //!important

        $data = [
            'pageTitle' => 'Create Credit Note', #required
            'pageAction' => 'Create', #required
            'txnUrlStore' => '/credit-notes', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson()) {
            return $data;
        }
    }

    public function store(Request $request)
	{
        $TxnStore = new TxnStore();
        $TxnStore->txnEntreeSlug = $this->txnEntreeSlug;
        $TxnStore->txnInsertData = $request->all();
        $insert = $TxnStore->run();

        if ($insert == false) {
            return [
                'status'    => false,
                'messages'   => $TxnStore->errors
            ];
        }

        return [
            'status'    => true,
            'messages'   => ['Credit Note saved'],
            'number'    => 0,
            'callback'  => URL::route('credit-notes.show', [$insert->id], false)
        ];

    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        if (FacadesRequest::wantsJson()) {
            $TxnRead = new TxnRead();
            return $TxnRead->run($id);
        }
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $TxnEdit = new TxnEdit();
        $txnAttributes = $TxnEdit->run($id);

        $data = [
            'pageTitle' => 'Edit Credit note', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/credit-notes/'.$id, #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson()) {
            return $data;
        }
    }

    public function update(Request $request)
	{
        //print_r($request->all()); exit;

        $TxnStore = new TxnUpdate();
        $TxnStore->txnInsertData = $request->all();
        $insert = $TxnStore->run();

        if ($insert == false) {
            return [
                'status'    => false,
                'messages'  => $TxnStore->errors
            ];
        }

        return [
            'status'    => true,
            'messages'  => ['Credit note updated'],
            'number'    => 0,
            'callback'  => URL::route('credit-notes.show', [$insert->id], false)
        ];
    }

    public function destroy($id)
	{
		$delete = Transaction::delete($id);

		if ($delete) {
			return [
				'status' => true,
				'message' => 'Credit Note deleted',
			];
		} else {
			return [
				'status' => false,
				'message' => implode('<br>', array_values(Transaction::$rg_errors))
			];
		}
	}

	#-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $TxnApprove = new TxnApprove();
        $approve = $TxnApprove->run($id);

        if ($approve == false) {
            return [
                'status'    => false,
                'messages'   => $TxnApprove->errors
            ];
        }

        return [
            'status'    => true,
            'messages'   => ['Credit Note Approved'],
        ];

    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $TxnCopy = new TxnCopy();
        $txnAttributes = $TxnCopy->run($id);

        $TxnNumber = new TxnNumber();
        $txnAttributes['number'] = $TxnNumber->run($this->txnEntreeSlug);


        $data = [
            'pageTitle' => 'Copy Credit Note', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/accounting/sales/credit-notes', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson()) {
            return $data;
        }
    }

    public function datatables(Request $request) {

        $txns = Transaction::setRoute('show', route('accounting.sales.credit-notes.show', '_id_'))
			->setRoute('edit', route('accounting.sales.credit-notes.edit', '_id_'))
			->setSortBy($request->sort_by)
			->paginate(false)
			->findByEntree($this->txnEntreeSlug);

        return Datatables::of($txns)->make(true);
    }

    public function exportToExcel(Request $request) {

        $txns = collect([]);

        $txns->push([
            'DATE',
            'NUMBER',
            'REFERENCE',
            'CUSTOMER',
            'TOTAL',
            ' ', //Currency
        ]);

        foreach (array_reverse($request->ids) as $id) {
            $txn = Transaction::transaction($id);

            $txns->push([
                $txn->date,
                $txn->number,
                $txn->reference,
                $txn->contact_name,
                $txn->total,
                $txn->base_currency,
            ]);
        }

        $export = $txns->downloadExcel(
            'maccounts-credit-notes-export-'.date('Y-m-d-H-m-s').'.xlsx',
            null,
            false
        );

        //$books->load('author', 'publisher'); //of no use

        return $export;
    }

}
