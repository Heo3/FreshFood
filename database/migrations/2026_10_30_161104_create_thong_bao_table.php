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
        Schema::create('thong_bao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tieu_de', 150);
            $table->text('noi_dung');
            $table->string('duong_dan')->nullable();
            $table->enum('loai_thong_bao', ['don_hang', 'khuyen_mai', 'he_thong'])->index();
            $table->boolean('da_doc')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'da_doc'], 'thong_bao_user_id_da_doc_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thong_bao');
    }
};
