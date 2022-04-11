<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Qubiqx\QcommerceEcommerceCore\Models\Order;

class EboekhoudenOrder extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'qcommerce__order_eboekhouden';

    protected $fillable = [
        'order_id',
        'pushed',
        'relation_code',
        'relation_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
