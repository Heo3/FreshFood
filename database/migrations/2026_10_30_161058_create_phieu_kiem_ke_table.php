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
        Schema::create('phieu_kiem_ke', function (Blueprint $table) {
            $table->id();
            $table->string('ma_phieu', 30)->unique();
            $table->foreignId('cua_hang_id')->constrained('cua_hang');
            $table->foreignId('nguoi_tao_id')->constrained('users');
            $table->foreignId('nguoi_duyet_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('ngay_kiem_ke');
            $table->tinyInteger('trang_thai')->default(0)->index();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->index('cua_hang_id');
            $table->index('nguoi_tao_id');
            $table->index('nguoi_duyet_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phieu_kiem_ke');
    }
};
