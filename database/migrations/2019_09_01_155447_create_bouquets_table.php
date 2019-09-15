<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBouquetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bouquets', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name',50);
            $table->string('service',100)->unique();
            $table->integer('position')->index();
            $table->integer('dreambox_id');

            $table->timestamps();

            $table->foreign('dreambox_id')
                  ->references('id')->on('dreamboxes')
                  ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bouquets');
    }
}
