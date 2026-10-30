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
        Schema::create('chi_tiet_doi_tra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_doi_tra_id')->constrained('phieu_doi_tra')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_pham');
            $table->foreignId('lo_hang_id')->nullable()->constrained('lo_hang')->nullOnDelete();
            $table->decimal('so_luong', 12, 2);
            $table->decimal('don_gia', 12, 2);
            $table->decimal('thanh_tien', 12, 2);
            $table->enum('tinh_trang_hang', ['con_dung_duoc', 'hu_hong_huy', 'tra_ve_nha_cung_cap'])->default('hu_hong_huy');
            $table->timestamps();
            $table->index('phieu_doi_tra_id');
            $table->index('san_pham_id');
            $table->index('lo_hang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_doi_tra');
    }
};
