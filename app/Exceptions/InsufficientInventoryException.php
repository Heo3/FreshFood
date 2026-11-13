<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientInventoryException extends RuntimeException
{
    /**
     * Khởi tạo ngoại lệ khi không đủ tồn kho theo nguyên tắc FEFO.
     */
    public function __construct(
        public int $sanPhamId,
        public string $sanPhamTen,
        public float $soLuongYeuCau,
        public float $soLuongKhaDung,
        string $message = ''
    ) {
        if ($message === '') {
            $message = sprintf(
                'Sản phẩm "%s" không đủ tồn kho khả dụng để xuất kho (Yêu cầu: %s, Khả dụng còn hạn: %s).',
                $sanPhamTen,
                $soLuongYeuCau,
                $soLuongKhaDung
            );
        }

        parent::__construct($message);
    }
}
