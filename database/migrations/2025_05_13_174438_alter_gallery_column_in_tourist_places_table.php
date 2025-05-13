<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tourist_places', function (Blueprint $table) {
            $table->json('gallery')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('tourist_places', function (Blueprint $table) {
            $table->text('gallery')->nullable()->change();
        });
    }

};
