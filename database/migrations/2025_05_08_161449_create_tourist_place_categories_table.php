<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tourist_place_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ক্যাটাগরির নাম
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tourist_place_categories');
    }
};
