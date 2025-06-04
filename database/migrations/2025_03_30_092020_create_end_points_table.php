<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('end_points', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();

            $table->foreignId('collection_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('type', ['create', 'read', 'update', 'delete', 'search']);
            $table->json('role');

            $table->boolean('own_only')->default(false);
            $table->boolean('trigger_event')->default(false);

            $table->json('fields')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('end_points');
    }
};
