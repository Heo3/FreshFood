<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ton_kho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cua_hang_id')->constrained('cua_hang')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_pham')->cascadeOnDelete();
            $table->decimal('so_luong_ton', 12, 2)->default(0);
            $table->decimal('so_luong_tam_giu', 12, 2)->default(0);
            $table->decimal('nguong_canh_bao_thap', 10, 2)->default(10);
            $table->timestamps();
            $table->unique(['cua_hang_id', 'san_pham_id'], 'uk_cuahang_sanpham');
            $table->index('cua_hang_id');
            $table->index('san_pham_id');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `ton_kho` ADD CONSTRAINT `chk_ton_kho_so_luong` CHECK (`so_luong_ton` >= 0 AND `so_luong_tam_giu` >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ton_kho');
    }
};
