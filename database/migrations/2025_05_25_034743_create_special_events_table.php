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
    Schema::create('special_events', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug')->unique();
        $table->date('event_date');
        $table->string('start_time')->nullable();
        $table->string('end_time')->nullable();
        $table->text('description')->nullable();
        $table->string('location')->nullable();
        $table->string('event_type')->nullable();
        $table->string('organizer_type')->nullable();
        $table->string('cover_image')->nullable();
        $table->json('gallery')->nullable();
        $table->string('meta_description')->nullable();
        $table->string('meta_keywords')->nullable();
        $table->boolean('is_featured')->default(false);
        $table->boolean('registration_required')->default(false);
        $table->string('registration_link')->nullable();
        $table->json('tags')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_events');
    }
};
