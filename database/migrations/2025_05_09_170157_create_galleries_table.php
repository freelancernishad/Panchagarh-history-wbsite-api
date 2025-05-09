<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGalleriesTable extends Migration
{
    public function up()
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('url');
            $table->text('description')->nullable();
            $table->string('type')->nullable(); // Optional: e.g., 'tourist_place', 'user_profile'
            $table->string('uploaded_by')->nullable(); // Optional: user id or 'admin'
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('tourist_place_categories')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('galleries');
    }
}
