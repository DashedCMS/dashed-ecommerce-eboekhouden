<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden\Commands;

use Illuminate\Console\Command;
use Qubiqx\QcommerceEcommerceEboekhouden\Classes\Eboekhouden;
use Qubiqx\QcommerceEcommerceEboekhouden\Models\EboekhoudenOrder;

class PushOrdersToEboekhoudenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eboekhouden:push-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push orders to E-boekhouden';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (Eboekhouden::isConnected()) {
            foreach (EboekhoudenOrder::where('pushed', '!=', 1)->get() as $eboekhoudenOrder) {
                Eboekhouden::pushOrder($eboekhoudenOrder);
            }
        }
    }
}
