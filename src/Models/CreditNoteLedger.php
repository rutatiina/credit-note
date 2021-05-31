<?php

namespace Rutatiina\CreditNote\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Scopes\TenantIdScope;

class CreditNoteLedger extends Model
{
    use LogsActivity;

    protected static $logName = 'TxnLedger';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_credit_note_ledgers';

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

    public function credit_note()
    {
        return $this->belongsTo('Rutatiina\CreditNote\Models\CreditNote', 'credit_note_id');
    }

}
