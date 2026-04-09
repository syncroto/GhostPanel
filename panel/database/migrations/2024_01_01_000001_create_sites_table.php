<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->enum('type', ['php', 'nodejs', 'python', 'wordpress']);
            $table->string('php_version', 5)->default('8.2');
            $table->string('node_version', 5)->nullable();
            $table->string('root_path', 500);
            $table->enum('status', ['creating', 'running', 'stopped', 'deleting', 'error'])->default('creating');
            $table->boolean('ssl_enabled')->default(false);
            $table->string('nginx_config_path')->nullable();
            $table->string('supervisor_program')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
