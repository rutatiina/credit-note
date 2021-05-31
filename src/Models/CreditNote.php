<?php

namespace Rutatiina\CreditNote\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Scopes\TenantIdScope;

class CreditNote extends Model
{
    use LogsActivity;

    protected static $logName = 'Txn';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_credit_notes';

    protected $primaryKey = 'id';

    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    protected $appends = [
        'number_string',
        'total_in_words',
        'contact_id',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new TenantIdScope);

        self::deleting(function($txn) { // before delete() method call this
             $txn->items()->each(function($row) {
                $row->delete();
             });
             $txn->comments()->each(function($row) {
                $row->delete();
             });
             $txn->ledgers()->each(function($row) {
                $row->delete();
             });
        });

    }

    public function rgGetAttributes()
    {
        $attributes = [];
        $describeTable =  \DB::connection('tenant')->select('describe ' . $this->getTable());

        foreach ($describeTable  as $row) {

            if (in_array($row->Field, ['id', 'created_at', 'updated_at', 'deleted_at', 'tenant_id', 'user_id'])) continue;

            if (in_array($row->Field, ['currencies', 'taxes'])) {
                $attributes[$row->Field] = [];
                continue;
            }

            if ($row->Default == '[]') {
                $attributes[$row->Field] = [];
            } else {
                $attributes[$row->Field] = ''; //$row->Default; //null affects laravel validation
            }
        }

        //add the relationships
        $attributes['type'] = [];
        $attributes['debit_account'] = [];
        $attributes['credit_account'] = [];
        $attributes['items'] = [];
        $attributes['ledgers'] = [];
        $attributes['comments'] = [];
        $attributes['debit_contact'] = [];
        $attributes['credit_contact'] = [];
        $attributes['recurring'] = [];

        return $attributes;
    }

    public function getContactAddressArrayAttribute()
    {
        return preg_split("/\r\n|\n|\r/", $this->contact_address);
    }

    public function getNumberStringAttribute()
    {
        return $this->number_prefix.(str_pad(($this->number), $this->number_length, "0", STR_PAD_LEFT)).$this->number_postfix;
    }

    public function getTotalInWordsAttribute()
    {
        $f = new \NumberFormatter( locale_get_default(), \NumberFormatter::SPELLOUT );
        return ucfirst($f->format($this->total));
    }

    public function getContactIdAttribute()
    {
        if ($this->debit_contact_id == $this->credit_contact_id)
        {
            return $this->debit_contact_id;
        }
        else
        {
            return null;
        }
    }

    public function debit_account()
    {
        return $this->hasOne('Rutatiina\FinancialAccounting\Models\Account', 'id', 'debit');
    }

    public function credit_account()
    {
        return $this->hasOne('Rutatiina\FinancialAccounting\Models\Account', 'id', 'credit');
    }

    public function items()
    {
        return $this->hasMany('Rutatiina\CreditNote\Models\CreditNoteItem', 'credit_note_id')->orderBy('id', 'asc');
    }

    public function ledgers()
    {
        return $this->hasMany('Rutatiina\CreditNote\Models\CreditNoteLedger', 'credit_note_id')->orderBy('id', 'asc');
    }

    public function comments()
    {
        return $this->hasMany('Rutatiina\CreditNote\Models\CreditNoteComment', 'credit_note_id')->latest();
    }

    public function contact()
    {
        return $this->hasOne('Rutatiina\Contact\Models\Contact', 'id', 'debit_contact_id');
    }

    public function debit_contact()
    {
        return $this->hasOne('Rutatiina\Contact\Models\Contact', 'id', 'debit_contact_id');
    }

    public function credit_contact()
    {
        return $this->hasOne('Rutatiina\Contact\Models\Contact', 'id', 'credit_contact_id');
    }

    public function item_taxes()
    {
        return $this->hasMany('Rutatiina\CreditNote\Models\CreditNoteItemTax', 'credit_note_id', 'id');
    }

    public function getTaxesAttribute()
    {
        $grouped = [];
        $this->item_taxes->load('tax'); //the values of the tax are used by the display of the document on the from end

        foreach($this->item_taxes as $item_tax)
        {
            if (isset($grouped[$item_tax->tax_code]))
            {
                $grouped['amount'] += $item_tax['amount'];
                $grouped['inclusive'] += $item_tax['inclusive'];
                $grouped['exclusive'] += $item_tax['exclusive'];
            }
            else
            {
                $grouped[$item_tax->tax_code] = $item_tax;
            }
        }
        return $grouped;
    }

}
