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
        Schema::create('ma_giam_gia', function (Blueprint $table) {
            $table->id();
            $table->string('ma_khuyen_mai', 30)->unique();
            $table->string('ten_chuong_trinh', 150);
            $table->enum('loai_giam_gia', ['phan_tram', 'tien_mat']);
            $table->decimal('gia_tri_giam', 12, 2);
            $table->decimal('don_hang_toi_thieu', 12, 2)->default(0);
            $table->decimal('giam_toi_da', 12, 2)->nullable();
            $table->dateTime('ngay_bat_dau');
            $table->dateTime('ngay_ket_thuc');
            $table->integer('so_luong');
            $table->integer('da_su_dung')->default(0);
            $table->tinyInteger('trang_thai')->default(1);
            $table->timestamps();
            $table->index(['ngay_bat_dau', 'ngay_ket_thuc'], 'ma_giam_gia_thoi_gian_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ma_giam_gia');
    }
};
