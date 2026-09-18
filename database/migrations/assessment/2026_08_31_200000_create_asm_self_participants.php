<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'mysql_assessment';

    public function up(): void
    {
        Schema::connection(self::CONNECTION)->create('asm_self_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->string('employee_code', 50);
            $table->boolean('is_selected')->default(false);
            $table->unsignedBigInteger('selected_by')->nullable();
            $table->timestamp('selected_at')->nullable();
            $table->timestamps();

            $table->unique(['round_id', 'employee_code'], 'asm_self_participants_round_employee_unique');
            $table->index(['round_id', 'is_selected'], 'asm_self_participants_round_selected_index');
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONNECTION)->dropIfExists('asm_self_participants');
    }
};
