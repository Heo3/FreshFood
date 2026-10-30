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
        Schema::create('chi_tiet_kiem_ke', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_kiem_ke_id')->constrained('phieu_kiem_ke')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_pham');
            $table->foreignId('lo_hang_id')->nullable()->constrained('lo_hang')->nullOnDelete();
            $table->decimal('so_luong_he_thong', 12, 2);
            $table->decimal('so_luong_thuc_te', 12, 2);
            $table->decimal('so_luong_lech', 12, 2)->storedAs('so_luong_thuc_te - so_luong_he_thong');
            $table->string('ly_do_lech', 255)->nullable();
            $table->timestamps();
            $table->index('phieu_kiem_ke_id');
            $table->index('san_pham_id');
            $table->index('lo_hang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_kiem_ke');
    }
};
