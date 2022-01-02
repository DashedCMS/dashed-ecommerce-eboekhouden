<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Qubiqx\QcommerceEcommerceCore\Events\Orders\InvoiceCreatedEvent;
use Qubiqx\QcommerceEcommerceEboekhouden\Listeners\MarkOrderAsPushableListener;

class QcommerceEcommerceEboekhoudenEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceCreatedEvent::class => [
            MarkOrderAsPushableListener::class,
        ],
    ];
}
