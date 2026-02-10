<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_blueprints', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('character_id')->index();
            $table->bigInteger('item_id');
            $table->integer('type_id')->index();
            $table->bigInteger('location_id')->nullable();
            $table->integer('material_efficiency')->default(0);
            $table->integer('time_efficiency')->default(0);
            $table->integer('runs')->default(-1);
            $table->integer('quantity')->default(-1);
            $table->timestamps();

            $table->unique(['character_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_blueprints');
    }
};
