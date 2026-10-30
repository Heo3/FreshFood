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
        Schema::create('danh_gia_san_pham', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_pham')->cascadeOnDelete();
            $table->foreignId('don_hang_id')->nullable()->constrained('don_hang')->nullOnDelete();
            $table->unsignedTinyInteger('so_sao')->default(5);
            $table->text('noi_dung')->nullable();
            $table->text('hinh_anh')->nullable();
            $table->tinyInteger('trang_thai')->default(1)->index();
            $table->timestamps();
            $table->index('user_id');
            $table->index('san_pham_id');
            $table->index('don_hang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('danh_gia_san_pham');
    }
};
