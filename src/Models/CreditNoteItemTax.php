<?php

namespace Rutatiina\CreditNote\Models;

use Illuminate\Database\Eloquent\Model;
use Rutatiina\Tenant\Scopes\TenantIdScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNoteItemTax extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static $logName = 'TxnItem';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_credit_note_item_taxes';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new TenantIdScope);
    }

    public function getTaxesAttribute($value)
    {
        $_array_ = json_decode($value);
        if (is_array($_array_)) {
            return $_array_;
        } else {
            return [];
        }
    }

    public function tax()
    {
        return $this->hasOne('Rutatiina\Tax\Models\Tax', 'code', 'tax_code');
    }

    public function credit_note()
    {
        return $this->belongsTo('Rutatiina\CreditNote\Models\CreditNote', 'credit_note_id', 'id');
    }

    public function credit_note_item()
    {
        return $this->belongsTo('Rutatiina\CreditNote\Models\CreditNoteItem', 'credit_note_item_id', 'id');
    }

}
