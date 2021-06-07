<?php

namespace Rutatiina\CreditNote\Http\Controllers;

use Rutatiina\CreditNote\Services\CreditNoteService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Rutatiina\CreditNote\Models\CreditNote;
use Rutatiina\Contact\Traits\ContactTrait;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;
use Yajra\DataTables\Facades\DataTables;

class CreditNoteController extends Controller
{
    use FinancialAccountingTrait;
    use ContactTrait;

    // >> get the item attributes template << !!important

    private $txnEntreeSlug = 'credit-note';

    public function __construct()
    {
        $this->middleware('permission:credit-notes.view');
        $this->middleware('permission:credit-notes.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:credit-notes.update', ['only' => ['edit', 'update']]);
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
            $query->where(function ($q) use ($request)
            {
                $q->where('debit_contact_id', $request->contact);
                $q->orWhere('credit_contact_id', $request->contact);
            });
        }

        $txns = $query->latest()->paginate($request->input('per_page', 20));

        return [
            'tableData' => $txns
        ];
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new CreditNote())->rgGetAttributes();

        $txnAttributes['number'] = CreditNoteService::nextNumber();
        $txnAttributes['status'] = 'approved';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['date'] = date('Y-m-d');
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [
            [
                'selectedTaxes' => [], #required
                'selectedItem' => json_decode('{}'), #required
                'displayTotal' => 0,
                'name' => '',
                'description' => '',
                'rate' => 0,
                'quantity' => 1,
                'total' => 0,
                'taxes' => [],

                'type' => '',
                'type_id' => '',
                'contact_id' => '',
                'tax_id' => '',
                'units' => '',
                'batch' => '',
                'expiry' => ''
            ]
        ];

        unset($txnAttributes['debit_contact_id']); //!important
        unset($txnAttributes['credit_contact_id']); //!important

        $data = [
            'pageTitle' => 'Create Credit Note', #required
            'pageAction' => 'Create', #required
            'txnUrlStore' => '/credit-notes', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson())
        {
            return $data;
        }
    }

    public function store(Request $request)
    {
        $storeService = CreditNoteService::store($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => CreditNoteService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Credit Note saved'],
            'number' => 0,
            'callback' => URL::route('credit-notes.show', [$storeService->id], false)
        ];

    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $txn = CreditNote::findOrFail($id);
        $txn->load('contact', 'items.taxes');
        $txn->setAppends([
            'taxes',
            'number_string',
            'total_in_words',
        ]);

        return $txn->toArray();
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }


        $txnAttributes = CreditNoteService::edit($id);

        $data = [
            'pageTitle' => 'Edit Credit note', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/credit-notes/' . $id, #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;
    }

    public function update(Request $request)
    {
        //print_r($request->all()); exit;

        $storeService = CreditNoteService::update($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => CreditNoteService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Credit note updated'],
            'number' => 0,
            'callback' => URL::route('credit-notes.show', [$storeService->id], false)
        ];
    }

    public function destroy($id)
    {
        $destroy = CreditNoteService::destroy($id);

        if ($destroy)
        {
            return [
                'status' => true,
                'messages' => ['Credit Note deleted'],
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => CreditNoteService::$errors
            ];
        }
    }

    #-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $approve = CreditNoteService::approve($id);

        if ($approve == false)
        {
            return [
                'status' => false,
                'messages' => CreditNoteService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Credit Note Approved'],
        ];

    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $txnAttributes = CreditNoteService::copy($id);

        $data = [
            'pageTitle' => 'Copy Credit Note', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/credit-notes', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson())
        {
            return $data;
        }
    }

    public function datatables(Request $request)
    {

        $txns = Transaction::setRoute('show', route('accounting.sales.credit-notes.show', '_id_'))
            ->setRoute('edit', route('accounting.sales.credit-notes.edit', '_id_'))
            ->setSortBy($request->sort_by)
            ->paginate(false)
            ->findByEntree($this->txnEntreeSlug);

        return Datatables::of($txns)->make(true);
    }

    public function exportToExcel(Request $request)
    {

        $txns = collect([]);

        $txns->push([
            'DATE',
            'NUMBER',
            'REFERENCE',
            'CUSTOMER',
            'TOTAL',
            ' ', //Currency
        ]);

        foreach (array_reverse($request->ids) as $id)
        {
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
            'maccounts-credit-notes-export-' . date('Y-m-d-H-m-s') . '.xlsx',
            null,
            false
        );

        //$books->load('author', 'publisher'); //of no use

        return $export;
    }

}
