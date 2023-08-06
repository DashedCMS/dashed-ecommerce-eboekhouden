<?php

namespace Dashed\DashedEcommerceEboekhouden;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Dashed\DashedEcommerceCore\Events\Orders\InvoiceCreatedEvent;
use Dashed\DashedEcommerceEboekhouden\Listeners\MarkOrderAsPushableListener;

class DashedEcommerceEboekhoudenEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceCreatedEvent::class => [
            MarkOrderAsPushableListener::class,
        ],
    ];
}
