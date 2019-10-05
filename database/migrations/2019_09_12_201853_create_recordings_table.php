<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecordingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recordings', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name',100);
            $table->string('service',150)->unique();
            $table->timestamp('start');
            $table->timestamp('stop');
            $table->text('description');
            $table->integer('filesize');

            $table->integer('dreambox_id');
            $table->integer('channel_id')->nullable();

            $table->timestamps();

            $table->foreign('dreambox_id')
                  ->references('id')->on('dreamboxes')
                  ->onDelete('cascade');

            $table->foreign('channel_id')
                  ->references('id')->on('channels')
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
        Schema::dropIfExists('recordings');
    }
}
