<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recria a tabela sites para:
 *  - trocar enum(type) por string (permite html5 + futuros tipos)
 *  - adicionar coluna port (para Node.js / Python)
 */
return new class extends Migration
{
    public function up(): void
    {
        // SQLite não suporta ALTER COLUMN — precisa recriar a tabela
        DB::statement('PRAGMA foreign_keys=OFF');
        DB::statement('ALTER TABLE sites RENAME TO _sites_old');

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->string('type', 20)->default('php');
            $table->string('php_version', 5)->default('8.2');
            $table->string('node_version', 5)->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('root_path', 500);
            $table->string('status', 20)->default('creating');
            $table->boolean('ssl_enabled')->default(false);
            $table->string('nginx_config_path')->nullable();
            $table->string('supervisor_program')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO sites
                (id, user_id, domain, type, php_version, node_version, port, root_path, status,
                 ssl_enabled, nginx_config_path, supervisor_program, notes, created_at, updated_at)
            SELECT
                id, user_id, domain, type, php_version, node_version, NULL, root_path, status,
                ssl_enabled, nginx_config_path, supervisor_program, notes, created_at, updated_at
            FROM _sites_old
        ');

        DB::statement('DROP TABLE _sites_old');
        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('port');
        });
    }
};
