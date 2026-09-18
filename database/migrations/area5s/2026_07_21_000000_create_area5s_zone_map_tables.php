<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONN = 'mysql_area5s';

    public function up(): void
    {
        Schema::connection(self::CONN)->create('a5s_zone_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('a5s_zones')->cascadeOnDelete();
            $table->string('name');
            $table->string('image_path');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['zone_id', 'sort']);
        });

        Schema::connection(self::CONN)->create('a5s_zone_map_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_map_id')->constrained('a5s_zone_maps')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('shape_points');
            $table->string('color', 20)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['zone_map_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->dropIfExists('a5s_zone_map_areas');
        Schema::connection(self::CONN)->dropIfExists('a5s_zone_maps');
    }
};
