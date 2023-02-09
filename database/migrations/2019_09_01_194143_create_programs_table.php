<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('epg_id');
            $table->integer('channel_id');

            $table->string('name',100);
            $table->timestamp('start');
            $table->timestamp('stop');
            $table->text('description');

            $table->timestamps();

            $table->foreign('channel_id')
                  ->references('id')->on('channels')
                  ->onDelete('cascade');

            $table->unique(['epg_id', 'channel_id']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('programs');
    }
}
