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
        Schema::create('chi_tiet_xuat_kho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chi_tiet_phieu_xuat_id')->constrained('chi_tiet_phieu_xuat')->cascadeOnDelete();
            $table->foreignId('lo_hang_id')->constrained('lo_hang');
            $table->decimal('so_luong', 12, 2);
            $table->decimal('gia_xuat', 12, 2)->nullable();
            $table->timestamps();
            $table->index('chi_tiet_phieu_xuat_id');
            $table->index('lo_hang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_xuat_kho');
    }
};
