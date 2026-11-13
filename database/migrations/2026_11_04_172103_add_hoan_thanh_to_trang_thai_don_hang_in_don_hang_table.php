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
        Schema::table('don_hang', function (Blueprint $table) {
            $table->enum('trang_thai_don_hang', [
                'cho_xac_nhan',
                'dang_chuan_bi',
                'dang_giao',
                'da_giao',
                'hoan_thanh',
                'da_huy',
            ])->default('cho_xac_nhan')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('don_hang', function (Blueprint $table) {
            $table->enum('trang_thai_don_hang', [
                'cho_xac_nhan',
                'dang_chuan_bi',
                'dang_giao',
                'da_giao',
                'da_huy',
            ])->default('cho_xac_nhan')->change();
        });
    }
};
