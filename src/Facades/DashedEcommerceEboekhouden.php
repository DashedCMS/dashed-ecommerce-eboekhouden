<?php

namespace Dashed\DashedEcommerceEboekhouden\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dashed\DashedEcommerceEboekhouden\DashedEcommerceEboekhouden
 */
class DashedEcommerceEboekhouden extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dashed-ecommerce-eboekhouden';
    }
}
