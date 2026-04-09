<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove a FK constraint de databases.site_id.
     * SQLite não suporta DROP FOREIGN KEY — precisa recriar a tabela.
     */
    public function up(): void
    {
        // Desativa FK enforcement temporariamente (necessário para recriar a tabela)
        DB::statement('PRAGMA foreign_keys = OFF');

        // Cria tabela nova sem FK constraint
        Schema::create('databases_new', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('name', 64)->unique();
            $table->string('username', 32);
            $table->enum('driver', ['mysql', 'postgresql'])->default('mysql');
            $table->string('host', 253)->default('localhost');
            $table->unsignedSmallInteger('port')->default(3306);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Copia dados existentes
        DB::statement('INSERT INTO databases_new SELECT * FROM databases');

        // Remove tabela antiga e renomeia a nova
        Schema::drop('databases');
        DB::statement('ALTER TABLE databases_new RENAME TO databases');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('databases_old', function (Blueprint $table) {
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

        DB::statement('INSERT INTO databases_old SELECT * FROM databases');
        Schema::drop('databases');
        DB::statement('ALTER TABLE databases_old RENAME TO databases');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
