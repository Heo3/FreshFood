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
        Schema::create('don_hang', function (Blueprint $table) {
            $table->id();
            $table->string('ma_don_hang', 30)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('cua_hang_id')->constrained('cua_hang');
            $table->foreignId('dia_chi_giao_hang_id')->nullable()->constrained('dia_chi_giao_hang')->nullOnDelete();
            $table->foreignId('ma_giam_gia_id')->nullable()->constrained('ma_giam_gia')->nullOnDelete();
            $table->decimal('tong_tien_hang', 12, 2);
            $table->decimal('phi_giao_hang', 12, 2)->default(0);
            $table->decimal('tien_giam_gia', 12, 2)->default(0);
            $table->decimal('tong_thanhtoan', 12, 2);
            $table->enum('phuong_thuc_thanh_toan', ['cod', 'momo', 'vnpay', 'chuyen_khoan'])->default('cod');
            $table->string('ma_giao_dich', 100)->nullable();
            $table->enum('trang_thai_thanh_toan', ['chua_thanh_toan', 'da_thanh_toan', 'hoan_tien'])->default('chua_thanh_toan')->index();
            $table->enum('trang_thai_don_hang', ['cho_xac_nhan', 'dang_chuan_bi', 'dang_giao', 'da_giao', 'da_huy'])->default('cho_xac_nhan')->index();
            $table->string('ten_nguoi_nhan', 100);
            $table->string('so_dien_thoai_nhan', 15);
            $table->string('dia_chi_giao', 255);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('user_id');
            $table->index('dia_chi_giao_hang_id');
            $table->index('ma_giam_gia_id');
            $table->index('cua_hang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('don_hang');
    }
};
