<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TinNhanChat extends Model
{
    use HasFactory;

    protected $table = 'tin_nhan_chat';

    public const NGUOI_GUI_KHACH_HANG = 'khach_hang';

    public const NGUOI_GUI_AI = 'ai';

    public const NGUOI_GUI_NHAN_VIEN = 'nhan_vien';

    public const NGUOI_GUI_HE_THONG = 'he_thong';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'phien_chat_id',
        'nguoi_gui_type',
        'nguoi_gui_id',
        'noi_dung',
        'du_lieu_dinh_kem',
        'da_xem',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'du_lieu_dinh_kem' => 'array',
            'da_xem' => 'boolean',
        ];
    }

    public function phienChat(): BelongsTo
    {
        return $this->belongsTo(PhienChat::class, 'phien_chat_id');
    }

    public function nguoiGui(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_gui_id');
    }
}
