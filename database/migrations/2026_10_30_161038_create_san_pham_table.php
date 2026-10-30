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
        Schema::create('san_pham', function (Blueprint $table) {
            $table->id();
            $table->string('ma_va_vach', 50)->nullable()->unique();
            $table->string('ten_san_pham', 200);
            $table->string('duong_dan_seo', 220)->unique();
            $table->foreignId('danh_muc_id')->constrained('danh_muc');
            $table->decimal('gia_ban', 12, 2);
            $table->decimal('gia_giam', 12, 2)->nullable();
            $table->string('don_vi_tinh', 20);
            $table->string('quy_cach', 100)->nullable();
            $table->enum('dieu_kien_bao_quan', ['thuong', 'mat', 'dong_lanh'])->default('thuong');
            $table->integer('han_su_dung_ngay')->nullable();
            $table->text('mo_ta')->nullable();
            $table->string('hinh_anh_chinh')->nullable();
            $table->boolean('la_tuoi_song')->default(false)->index();
            $table->tinyInteger('trang_thai')->default(1)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index('danh_muc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('san_pham');
    }
};
