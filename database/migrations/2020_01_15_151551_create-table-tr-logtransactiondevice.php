<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableTrLogtransactiondevice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('TR_LogTransactionDevice', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('RandomCode', 255)->nullable();
            $table->string('Status', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('TR_LogTransactionDevice');
    }
}
