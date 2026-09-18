<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('pending_resign_date')->nullable()->after('resign_date')->index();
            $table->unsignedBigInteger('pending_resign_transaction_key')->nullable()->after('pending_resign_date');
            $table->timestamp('pending_resign_synced_at')->nullable()->after('pending_resign_transaction_key');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['pending_resign_date']);
            $table->dropColumn([
                'pending_resign_date',
                'pending_resign_transaction_key',
                'pending_resign_synced_at',
            ]);
        });
    }
};
