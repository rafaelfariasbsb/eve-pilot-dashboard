<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('character_id')->unique();
            $table->string('name');
            $table->bigInteger('corporation_id')->nullable();
            $table->string('corporation_name')->nullable();
            $table->bigInteger('alliance_id')->nullable();
            $table->string('alliance_name')->nullable();
            $table->string('portrait_url')->nullable();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->boolean('is_main')->default(false);
            $table->decimal('wallet_balance', 20, 2)->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('corporation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
