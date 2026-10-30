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
        Schema::create('gia_san_pham_cua_hang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cua_hang_id')->constrained('cua_hang')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_pham')->cascadeOnDelete();
            $table->decimal('gia_ban', 12, 2);
            $table->decimal('gia_giam', 12, 2)->nullable();
            $table->timestamps();
            $table->unique(['cua_hang_id', 'san_pham_id'], 'uk_cuahang_sanpham_gia');
            $table->index('san_pham_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gia_san_pham_cua_hang');
    }
};
