<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LichSuTichDiem extends Model
{
    use HasFactory;

    protected $table = 'lich_su_tich_diem';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'don_hang_id',
        'loai_giao_dich',
        'so_diem_thay_doi',
        'so_diem_sau_giao_dich',
        'ghi_chu',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_diem_thay_doi' => 'integer',
            'so_diem_sau_giao_dich' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }
}
