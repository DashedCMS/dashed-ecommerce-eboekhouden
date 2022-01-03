<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden\Models;

use Illuminate\Database\Eloquent\Model;
use Qubiqx\QcommerceEcommerceCore\Models\Order;
use Spatie\Activitylog\Traits\LogsActivity;

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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
