<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('tourist_places', function (Blueprint $table) {
            $table->longText('main_attractions')->nullable()->after('map_link');
            $table->longText('purpose_and_significance')->nullable()->after('main_attractions');
        });
    }

    public function down()
    {
        Schema::table('tourist_places', function (Blueprint $table) {
            $table->dropColumn(['main_attractions', 'purpose_and_significance']);
        });
    }

};
