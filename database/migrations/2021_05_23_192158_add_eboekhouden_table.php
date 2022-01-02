<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEboekhoudenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qcommerce__eboekhouden_order_connection', function (Blueprint $table) {
            $table->id();

            $table->string('relation_code');
            $table->string('relation_id');

            $table->timestamps();
        });

        Schema::table('qcommerce__orders', function (Blueprint $table) {
            $table->boolean('pushable_to_eboekhouden')->default(0);
            $table->boolean('pushed_to_eboekhouden')->default(0);
            $table->unsignedBigInteger('eboekhouden_order_connection_id')->nullable();
            $table->foreign('eboekhouden_order_connection_id')->references('id')->on('qcommerce__eboekhouden_order_connection');
        });

        if (\Qubiqx\QcommerceEcommerceEboekhouden\Classes\Eboekhouden::isConnected(\Qubiqx\QcommerceCore\Classes\Sites::getActive())) {
            foreach (\Qubiqx\QcommerceEcommerceCore\Models\Order::isPaid()->get() as $order) {
                $order->pushable_to_eboekhouden = 1;
                $order->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
