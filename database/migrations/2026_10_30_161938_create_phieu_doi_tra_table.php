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
        Schema::create('phieu_doi_tra', function (Blueprint $table) {
            $table->id();
            $table->string('ma_phieu', 30)->unique();
            $table->foreignId('don_hang_id')->constrained('don_hang');
            $table->foreignId('cua_hang_id')->constrained('cua_hang');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('nguoi_tao_id');
            $table->foreignId('nguoi_duyet_id')->nullable();
            $table->enum('loai_doi_tra', ['doi_hang', 'hoan_tien'])->default('hoan_tien');
            $table->decimal('tong_tien_hoan', 12, 2)->default(0);
            $table->tinyInteger('trang_thai')->default(0)->index();
            $table->text('ly_do');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->index('don_hang_id');
            $table->index('cua_hang_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phieu_doi_tra');
    }
};
