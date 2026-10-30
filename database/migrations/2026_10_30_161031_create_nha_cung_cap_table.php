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
        Schema::create('nha_cung_cap', function (Blueprint $table) {
            $table->id();
            $table->string('ma_ncc', 30)->unique();
            $table->string('ten_nha_cung_cap', 150);
            $table->string('nguoi_lien_he', 100)->nullable();
            $table->string('so_dien_thoai', 15);
            $table->string('email', 100)->nullable();
            $table->string('dia_chi')->nullable();
            $table->string('ma_so_thue', 30)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->tinyInteger('trang_thai')->default(1)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nha_cung_cap');
    }
};
