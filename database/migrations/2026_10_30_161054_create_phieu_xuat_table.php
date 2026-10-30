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
        Schema::create('phieu_xuat', function (Blueprint $table) {
            $table->id();
            $table->string('ma_phieu', 30)->unique();
            $table->foreignId('cua_hang_id')->constrained('cua_hang');
            $table->tinyInteger('loai_xuat')->index();
            $table->foreignId('don_hang_id')->nullable()->constrained('don_hang')->nullOnDelete();
            $table->foreignId('cua_hang_nhan_id')->nullable()->constrained('cua_hang')->nullOnDelete();
            $table->foreignId('nha_cung_cap_id')->nullable()->constrained('nha_cung_cap')->nullOnDelete();
            $table->foreignId('nguoi_tao_id')->constrained('users');
            $table->foreignId('nguoi_duyet_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('trang_thai')->default(0)->index();
            $table->decimal('tong_so_luong', 12, 2)->default(0);
            $table->decimal('tong_gia_tri', 14, 2)->default(0);
            $table->text('ly_do')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamp('xuat_luc')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['cua_hang_id', 'loai_xuat'], 'idx_ch_loai');
            $table->index('don_hang_id');
            $table->index('cua_hang_nhan_id');
            $table->index('nha_cung_cap_id');
            $table->index('nguoi_tao_id');
            $table->index('nguoi_duyet_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phieu_xuat');
    }
};
