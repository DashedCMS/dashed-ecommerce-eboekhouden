<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateEboekhoudenToV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('qcommerce__eboekhouden_order_connection', 'qcommerce__order_eboekhouden');

        Schema::table('qcommerce__order_eboekhouden', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('id');
            $table->boolean('pushed')->default(0)->after('order_id');
            $table->string('relation_code')->nullable()->change();
            $table->string('relation_id')->nullable()->change();
        });

        foreach (\Qubiqx\QcommerceEcommerceCore\Models\Order::where('pushable_to_eboekhouden', 1)->get() as $order) {
            $eboekhoudenConnection = \Qubiqx\QcommerceEcommerceEboekhouden\Models\EboekhoudenOrder::find($order->eboekhouden_order_connection_id);
            if ($eboekhoudenConnection) {
                $eboekhoudenConnection->order_id = $order->id;
                $eboekhoudenConnection->pushed = $order->pushed_to_eboekhouden;
                $eboekhoudenConnection->save();
            }
        }

        \Qubiqx\QcommerceEcommerceEboekhouden\Models\EboekhoudenOrder::whereNull('order_id')->delete();

        Schema::table('qcommerce__orders', function (Blueprint $table) {
            $table->dropColumn('pushable_to_eboekhouden')->default(0);
            $table->dropColumn('pushed_to_eboekhouden')->default(0);
            $table->dropConstrainedForeignId('eboekhouden_order_connection_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v2', function (Blueprint $table) {
            //
        });
    }
}
