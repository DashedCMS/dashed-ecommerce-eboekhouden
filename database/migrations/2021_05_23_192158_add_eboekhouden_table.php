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
        Schema::create('dashed__order_eboekhouden', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->nullable();
            $table->boolean('pushed')
                ->default(false);

            $table->string('relation_code')
                ->nullable();
            $table->string('relation_id')
                ->nullable();

            $table->timestamps();
        });
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
