<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTableMsZone extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('MS_Zone', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('Id');
            $table->string('Code', 5);
            $table->string('Name', 255);
            $table->integer('StatusFlag')->nullable()->unsigned()->default(1);
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
        Schema::dropIfExists('MS_Zone');
    }
}
