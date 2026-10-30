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
        Schema::create('cua_hang', function (Blueprint $table) {
            $table->id();
            $table->string('ten_cua_hang', 150);
            $table->string('so_dien_thoai', 15)->nullable();
            $table->string('dia_chi', 255);
            $table->decimal('kinh_do', 10, 7)->nullable();
            $table->decimal('vi_do', 10, 7)->nullable();
            $table->time('gio_mo_cua')->default('06:00:00');
            $table->time('gio_dong_cua')->default('21:30:00');
            $table->tinyInteger('trang_thai')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cua_hang');
    }
};
