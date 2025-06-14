<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTouristPlacesTable extends Migration
{
    public function up()
    {
        Schema::create('tourist_places', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name');
            $table->string('location');
            $table->longText('description')->nullable();
            $table->longText('short_description')->nullable();
            $table->longText('history')->nullable();
            $table->longText('architecture')->nullable();
            $table->longText('how_to_go')->nullable();
            $table->longText('where_to_stay')->nullable();
            $table->longText('where_to_eat')->nullable();
            $table->string('ticket_price')->nullable();
            $table->string('opening_hours')->nullable();
            $table->longText('best_time_to_visit')->nullable();
            $table->string('image_url')->nullable();
            $table->json('gallery')->nullable();
            $table->longText('map_link')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('tourist_place_categories')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tourist_places');
    }
}
