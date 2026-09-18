<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('manual_resign_date')->nullable()->after('pending_resign_synced_at')->index();
            $table->string('manual_resign_reason')->nullable()->after('manual_resign_date');
            $table->timestamp('manual_resign_set_at')->nullable()->after('manual_resign_reason');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['manual_resign_date']);
            $table->dropColumn([
                'manual_resign_date',
                'manual_resign_reason',
                'manual_resign_set_at',
            ]);
        });
    }
};
