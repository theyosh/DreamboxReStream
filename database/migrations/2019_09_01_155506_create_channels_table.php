<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name',100);
            $table->string('service',150)->unique();
            $table->string('picon',50)->nullable();

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
        Schema::dropIfExists('channels');
    }
}
