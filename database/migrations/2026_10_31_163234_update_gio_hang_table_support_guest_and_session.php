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
        Schema::table('gio_hang', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('session_id', 100)->nullable()->index()->after('user_id');
            $table->index(['session_id', 'cua_hang_id', 'san_pham_id'], 'idx_giohang_session_store_prod');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gio_hang', function (Blueprint $table) {
            $table->dropIndex('idx_giohang_session_store_prod');
            $table->dropColumn('session_id');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
