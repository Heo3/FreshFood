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
        Schema::create('chi_tiet_don_hang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_hang_id')->constrained('don_hang')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_pham');
            $table->string('ten_san_pham', 200);
            $table->string('hinh_anh_snapshot')->nullable();
            $table->string('don_vi_tinh', 20)->nullable();
            $table->decimal('don_gia', 12, 2);
            $table->decimal('gia_von', 12, 2)->default(0);
            $table->decimal('so_luong', 10, 2);
            $table->decimal('thanh_tien', 12, 2);
            $table->timestamps();
            $table->index('don_hang_id');
            $table->index('san_pham_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_don_hang');
    }
};
