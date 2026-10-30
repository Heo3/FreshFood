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
        Schema::create('the_kho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cua_hang_id')->constrained('cua_hang');
            $table->foreignId('san_pham_id')->constrained('san_pham');
            $table->foreignId('lo_hang_id')->nullable()->constrained('lo_hang')->nullOnDelete();
            $table->enum('loai_thao_tac', ['nhap_kho', 'xuat_ban', 'xuat_huy', 'xuat_chuyen', 'nhap_chuyen', 'can_bang_kiem_ke']);
            $table->decimal('so_luong_thay_doi', 12, 2);
            $table->decimal('ton_sau_thay_doi', 12, 2);
            $table->string('ma_chung_tu', 50)->index();
            $table->foreignId('nguoi_thuc_hien_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('ghi_chu')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['cua_hang_id', 'san_pham_id'], 'the_kho_cua_hang_san_pham_index');
            $table->index('lo_hang_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('the_kho');
    }
};
