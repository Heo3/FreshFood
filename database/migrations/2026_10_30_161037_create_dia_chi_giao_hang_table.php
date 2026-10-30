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
        Schema::create('dia_chi_giao_hang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ten_nguoi_nhan', 100);
            $table->string('so_dien_thoai_nhan', 15);
            $table->string('dia_chi_chi_tiet');
            $table->string('phuong_xa', 100);
            $table->string('quan_huyen', 100);
            $table->string('tinh_thanh', 100);
            $table->boolean('la_mac_dinh')->default(false);
            $table->timestamps();
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dia_chi_giao_hang');
    }
};
