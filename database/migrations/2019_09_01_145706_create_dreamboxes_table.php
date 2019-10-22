<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDreamboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dreamboxes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name',150);

            $table->string('hostname',100);
            $table->integer('port');

            $table->string('username',50)->nullable();
            $table->string('password',50)->nullable();

            $table->boolean('multiple_tuners')->default(0);

            $table->string('audio_language',10)->nullable();
            $table->string('subtitle_language',10)->nullable();
            $table->integer('epg_limit')->default(36);
            $table->integer('dvr_length')->default(120);
            $table->integer('buffer_time')->default(0);

            $table->string('transcoding_profiles',50)->nullable();
            $table->string('interface_language',5)->default('en');

            $table->text('exclude_bouquets')->nullable();

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
        Schema::dropIfExists('dreamboxes');
    }
}
