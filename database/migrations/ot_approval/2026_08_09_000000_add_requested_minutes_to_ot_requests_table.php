<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    public function up(): void
    {
        Schema::connection(self::CONN)->table('ot_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('requested_minutes')->default(0)->after('requested_hours');
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->table('ot_requests', function (Blueprint $table) {
            $table->dropColumn('requested_minutes');
        });
    }
};
