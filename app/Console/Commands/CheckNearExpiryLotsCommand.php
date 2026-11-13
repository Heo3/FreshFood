<?php

namespace App\Console\Commands;

use App\Models\CuaHang;
use App\Models\LoHang;
use App\Models\ThongBao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckNearExpiryLotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-expiry {--days=2 : Số ngày ngưỡng cận hạn}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quét các lô hàng cận hạn sử dụng và tạo thông báo hệ thống cho Quản lý chi nhánh';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $thresholdDate = Carbon::today()->addDays($days)->toDateString();

        $this->info("Đang quét các lô hàng cận hạn sử dụng (<= {$days} ngày, đến ngày {$thresholdDate})...");

        $nearExpiryLots = LoHang::query()
            ->with(['sanPham', 'cuaHang'])
            ->where('trang_thai', 1)
            ->where('so_luong_con_lai', '>', 0)
            ->where('ngay_het_han', '<=', $thresholdDate)
            ->orderBy('ngay_het_han', 'asc')
            ->get();

        if ($nearExpiryLots->isEmpty()) {
            $this->info('Không có lô hàng nào cận date. Kho an toàn 100%!');

            return Command::SUCCESS;
        }

        $groupedByStore = $nearExpiryLots->groupBy('cua_hang_id');

        $totalNotifications = 0;

        foreach ($groupedByStore as $storeId => $lots) {
            $store = CuaHang::find($storeId);
            $storeName = $store?->ten_cua_hang ?? "Chi nhánh #{$storeId}";
            $count = $lots->count();

            // Tìm quản lý của chi nhánh này và admin
            $recipients = User::query()
                ->where(function ($q) use ($storeId) {
                    $q->where('store_id', $storeId)
                        ->where('role', 'quan_ly');
                })
                ->orWhere('role', 'admin')
                ->where('status', 1)
                ->get();

            $summaryProducts = $lots->take(3)->map(fn ($l) => $l->sanPham?->ten_san_pham)->filter()->join(', ');
            if ($lots->count() > 3) {
                $summaryProducts .= ' và '.($lots->count() - 3).' sản phẩm khác';
            }

            foreach ($recipients as $recipient) {
                ThongBao::create([
                    'user_id' => $recipient->id,
                    'tieu_de' => "Cảnh báo: Có {$count} lô hàng cận date tại {$storeName}",
                    'noi_dung' => "Các mặt hàng: {$summaryProducts}. Vui lòng kiểm tra và áp dụng giảm giá xả hàng trong ngày.",
                    'duong_dan' => '/store/canh-bao-can-date',
                    'loai_thong_bao' => 'he_thong',
                    'da_doc' => false,
                ]);

                $totalNotifications++;
            }

            $this->line("- {$storeName}: {$count} lô cận date (Đã tạo thông báo cho {$recipients->count()} nhân sự)");
        }

        $this->info("Hoàn tất! Tổng cộng đã gửi {$totalNotifications} thông báo.");

        return Command::SUCCESS;
    }
}
