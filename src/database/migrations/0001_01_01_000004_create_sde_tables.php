<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sde_types', function (Blueprint $table) {
            $table->integer('type_id')->primary();
            $table->integer('group_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('volume', 20, 4)->nullable();
            $table->integer('market_group_id')->nullable()->index();
            $table->boolean('published')->default(false);
            $table->integer('icon_id')->nullable();
        });

        Schema::create('sde_blueprints', function (Blueprint $table) {
            $table->integer('blueprint_type_id')->primary();
            $table->integer('max_production_limit')->default(0);
        });

        Schema::create('sde_blueprint_materials', function (Blueprint $table) {
            $table->id();
            $table->integer('blueprint_type_id')->index();
            $table->string('activity');
            $table->integer('material_type_id')->index();
            $table->integer('quantity');
        });

        Schema::create('sde_blueprint_products', function (Blueprint $table) {
            $table->id();
            $table->integer('blueprint_type_id')->index();
            $table->string('activity');
            $table->integer('product_type_id')->index();
            $table->integer('quantity')->default(1);
            $table->decimal('probability', 5, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sde_blueprint_products');
        Schema::dropIfExists('sde_blueprint_materials');
        Schema::dropIfExists('sde_blueprints');
        Schema::dropIfExists('sde_types');
    }
};
