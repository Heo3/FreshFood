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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 15)->nullable()->unique();
            $table->string('email', 100)->nullable()->unique();
            $table->string('password');
            $table->enum('role', ['khach_hang', 'nhan_vien', 'quan_ly', 'admin'])->default('khach_hang')->index();
            $table->foreignId('store_id')->nullable()->constrained('cua_hang')->nullOnDelete();
            $table->integer('loyalty_points')->default(0);
            $table->tinyInteger('status')->default(1)->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->index('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
