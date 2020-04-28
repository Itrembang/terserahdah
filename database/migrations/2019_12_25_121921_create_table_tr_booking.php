<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTableTrBooking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('TR_Booking', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('Id');
            $table->unsignedBigInteger('UserId');
            $table->foreign('UserId')->references('Id')->on('MS_User');
            $table->unsignedBigInteger('ZoneDetailId');
            $table->foreign('ZoneDetailId')->references('Id')->on('MS_ZoneDetail');
            $table->unsignedInteger('Status');
            $table->string('InputUN', 255)->nullable();
            $table->timestamp('InputTime')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('ModifUN', 255)->nullable();
            $table->timestamp('ModifTime')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('TR_Booking');
    }
}
