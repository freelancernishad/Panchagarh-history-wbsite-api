<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- Import DB facade

class CreateAllowedOriginsTable extends Migration
{
    public function up()
    {
        Schema::create('allowed_origins', function (Blueprint $table) {
            $table->id();
            $table->string('origin_url')->unique();
            $table->timestamps();
        });

        // Insert default record
        DB::table('allowed_origins')->insert([
            'origin_url' => 'postman',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('allowed_origins');
    }
}
