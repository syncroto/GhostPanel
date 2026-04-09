<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 64)->unique();
            $table->string('username', 32);
            $table->enum('driver', ['mysql', 'postgresql'])->default('mysql');
            $table->string('host', 253)->default('localhost');
            $table->unsignedSmallInteger('port')->default(3306);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('databases');
    }
};
