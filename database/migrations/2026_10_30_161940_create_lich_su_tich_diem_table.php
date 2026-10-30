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
        Schema::create('lich_su_tich_diem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('don_hang_id')->nullable()->constrained('don_hang')->nullOnDelete();
            $table->enum('loai_giao_dich', ['tich_diem', 'tieu_diem', 'hoan_diem', 'admin_dieu_chinh'])->index();
            $table->integer('so_diem_thay_doi');
            $table->integer('so_diem_sau_giao_dich');
            $table->string('ghi_chu', 255)->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('don_hang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_su_tich_diem');
    }
};
