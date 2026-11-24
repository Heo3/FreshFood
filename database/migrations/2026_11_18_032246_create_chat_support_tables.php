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
        Schema::create('phien_chat', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cua_hang_id')->nullable()->constrained('cua_hang')->nullOnDelete();
            $table->foreignId('nhan_vien_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('loai_chat', 20)->default('ai')->index(); // ai, nhan_vien
            $table->string('chu_de', 30)->default('tu_van'); // tu_van, khieu_nai, doi_tra, khac
            $table->string('ma_don_hang', 50)->nullable()->index();
            $table->string('ten_khach_hang', 100)->nullable();
            $table->string('so_dien_thoai', 20)->nullable();
            $table->string('trang_thai', 20)->default('cho_xu_ly')->index(); // cho_xu_ly, dang_xu_ly, da_dong
            $table->unsignedTinyInteger('danh_gia_sao')->nullable();
            $table->text('tin_nhan_cuoi')->nullable();
            $table->timestamp('thoi_gian_tin_nhan_cuoi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tin_nhan_chat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phien_chat_id')->constrained('phien_chat')->cascadeOnDelete();
            $table->string('nguoi_gui_type', 20)->index(); // khach_hang, ai, nhan_vien, he_thong
            $table->foreignId('nguoi_gui_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('noi_dung');
            $table->json('du_lieu_dinh_kem')->nullable();
            $table->boolean('da_xem')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tin_nhan_chat');
        Schema::dropIfExists('phien_chat');
    }
};
