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
        Schema::create('lo_hang', function (Blueprint $table) {
            $table->id();
            $table->string('ma_lo', 50)->unique();
            $table->foreignId('san_pham_id')->constrained('san_pham');
            $table->foreignId('cua_hang_id')->constrained('cua_hang');
            $table->foreignId('nha_cung_cap_id')->nullable()->constrained('nha_cung_cap')->nullOnDelete();
            $table->foreignId('phieu_nhap_id')->nullable()->constrained('phieu_nhap')->nullOnDelete();
            $table->date('ngay_san_xuat')->nullable();
            $table->date('ngay_het_han')->index();
            $table->decimal('so_luong_nhap', 12, 2);
            $table->decimal('so_luong_con_lai', 12, 2)->default(0);
            $table->decimal('gia_nhap', 12, 2);
            $table->tinyInteger('trang_thai')->default(1)->index();
            $table->timestamps();
            $table->index(['san_pham_id', 'cua_hang_id', 'trang_thai', 'ngay_het_han'], 'idx_fefo_query');
            $table->index('cua_hang_id');
            $table->index('nha_cung_cap_id');
            $table->index('phieu_nhap_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lo_hang');
    }
};
