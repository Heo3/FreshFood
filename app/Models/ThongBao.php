<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThongBao extends Model
{
    use HasFactory;

    protected $table = 'thong_bao';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'tieu_de',
        'noi_dung',
        'duong_dan',
        'loai_thong_bao',
        'da_doc',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'da_doc' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeChuaDoc(Builder $query): Builder
    {
        return $query->where('da_doc', false);
    }
}
