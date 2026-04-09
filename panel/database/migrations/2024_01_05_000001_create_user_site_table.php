<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_site', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('site_id');
            $table->timestamps();
            $table->unique(['user_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_site');
    }
};
