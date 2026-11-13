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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('tieu_de');
            $table->string('phu_de')->nullable();
            $table->string('hinh_anh');
            $table->string('duong_dan')->nullable();
            $table->string('nhan_nut')->nullable()->default('Mua ngay');
            $table->string('vi_tri')->default('trang_chu');
            $table->integer('thu_tu')->default(0);
            $table->tinyInteger('trang_thai')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
