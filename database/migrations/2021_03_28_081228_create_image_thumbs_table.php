<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImageThumbsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('image_thumbs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id');
            $table->foreign('image_id')
                ->references('id')->on('images')
                ->onDelete('cascade');
            $table->string('path');
            $table->mediumInteger('width');
            $table->mediumInteger('height');
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
        Schema::dropIfExists('images_thumbs');
    }
}
