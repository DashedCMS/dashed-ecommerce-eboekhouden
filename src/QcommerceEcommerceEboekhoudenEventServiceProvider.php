<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden;

use Qubiqx\QcommerceEcommerceCore\Events\Orders\InvoiceCreatedEvent;
use Qubiqx\QcommerceEcommerceEboekhouden\Listeners\MarkOrderAsPushableListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class QcommerceEcommerceEboekhoudenEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceCreatedEvent::class => [
            MarkOrderAsPushableListener::class,
        ],
    ];
}
