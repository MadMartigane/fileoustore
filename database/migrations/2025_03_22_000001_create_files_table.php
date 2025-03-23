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
        Schema::create('files', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type');
            $table->integer('size');
            $table->string('owner_id');
            $table->json('shared_with')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();
            
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};