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
        Schema::create('chi_tiet_phieu_xuat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_xuat_id')->constrained('phieu_xuat')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_pham');
            $table->decimal('so_luong', 12, 2);
            $table->decimal('gia_xuat', 12, 2)->nullable();
            $table->decimal('thanh_tien', 14, 2)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->index('phieu_xuat_id');
            $table->index('san_pham_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_phieu_xuat');
    }
};
