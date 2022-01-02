<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden\Commands;

use Illuminate\Console\Command;

class QcommerceEcommerceEboekhoudenCommand extends Command
{
    public $signature = 'qcommerce-ecommerce-eboekhouden';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
