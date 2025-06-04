<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable();
            $table->json('data');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('collection_entries');
    }
};
