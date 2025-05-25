<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpecialFeaturesToTouristPlacesTable extends Migration
{
    public function up()
    {
        Schema::table('tourist_places', function (Blueprint $table) {
            $table->text('special_features')->nullable()->after('best_time_to_visit');
        });
    }

    public function down()
    {
        Schema::table('tourist_places', function (Blueprint $table) {
            $table->dropColumn('special_features');
        });
    }
}
