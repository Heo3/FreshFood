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
        // 1. Cập nhật bảng users: thêm vai trò giao_hang và thông tin đội xe (xe máy / xe tải)
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['khach_hang', 'nhan_vien', 'quan_ly', 'admin', 'giao_hang'])
                ->default('khach_hang')
                ->change();

            $table->enum('loai_phuong_tien', ['xe_may', 'xe_tai'])->nullable()->after('role');
            $table->string('bien_so_xe', 30)->nullable()->after('loai_phuong_tien');
            $table->integer('tai_trong_kg')->nullable()->after('bien_so_xe');
            $table->enum('trang_thai_giao', ['san_sang', 'dang_giao', 'nghi'])->default('san_sang')->after('tai_trong_kg');
        });

        // 2. Cập nhật bảng don_hang: gán nhân viên giao hàng, loại xe (xe máy / xe tải) và thời gian giao
        Schema::table('don_hang', function (Blueprint $table) {
            $table->foreignId('nguoi_giao_hang_id')->nullable()->after('trang_thai_don_hang')->constrained('users')->nullOnDelete();
            $table->enum('loai_phuong_tien_giao', ['xe_may', 'xe_tai'])->nullable()->after('nguoi_giao_hang_id');
            $table->string('bien_so_xe_giao', 30)->nullable()->after('loai_phuong_tien_giao');
            $table->text('ghi_chu_giao_hang')->nullable()->after('bien_so_xe_giao');
            $table->timestamp('thoi_gian_bat_dau_giao')->nullable()->after('ghi_chu_giao_hang');
            $table->timestamp('thoi_gian_giao_thanh_cong')->nullable()->after('thoi_gian_bat_dau_giao');
        });

        // 3. Cập nhật bảng phieu_xuat: ghi nhận tài xế và phương tiện điều chuyển liên chi nhánh
        Schema::table('phieu_xuat', function (Blueprint $table) {
            $table->foreignId('nguoi_giao_hang_id')->nullable()->after('nguoi_duyet_id')->constrained('users')->nullOnDelete();
            $table->enum('loai_phuong_tien', ['xe_tai', 'xe_may'])->nullable()->after('nguoi_giao_hang_id');
            $table->string('bien_so_xe', 30)->nullable()->after('loai_phuong_tien');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phieu_xuat', function (Blueprint $table) {
            $table->dropForeign(['nguoi_giao_hang_id']);
            $table->dropColumn(['nguoi_giao_hang_id', 'loai_phuong_tien', 'bien_so_xe']);
        });

        Schema::table('don_hang', function (Blueprint $table) {
            $table->dropForeign(['nguoi_giao_hang_id']);
            $table->dropColumn([
                'nguoi_giao_hang_id',
                'loai_phuong_tien_giao',
                'bien_so_xe_giao',
                'ghi_chu_giao_hang',
                'thoi_gian_bat_dau_giao',
                'thoi_gian_giao_thanh_cong',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['loai_phuong_tien', 'bien_so_xe', 'tai_trong_kg', 'trang_thai_giao']);
            $table->enum('role', ['khach_hang', 'nhan_vien', 'quan_ly', 'admin'])
                ->default('khach_hang')
                ->change();
        });
    }
};
