<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONN = 'mysql_area5s';

    public function up(): void
    {
        if (! $this->hasIndex('a5s_floors', 'a5s_floors_zone_id_index')) {
            Schema::connection(self::CONN)->table('a5s_floors', function (Blueprint $table) {
                $table->index('zone_id', 'a5s_floors_zone_id_index');
            });
        }

        if ($this->hasIndex('a5s_floors', 'a5s_floors_zone_id_name_unique')) {
            Schema::connection(self::CONN)->table('a5s_floors', function (Blueprint $table) {
                $table->dropUnique('a5s_floors_zone_id_name_unique');
            });
        }

        if (! Schema::connection(self::CONN)->hasColumn('a5s_floors', 'zone_map_area_id')) {
            Schema::connection(self::CONN)->table('a5s_floors', function (Blueprint $table) {
                $table->unsignedBigInteger('zone_map_area_id')->nullable()->after('zone_id');
                $table->index('zone_map_area_id', 'a5s_floors_area_id_index');
                $table->foreign('zone_map_area_id', 'a5s_floors_area_id_foreign')
                    ->references('id')
                    ->on('a5s_zone_map_areas')
                    ->nullOnDelete();
            });
        }

        if (! $this->hasIndex('a5s_floors', 'a5s_floors_area_name_unique')) {
            Schema::connection(self::CONN)->table('a5s_floors', function (Blueprint $table) {
                $table->unique(['zone_map_area_id', 'name'], 'a5s_floors_area_name_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('a5s_floors', 'a5s_floors_area_name_unique')) {
            Schema::connection(self::CONN)->table('a5s_floors', function (Blueprint $table) {
                $table->dropUnique('a5s_floors_area_name_unique');
            });
        }

        if (Schema::connection(self::CONN)->hasColumn('a5s_floors', 'zone_map_area_id')) {
            Schema::connection(self::CONN)->table('a5s_floors', function (Blueprint $table) {
                if ($this->hasForeign('a5s_floors', 'a5s_floors_area_id_foreign')) {
                    $table->dropForeign('a5s_floors_area_id_foreign');
                }
                if ($this->hasIndex('a5s_floors', 'a5s_floors_area_id_index')) {
                    $table->dropIndex('a5s_floors_area_id_index');
                }
                $table->dropColumn('zone_map_area_id');
            });
        }

        if (! $this->hasIndex('a5s_floors', 'a5s_floors_zone_id_name_unique')) {
            Schema::connection(self::CONN)->table('a5s_floors', function (Blueprint $table) {
                $table->unique(['zone_id', 'name'], 'a5s_floors_zone_id_name_unique');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::connection(self::CONN)
            ->table('information_schema.statistics')
            ->where('table_schema', DB::connection(self::CONN)->getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function hasForeign(string $table, string $constraint): bool
    {
        return DB::connection(self::CONN)
            ->table('information_schema.table_constraints')
            ->where('constraint_schema', DB::connection(self::CONN)->getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
