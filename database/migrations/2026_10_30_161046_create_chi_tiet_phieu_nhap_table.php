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
        Schema::create('chi_tiet_phieu_nhap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_nhap_id')->constrained('phieu_nhap')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_pham');
            $table->foreignId('lo_hang_id')->nullable()->constrained('lo_hang')->nullOnDelete();
            $table->string('ma_lo', 50)->nullable();
            $table->decimal('so_luong', 12, 2);
            $table->decimal('don_gia_nhap', 12, 2);
            $table->decimal('thanh_tien', 14, 2);
            $table->date('ngay_san_xuat')->nullable();
            $table->date('ngay_het_han')->index();
            $table->timestamps();
            $table->index('phieu_nhap_id');
            $table->index('san_pham_id');
            $table->index('lo_hang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_phieu_nhap');
    }
};
