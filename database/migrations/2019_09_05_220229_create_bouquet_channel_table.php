<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBouquetChannelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bouquet_channel', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('bouquet_id');
            $table->integer('channel_id');
            $table->integer('position')->index();

            $table->foreign('bouquet_id')
                  ->references('id')->on('bouquets')
                  ->onDelete('cascade');

            $table->foreign('channel_id')
                  ->references('id')->on('channels')
                  ->onDelete('cascade');

            $table->unique(['bouquet_id', 'channel_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bouquet_channel');
    }
}
