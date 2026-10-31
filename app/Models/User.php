<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
        'store_id',
        'loyalty_points',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'loyalty_points' => 'integer',
            'status' => 'integer',
        ];
    }

    public function cuaHang(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'store_id');
    }

    public function diaChis(): HasMany
    {
        return $this->hasMany(DiaChiGiaoHang::class, 'user_id');
    }

    public function donHangs(): HasMany
    {
        return $this->hasMany(DonHang::class, 'user_id');
    }

    public function gioHangs(): HasMany
    {
        return $this->hasMany(GioHang::class, 'user_id');
    }

    public function danhGias(): HasMany
    {
        return $this->hasMany(DanhGiaSanPham::class, 'user_id');
    }

    public function thongBaos(): HasMany
    {
        return $this->hasMany(ThongBao::class, 'user_id');
    }

    public function phieuDoiTras(): HasMany
    {
        return $this->hasMany(PhieuDoiTra::class, 'user_id');
    }

    public function lichSuTichDiems(): HasMany
    {
        return $this->hasMany(LichSuTichDiem::class, 'user_id');
    }

    public function phieuNhapsTao(): HasMany
    {
        return $this->hasMany(PhieuNhap::class, 'nguoi_tao_id');
    }

    public function phieuXuatsTao(): HasMany
    {
        return $this->hasMany(PhieuXuat::class, 'nguoi_tao_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isQuanLy(): bool
    {
        return $this->role === 'quan_ly';
    }

    public function isNhanVien(): bool
    {
        return $this->role === 'nhan_vien';
    }

    public function isKhachHang(): bool
    {
        return $this->role === 'khach_hang';
    }
}
