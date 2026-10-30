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
        Schema::create('danh_muc', function (Blueprint $table) {
            $table->id();
            $table->string('ten_danh_muc', 100);
            $table->string('duong_dan_seo', 120)->unique();
            $table->string('hinh_anh')->nullable();
            $table->foreignId('danh_muc_cha_id')->nullable()->constrained('danh_muc')->nullOnDelete();
            $table->timestamps();
            $table->index('danh_muc_cha_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('danh_muc');
    }
};
