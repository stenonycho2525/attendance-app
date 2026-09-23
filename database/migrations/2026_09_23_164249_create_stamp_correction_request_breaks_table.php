<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stamp_correction_request_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stamp_correction_request_id')
                ->constrained('stamp_correction_requests', 'id', 'scr_breaks_scr_id_foreign')->cascadeOnDelete();
            $table->time('break_in')->nullable();
            $table->time('break_out')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamp_correction_request_breaks');
    }
};
