<?php

namespace App\Services;

use App\Exceptions\InsufficientInventoryException;
use App\Models\ChiTietDonHang;
use App\Models\ChiTietPhieuXuat;
use App\Models\ChiTietXuatKho;
use App\Models\DonHang;
use App\Models\LoHang;
use App\Models\PhieuXuat;
use App\Models\SanPham;
use App\Models\TheKho;
use App\Models\TonKho;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryService
{
    /**
     * Kiểm tra tính khả dụng của tồn kho theo nguyên tắc FEFO trước khi xuất.
     *
     * @return array{
     *     kha_dung: bool,
     *     tong_ton: float,
     *     tong_con_han: float,
     *     thieu: float,
     *     lo_hangs: Collection<int, LoHang>
     * }
     */
    public function kiemTraKhaDung(
        int $cuaHangId,
        int $sanPhamId,
        float $soLuongCan,
        bool $chiLayConHan = true
    ): array {
        $today = Carbon::today()->toDateString();

        $query = LoHang::query()
            ->where('cua_hang_id', $cuaHangId)
            ->where('san_pham_id', $sanPhamId)
            ->where('trang_thai', 1)
            ->where('so_luong_con_lai', '>', 0);

        if ($chiLayConHan) {
            $query->where('ngay_het_han', '>=', $today);
        }

        /** @var Collection<int, LoHang> $loHangs */
        $loHangs = $query->orderBy('ngay_het_han', 'asc')->get();

        $tongConHan = (float) $loHangs->sum('so_luong_con_lai');

        $tongTonKho = (float) (TonKho::query()
            ->where('cua_hang_id', $cuaHangId)
            ->where('san_pham_id', $sanPhamId)
            ->value('so_luong_ton') ?? 0);

        $thieu = max(0.0, $soLuongCan - $tongConHan);

        return [
            'kha_dung' => $thieu <= 0.0001,
            'tong_ton' => $tongTonKho,
            'tong_con_han' => $tongConHan,
            'thieu' => round($thieu, 2),
            'lo_hangs' => $loHangs,
        ];
    }

    /**
     * Xuất kho toàn bộ sản phẩm trong Đơn Hàng theo nguyên tắc FEFO.
     *
     * @throws InsufficientInventoryException
     */
    public function xuatKhoDonHang(DonHang $donHang, ?int $nguoiThucHienId = null): PhieuXuat
    {
        return DB::transaction(function () use ($donHang, $nguoiThucHienId): PhieuXuat {
            $cuaHangId = (int) $donHang->cua_hang_id;
            $donHang->loadMissing('chiTiets.sanPham');

            if ($donHang->chiTiets->isEmpty()) {
                throw new \InvalidArgumentException('Đơn hàng không có sản phẩm nào để xuất kho.');
            }

            // 1. Kiểm tra tồn kho khả dụng còn hạn (FEFO) cho tất cả sản phẩm trước khi xuất
            foreach ($donHang->chiTiets as $chiTiet) {
                /** @var ChiTietDonHang $chiTiet */
                $sanPhamId = (int) $chiTiet->san_pham_id;
                $soLuongYeuCau = (float) $chiTiet->so_luong;
                $sanPhamTen = $chiTiet->sanPham?->ten_san_pham ?? "Sản phẩm #{$sanPhamId}";

                $kiemTra = $this->kiemTraKhaDung($cuaHangId, $sanPhamId, $soLuongYeuCau, true);

                if (! $kiemTra['kha_dung']) {
                    throw new InsufficientInventoryException(
                        sanPhamId: $sanPhamId,
                        sanPhamTen: $sanPhamTen,
                        soLuongYeuCau: $soLuongYeuCau,
                        soLuongKhaDung: $kiemTra['tong_con_han']
                    );
                }
            }

            // 2. Tạo chứng từ Phiếu Xuất Kho
            $maPhieu = $this->sinhMaPhieu('PX');
            $phieuXuat = PhieuXuat::create([
                'ma_phieu' => $maPhieu,
                'cua_hang_id' => $cuaHangId,
                'loai_xuat' => PhieuXuat::LOAI_XUAT_BAN,
                'don_hang_id' => $donHang->id,
                'nguoi_tao_id' => $nguoiThucHienId ?? $donHang->user_id ?? 1,
                'nguoi_duyet_id' => $nguoiThucHienId,
                'trang_thai' => PhieuXuat::TRANG_THAI_DA_XUAT,
                'tong_so_luong' => 0,
                'tong_gia_tri' => 0,
                'ly_do' => "Xuất kho bán hàng cho đơn #{$donHang->ma_don_hang}",
                'xuat_luc' => now(),
            ]);

            $tongSoLuongXuat = 0.0;
            $tongGiaTriXuat = 0.0;
            $today = Carbon::today()->toDateString();

            // 3. Thực hiện xuất kho FEFO từng sản phẩm
            foreach ($donHang->chiTiets as $chiTiet) {
                /** @var ChiTietDonHang $chiTiet */
                $sanPhamId = (int) $chiTiet->san_pham_id;
                $soLuongCanXuat = (float) $chiTiet->so_luong;
                $donGiaXuat = (float) $chiTiet->don_gia;
                $soLuongConLaiCanTru = $soLuongCanXuat;

                // Tạo ChiTietPhieuXuat
                $chiTietPhieuXuat = ChiTietPhieuXuat::create([
                    'phieu_xuat_id' => $phieuXuat->id,
                    'san_pham_id' => $sanPhamId,
                    'so_luong' => $soLuongCanXuat,
                    'gia_xuat' => $donGiaXuat,
                    'thanh_tien' => $soLuongCanXuat * $donGiaXuat,
                    'ghi_chu' => "Xuất theo đơn hàng #{$donHang->ma_don_hang}",
                ]);

                // Lấy các lô hàng còn hạn, sắp xếp FEFO (hết hạn sớm nhất lên trước) và lock bản ghi
                /** @var Collection<int, LoHang> $loHangs */
                $loHangs = LoHang::query()
                    ->where('cua_hang_id', $cuaHangId)
                    ->where('san_pham_id', $sanPhamId)
                    ->where('trang_thai', 1)
                    ->where('so_luong_con_lai', '>', 0)
                    ->where('ngay_het_han', '>=', $today)
                    ->orderBy('ngay_het_han', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($loHangs as $loHang) {
                    if ($soLuongConLaiCanTru <= 0) {
                        break;
                    }

                    $soLuongCoTheLay = (float) $loHang->so_luong_con_lai;
                    $soLuongLayTuLo = min($soLuongConLaiCanTru, $soLuongCoTheLay);

                    // Trừ số lượng còn lại trong lô
                    $loHang->so_luong_con_lai = round($soLuongCoTheLay - $soLuongLayTuLo, 2);
                    if ($loHang->so_luong_con_lai <= 0.0001) {
                        $loHang->so_luong_con_lai = 0;
                    }
                    $loHang->save();

                    // Ghi nhận ChiTietXuatKho gắn với từng Lô Hàng
                    ChiTietXuatKho::create([
                        'chi_tiet_phieu_xuat_id' => $chiTietPhieuXuat->id,
                        'lo_hang_id' => $loHang->id,
                        'so_luong' => $soLuongLayTuLo,
                        'gia_xuat' => $loHang->gia_nhap ?? $donGiaXuat,
                    ]);

                    // Gán mã lô hàng thực tế xuất vào chi_tiet_don_hang để phục vụ truy vết ATTP & in tem
                    if (! $chiTiet->lo_hang_id) {
                        $chiTiet->lo_hang_id = $loHang->id;
                        $chiTiet->save();
                    }

                    $soLuongConLaiCanTru -= $soLuongLayTuLo;
                }

                // Cập nhật bảng TonKho của chi nhánh
                /** @var TonKho|null $tonKho */
                $tonKho = TonKho::query()
                    ->where('cua_hang_id', $cuaHangId)
                    ->where('san_pham_id', $sanPhamId)
                    ->lockForUpdate()
                    ->first();

                $tonSauThayDoi = 0.0;
                if ($tonKho) {
                    $tonHienTai = (float) $tonKho->so_luong_ton;
                    $tonKho->so_luong_ton = max(0.0, round($tonHienTai - $soLuongCanXuat, 2));
                    $tonKho->save();
                    $tonSauThayDoi = (float) $tonKho->so_luong_ton;
                }

                // Ghi nhận Sổ Thẻ Kho (TheKho)
                TheKho::create([
                    'cua_hang_id' => $cuaHangId,
                    'san_pham_id' => $sanPhamId,
                    'lo_hang_id' => $loHangs->first()?->id,
                    'loai_thao_tac' => 'xuat_ban',
                    'so_luong_thay_doi' => -$soLuongCanXuat,
                    'ton_sau_thay_doi' => $tonSauThayDoi,
                    'ma_chung_tu' => $maPhieu,
                    'nguoi_thuc_hien_id' => $nguoiThucHienId,
                    'ghi_chu' => "Xuất bán theo đơn hàng #{$donHang->ma_don_hang} (FEFO)",
                ]);

                $tongSoLuongXuat += $soLuongCanXuat;
                $tongGiaTriXuat += ($soLuongCanXuat * $donGiaXuat);
            }

            // 4. Cập nhật tổng số lượng & tổng giá trị phiếu xuất
            $phieuXuat->tong_so_luong = round($tongSoLuongXuat, 2);
            $phieuXuat->tong_gia_tri = round($tongGiaTriXuat, 2);
            $phieuXuat->save();

            return $phieuXuat->load(['chiTiets.chiTietXuatKhos.loHang']);
        });
    }

    /**
     * Xuất kho một sản phẩm đơn lẻ theo nguyên tắc FEFO (dùng cho POS, xuất lẻ, hoặc xuất hủy).
     *
     * @return array{
     *     so_luong_da_xuat: float,
     *     lo_da_tru: list<array{lo_hang_id: int, ma_lo: string, so_luong: float, ngay_het_han: string}>,
     *     ton_sau_xuat: float
     * }
     *
     * @throws InsufficientInventoryException
     */
    public function xuatKhoSanPham(
        int $cuaHangId,
        int $sanPhamId,
        float $soLuong,
        string $loaiThaoTac = 'xuat_ban',
        ?string $maChungTu = null,
        ?int $nguoiThucHienId = null,
        ?string $ghiChu = null,
        bool $chiLayConHan = true
    ): array {
        return DB::transaction(function () use (
            $cuaHangId,
            $sanPhamId,
            $soLuong,
            $loaiThaoTac,
            $maChungTu,
            $nguoiThucHienId,
            $ghiChu,
            $chiLayConHan
        ): array {
            $kiemTra = $this->kiemTraKhaDung($cuaHangId, $sanPhamId, $soLuong, $chiLayConHan);
            if (! $kiemTra['kha_dung']) {
                $sanPham = SanPham::find($sanPhamId);
                throw new InsufficientInventoryException(
                    sanPhamId: $sanPhamId,
                    sanPhamTen: $sanPham?->ten_san_pham ?? "Sản phẩm #{$sanPhamId}",
                    soLuongYeuCau: $soLuong,
                    soLuongKhaDung: $kiemTra['tong_con_han']
                );
            }

            $today = Carbon::today()->toDateString();
            $query = LoHang::query()
                ->where('cua_hang_id', $cuaHangId)
                ->where('san_pham_id', $sanPhamId)
                ->where('trang_thai', 1)
                ->where('so_luong_con_lai', '>', 0);

            if ($chiLayConHan) {
                $query->where('ngay_het_han', '>=', $today);
            }

            /** @var Collection<int, LoHang> $loHangs */
            $loHangs = $query->orderBy('ngay_het_han', 'asc')
                ->lockForUpdate()
                ->get();

            $soLuongCanTru = $soLuong;
            $loDaTru = [];

            foreach ($loHangs as $loHang) {
                if ($soLuongCanTru <= 0) {
                    break;
                }

                $soLuongCoTheLay = (float) $loHang->so_luong_con_lai;
                $soLuongLayTuLo = min($soLuongCanTru, $soLuongCoTheLay);

                $loHang->so_luong_con_lai = round($soLuongCoTheLay - $soLuongLayTuLo, 2);
                if ($loHang->so_luong_con_lai <= 0.0001) {
                    $loHang->so_luong_con_lai = 0;
                }
                $loHang->save();

                $loDaTru[] = [
                    'lo_hang_id' => $loHang->id,
                    'ma_lo' => $loHang->ma_lo,
                    'so_luong' => $soLuongLayTuLo,
                    'ngay_het_han' => $loHang->ngay_het_han ? $loHang->ngay_het_han->format('Y-m-d') : '',
                ];

                $soLuongCanTru -= $soLuongLayTuLo;
            }

            // Cập nhật TonKho
            /** @var TonKho|null $tonKho */
            $tonKho = TonKho::query()
                ->where('cua_hang_id', $cuaHangId)
                ->where('san_pham_id', $sanPhamId)
                ->lockForUpdate()
                ->first();

            $tonSauXuat = 0.0;
            if ($tonKho) {
                $tonKho->so_luong_ton = max(0.0, round(((float) $tonKho->so_luong_ton) - $soLuong, 2));
                $tonKho->save();
                $tonSauXuat = (float) $tonKho->so_luong_ton;
            }

            // Ghi nhận Thẻ Kho
            TheKho::create([
                'cua_hang_id' => $cuaHangId,
                'san_pham_id' => $sanPhamId,
                'lo_hang_id' => $loDaTru[0]['lo_hang_id'] ?? null,
                'loai_thao_tac' => $loaiThaoTac,
                'so_luong_thay_doi' => -$soLuong,
                'ton_sau_thay_doi' => $tonSauXuat,
                'ma_chung_tu' => $maChungTu ?? $this->sinhMaPhieu('XK'),
                'nguoi_thuc_hien_id' => $nguoiThucHienId,
                'ghi_chu' => $ghiChu ?? "Xuất kho FEFO {$loaiThaoTac}",
            ]);

            return [
                'so_luong_da_xuat' => $soLuong,
                'lo_da_tru' => $loDaTru,
                'ton_sau_xuat' => $tonSauXuat,
            ];
        });
    }

    /**
     * Xuất hủy lô hàng hư hỏng hoặc quá hạn sử dụng.
     */
    public function xuatHuyHang(
        int $cuaHangId,
        int $loHangId,
        float $soLuong,
        int $nguoiThucHienId,
        string $lyDo,
        ?string $ghiChu = null
    ): PhieuXuat {
        return DB::transaction(function () use (
            $cuaHangId,
            $loHangId,
            $soLuong,
            $nguoiThucHienId,
            $lyDo,
            $ghiChu
        ): PhieuXuat {
            /** @var LoHang $loHang */
            $loHang = LoHang::query()
                ->where('id', $loHangId)
                ->where('cua_hang_id', $cuaHangId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($soLuong > (float) $loHang->so_luong_con_lai) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Số lượng xuất hủy (%.2f) vượt quá số lượng tồn của lô %s (%.2f).',
                        $soLuong,
                        $loHang->ma_lo,
                        $loHang->so_luong_con_lai
                    )
                );
            }

            $sanPhamId = (int) $loHang->san_pham_id;
            $maPhieu = $this->sinhMaPhieu('XH');
            $giaXuat = (float) $loHang->gia_nhap;

            // 1. Tạo Phiếu Xuất Hủy
            $phieuXuat = PhieuXuat::create([
                'ma_phieu' => $maPhieu,
                'cua_hang_id' => $cuaHangId,
                'loai_xuat' => PhieuXuat::LOAI_XUAT_HUY,
                'nguoi_tao_id' => $nguoiThucHienId,
                'nguoi_duyet_id' => $nguoiThucHienId,
                'trang_thai' => PhieuXuat::TRANG_THAI_DA_XUAT,
                'tong_so_luong' => $soLuong,
                'tong_gia_tri' => $soLuong * $giaXuat,
                'ly_do' => $lyDo,
                'ghi_chu' => $ghiChu,
                'xuat_luc' => now(),
            ]);

            // 2. Chi tiết phiếu xuất
            $chiTietPhieuXuat = ChiTietPhieuXuat::create([
                'phieu_xuat_id' => $phieuXuat->id,
                'san_pham_id' => $sanPhamId,
                'so_luong' => $soLuong,
                'gia_xuat' => $giaXuat,
                'thanh_tien' => $soLuong * $giaXuat,
                'ghi_chu' => "Xuất hủy: {$lyDo}",
            ]);

            ChiTietXuatKho::create([
                'chi_tiet_phieu_xuat_id' => $chiTietPhieuXuat->id,
                'lo_hang_id' => $loHang->id,
                'so_luong' => $soLuong,
                'gia_xuat' => $giaXuat,
            ]);

            // 3. Trừ tồn lô hàng
            $loHang->so_luong_con_lai = round(((float) $loHang->so_luong_con_lai) - $soLuong, 2);
            if ($loHang->so_luong_con_lai <= 0.0001) {
                $loHang->so_luong_con_lai = 0;
            }
            $loHang->save();

            // 4. Trừ TonKho
            /** @var TonKho|null $tonKho */
            $tonKho = TonKho::query()
                ->where('cua_hang_id', $cuaHangId)
                ->where('san_pham_id', $sanPhamId)
                ->lockForUpdate()
                ->first();

            $tonSauThayDoi = 0.0;
            if ($tonKho) {
                $tonKho->so_luong_ton = max(0.0, round(((float) $tonKho->so_luong_ton) - $soLuong, 2));
                $tonKho->save();
                $tonSauThayDoi = (float) $tonKho->so_luong_ton;
            }

            // 5. Ghi Thẻ Kho
            TheKho::create([
                'cua_hang_id' => $cuaHangId,
                'san_pham_id' => $sanPhamId,
                'lo_hang_id' => $loHang->id,
                'loai_thao_tac' => 'xuat_huy',
                'so_luong_thay_doi' => -$soLuong,
                'ton_sau_thay_doi' => $tonSauThayDoi,
                'ma_chung_tu' => $maPhieu,
                'nguoi_thuc_hien_id' => $nguoiThucHienId,
                'ghi_chu' => "Xuất hủy hàng hỏng: {$lyDo}",
            ]);

            return $phieuXuat->load(['chiTiets.chiTietXuatKhos.loHang']);
        });
    }

    /**
     * Điều chuyển hàng hóa nội bộ giữa 2 chi nhánh theo nguyên tắc FEFO.
     *
     * @param  array<int, array{san_pham_id: int, so_luong: float}>  $items
     *
     * @throws InsufficientInventoryException
     */
    public function dieuChuyenKhoChiNhanh(
        int $cuaHangXuatId,
        int $cuaHangNhanId,
        array $items,
        int $nguoiThucHienId,
        ?string $ghiChu = null,
        ?int $nguoiGiaoHangId = null,
        ?string $loaiPhuongTien = 'xe_tai',
        ?string $bienSoXe = null
    ): PhieuXuat {
        if ($cuaHangXuatId === $cuaHangNhanId) {
            throw new \InvalidArgumentException('Chi nhánh xuất và chi nhánh nhận không được trùng nhau.');
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('Danh sách hàng hóa điều chuyển không được để trống.');
        }

        return DB::transaction(function () use (
            $cuaHangXuatId,
            $cuaHangNhanId,
            $items,
            $nguoiThucHienId,
            $ghiChu,
            $nguoiGiaoHangId,
            $loaiPhuongTien,
            $bienSoXe
        ): PhieuXuat {
            // 1. Kiểm tra tồn khả dụng FEFO trước khi chuyển
            foreach ($items as $item) {
                $sanPhamId = (int) $item['san_pham_id'];
                $soLuongYeuCau = (float) $item['so_luong'];
                $kiemTra = $this->kiemTraKhaDung($cuaHangXuatId, $sanPhamId, $soLuongYeuCau, true);

                if (! $kiemTra['kha_dung']) {
                    $sanPham = SanPham::find($sanPhamId);
                    $sanPhamTen = $sanPham?->ten_san_pham ?? "Sản phẩm #{$sanPhamId}";
                    throw new InsufficientInventoryException(
                        sanPhamId: $sanPhamId,
                        sanPhamTen: $sanPhamTen,
                        soLuongYeuCau: $soLuongYeuCau,
                        soLuongKhaDung: $kiemTra['tong_con_han']
                    );
                }
            }

            $maPhieu = $this->sinhMaPhieu('DC');

            // 2. Tạo chứng từ Phiếu Xuất Điều Chuyển
            $phieuXuat = PhieuXuat::create([
                'ma_phieu' => $maPhieu,
                'cua_hang_id' => $cuaHangXuatId,
                'cua_hang_nhan_id' => $cuaHangNhanId,
                'loai_xuat' => PhieuXuat::LOAI_XUAT_CHUYEN,
                'nguoi_tao_id' => $nguoiThucHienId,
                'nguoi_duyet_id' => $nguoiThucHienId,
                'nguoi_giao_hang_id' => $nguoiGiaoHangId,
                'loai_phuong_tien' => $loaiPhuongTien ?: 'xe_tai',
                'bien_so_xe' => $bienSoXe,
                'trang_thai' => PhieuXuat::TRANG_THAI_DA_XUAT,
                'tong_so_luong' => 0,
                'tong_gia_tri' => 0,
                'ly_do' => 'Điều chuyển hàng hóa nội bộ giữa các chi nhánh',
                'ghi_chu' => $ghiChu,
                'xuat_luc' => now(),
            ]);

            if ($nguoiGiaoHangId) {
                User::where('id', $nguoiGiaoHangId)->update(['trang_thai_giao' => 'dang_giao']);
            }

            $tongSoLuong = 0.0;
            $tongGiaTri = 0.0;

            // 3. Thực hiện xuất FEFO từ bên xuất & nhập vào bên nhận
            foreach ($items as $item) {
                $sanPhamId = (int) $item['san_pham_id'];
                $soLuongCanChuyen = (float) $item['so_luong'];
                $soLuongConLai = $soLuongCanChuyen;

                $loHangs = LoHang::query()
                    ->where('cua_hang_id', $cuaHangXuatId)
                    ->where('san_pham_id', $sanPhamId)
                    ->where('trang_thai', 1)
                    ->where('so_luong_con_lai', '>', 0)
                    ->where('ngay_het_han', '>=', Carbon::today()->toDateString())
                    ->orderBy('ngay_het_han', 'asc')
                    ->lockForUpdate()
                    ->get();

                // Tạo dòng ChiTietPhieuXuat
                $sanPham = SanPham::find($sanPhamId);
                $giaXuatTam = (float) ($loHangs->first()?->gia_nhap ?? $sanPham?->gia_ban ?? 0);

                $chiTietPhieuXuat = ChiTietPhieuXuat::create([
                    'phieu_xuat_id' => $phieuXuat->id,
                    'san_pham_id' => $sanPhamId,
                    'so_luong' => $soLuongCanChuyen,
                    'gia_xuat' => $giaXuatTam,
                    'thanh_tien' => $soLuongCanChuyen * $giaXuatTam,
                    'ghi_chu' => "Điều chuyển sang chi nhánh #{$cuaHangNhanId}",
                ]);

                foreach ($loHangs as $sourceLot) {
                    if ($soLuongConLai <= 0.0001) {
                        break;
                    }

                    $khaDung = (float) $sourceLot->so_luong_con_lai;
                    $allocated = min($khaDung, $soLuongConLai);
                    $giaVonLo = (float) $sourceLot->gia_nhap;

                    // 3.1. Trừ tồn lô nguồn (FEFO)
                    $sourceLot->so_luong_con_lai = round($khaDung - $allocated, 2);
                    if ($sourceLot->so_luong_con_lai <= 0.0001) {
                        $sourceLot->so_luong_con_lai = 0;
                    }
                    $sourceLot->save();

                    // 3.2. Ghi nhận ChiTietXuatKho
                    ChiTietXuatKho::create([
                        'chi_tiet_phieu_xuat_id' => $chiTietPhieuXuat->id,
                        'lo_hang_id' => $sourceLot->id,
                        'so_luong' => $allocated,
                        'gia_xuat' => $giaVonLo,
                    ]);

                    // 3.3. Trừ TonKho bên xuất
                    /** @var TonKho|null $sourceTonKho */
                    $sourceTonKho = TonKho::query()
                        ->where('cua_hang_id', $cuaHangXuatId)
                        ->where('san_pham_id', $sanPhamId)
                        ->lockForUpdate()
                        ->first();

                    $sourceTonSau = 0.0;
                    if ($sourceTonKho) {
                        $sourceTonKho->so_luong_ton = max(0.0, round(((float) $sourceTonKho->so_luong_ton) - $allocated, 2));
                        $sourceTonKho->save();
                        $sourceTonSau = (float) $sourceTonKho->so_luong_ton;
                    }

                    // 3.4. Thẻ kho bên xuất (xuat_chuyen)
                    TheKho::create([
                        'cua_hang_id' => $cuaHangXuatId,
                        'san_pham_id' => $sanPhamId,
                        'lo_hang_id' => $sourceLot->id,
                        'loai_thao_tac' => 'xuat_chuyen',
                        'so_luong_thay_doi' => -$allocated,
                        'ton_sau_thay_doi' => $sourceTonSau,
                        'ma_chung_tu' => $maPhieu,
                        'nguoi_thuc_hien_id' => $nguoiThucHienId,
                        'ghi_chu' => "Xuất điều chuyển sang Chi nhánh #{$cuaHangNhanId}",
                    ]);

                    // 3.5. Đồng bộ Lô Hàng sang bên nhận (Bảo toàn hạn sử dụng & tiêu chuẩn ATTP)
                    $destLot = LoHang::query()
                        ->where('cua_hang_id', $cuaHangNhanId)
                        ->where('san_pham_id', $sanPhamId)
                        ->where('ngay_het_han', $sourceLot->ngay_het_han)
                        ->where('gia_nhap', $sourceLot->gia_nhap)
                        ->where('trang_thai', 1)
                        ->first();

                    if ($destLot) {
                        $destLot->so_luong_con_lai = round(((float) $destLot->so_luong_con_lai) + $allocated, 2);
                        $destLot->so_luong_nhap = round(((float) $destLot->so_luong_nhap) + $allocated, 2);
                        $destLot->save();
                    } else {
                        $destMaLo = $sourceLot->ma_lo.'-C'.$cuaHangNhanId;
                        if (LoHang::where('ma_lo', $destMaLo)->exists()) {
                            $destMaLo = Str::limit($sourceLot->ma_lo, 38, '').'-C'.$cuaHangNhanId.'-'.strtoupper(Str::random(4));
                        }

                        $destLot = LoHang::create([
                            'ma_lo' => $destMaLo,
                            'san_pham_id' => $sanPhamId,
                            'cua_hang_id' => $cuaHangNhanId,
                            'nha_cung_cap_id' => $sourceLot->nha_cung_cap_id,
                            'ngay_san_xuat' => $sourceLot->ngay_san_xuat,
                            'ngay_het_han' => $sourceLot->ngay_het_han,
                            'so_luong_nhap' => $allocated,
                            'so_luong_con_lai' => $allocated,
                            'gia_nhap' => $sourceLot->gia_nhap,
                            'trang_thai' => 1,
                        ]);
                    }

                    // 3.6. Tăng TonKho bên nhận
                    /** @var TonKho $destTonKho */
                    $destTonKho = TonKho::firstOrCreate(
                        ['cua_hang_id' => $cuaHangNhanId, 'san_pham_id' => $sanPhamId],
                        ['so_luong_ton' => 0, 'so_luong_tam_giu' => 0, 'nguong_canh_bao_thap' => 10]
                    );
                    $destTonKho->so_luong_ton = round(((float) $destTonKho->so_luong_ton) + $allocated, 2);
                    $destTonKho->save();
                    $destTonSau = (float) $destTonKho->so_luong_ton;

                    // 3.7. Thẻ kho bên nhận (nhap_chuyen)
                    TheKho::create([
                        'cua_hang_id' => $cuaHangNhanId,
                        'san_pham_id' => $sanPhamId,
                        'lo_hang_id' => $destLot->id,
                        'loai_thao_tac' => 'nhap_chuyen',
                        'so_luong_thay_doi' => $allocated,
                        'ton_sau_thay_doi' => $destTonSau,
                        'ma_chung_tu' => $maPhieu,
                        'nguoi_thuc_hien_id' => $nguoiThucHienId,
                        'ghi_chu' => "Nhận điều chuyển từ Chi nhánh #{$cuaHangXuatId}",
                    ]);

                    $soLuongConLai -= $allocated;
                    $tongGiaTri += ($allocated * $giaVonLo);
                }

                $tongSoLuong += $soLuongCanChuyen;
            }

            // 4. Cập nhật tổng số lượng & tổng giá trị chứng từ
            $phieuXuat->tong_so_luong = $tongSoLuong;
            $phieuXuat->tong_gia_tri = $tongGiaTri;
            $phieuXuat->save();

            return $phieuXuat->load(['cuaHang', 'cuaHangNhan', 'chiTiets.sanPham', 'chiTiets.chiTietXuatKhos.loHang']);
        });
    }

    /**
     * Xác nhận nhập hàng điều chuyển vào kho chi nhánh nhận (Bước 2 của quy trình điều chuyển 2 bước).
     */
    public function xacNhanNhapChuyen(PhieuXuat $phieuXuat, int $nguoiNhanId): PhieuXuat
    {
        if ($phieuXuat->loai_xuat !== PhieuXuat::LOAI_XUAT_CHUYEN) {
            throw new \InvalidArgumentException('Phiếu này không phải là chứng từ điều chuyển chi nhánh.');
        }

        if ($phieuXuat->trang_thai === PhieuXuat::TRANG_THAI_DA_NHAN) {
            throw new \InvalidArgumentException('Phiếu điều chuyển này đã được xác nhận nhập kho trước đó.');
        }

        return DB::transaction(function () use ($phieuXuat, $nguoiNhanId) {
            $cuaHangNhanId = (int) $phieuXuat->cua_hang_nhan_id;
            $cuaHangXuatId = (int) $phieuXuat->cua_hang_id;
            $maPhieu = $phieuXuat->ma_phieu;

            $phieuXuat->loadMissing(['chiTiets.chiTietXuatKhos.loHang']);

            foreach ($phieuXuat->chiTiets as $ct) {
                $sanPhamId = (int) $ct->san_pham_id;

                foreach ($ct->chiTietXuatKhos as $ctxk) {
                    $sourceLot = $ctxk->loHang;
                    $allocated = (float) $ctxk->so_luong;

                    if (! $sourceLot || $allocated <= 0) {
                        continue;
                    }

                    // 1. Đồng bộ Lô Hàng sang bên nhận (Bảo toàn HSD & NSX)
                    $destLot = LoHang::query()
                        ->where('cua_hang_id', $cuaHangNhanId)
                        ->where('san_pham_id', $sanPhamId)
                        ->where('ngay_het_han', $sourceLot->ngay_het_han)
                        ->where('gia_nhap', $sourceLot->gia_nhap)
                        ->where('trang_thai', 1)
                        ->first();

                    if ($destLot) {
                        $destLot->so_luong_con_lai = round(((float) $destLot->so_luong_con_lai) + $allocated, 2);
                        $destLot->so_luong_nhap = round(((float) $destLot->so_luong_nhap) + $allocated, 2);
                        $destLot->save();
                    } else {
                        $destMaLo = $sourceLot->ma_lo.'-C'.$cuaHangNhanId;
                        if (LoHang::where('ma_lo', $destMaLo)->exists()) {
                            $destMaLo = Str::limit($sourceLot->ma_lo, 38, '').'-C'.$cuaHangNhanId.'-'.strtoupper(Str::random(4));
                        }

                        $destLot = LoHang::create([
                            'ma_lo' => $destMaLo,
                            'san_pham_id' => $sanPhamId,
                            'cua_hang_id' => $cuaHangNhanId,
                            'nha_cung_cap_id' => $sourceLot->nha_cung_cap_id,
                            'ngay_san_xuat' => $sourceLot->ngay_san_xuat,
                            'ngay_het_han' => $sourceLot->ngay_het_han,
                            'so_luong_nhap' => $allocated,
                            'so_luong_con_lai' => $allocated,
                            'gia_nhap' => $sourceLot->gia_nhap,
                            'trang_thai' => 1,
                        ]);
                    }

                    // 2. Tăng TonKho bên nhận
                    /** @var TonKho $destTonKho */
                    $destTonKho = TonKho::firstOrCreate(
                        ['cua_hang_id' => $cuaHangNhanId, 'san_pham_id' => $sanPhamId],
                        ['so_luong_ton' => 0, 'so_luong_tam_giu' => 0, 'nguong_canh_bao_thap' => 10]
                    );
                    $destTonKho->so_luong_ton = round(((float) $destTonKho->so_luong_ton) + $allocated, 2);
                    $destTonKho->save();
                    $destTonSau = (float) $destTonKho->so_luong_ton;

                    // 3. Thẻ kho bên nhận (nhap_chuyen)
                    TheKho::create([
                        'cua_hang_id' => $cuaHangNhanId,
                        'san_pham_id' => $sanPhamId,
                        'lo_hang_id' => $destLot->id,
                        'loai_thao_tac' => 'nhap_chuyen',
                        'so_luong_thay_doi' => $allocated,
                        'ton_sau_thay_doi' => $destTonSau,
                        'ma_chung_tu' => $maPhieu,
                        'nguoi_thuc_hien_id' => $nguoiNhanId,
                        'ghi_chu' => "Xác nhận nhận điều chuyển từ Chi nhánh #{$cuaHangXuatId}",
                    ]);
                }
            }

            $phieuXuat->trang_thai = PhieuXuat::TRANG_THAI_DA_NHAN;
            $phieuXuat->nguoi_duyet_id = $nguoiNhanId;
            $phieuXuat->save();

            if ($phieuXuat->nguoi_giao_hang_id) {
                User::where('id', $phieuXuat->nguoi_giao_hang_id)->update(['trang_thai_giao' => 'san_sang']);
            }

            return $phieuXuat->fresh(['cuaHang', 'cuaHangNhan', 'chiTiets.sanPham']);
        });
    }

    /**
     * Lấy danh sách các lô hàng cận hạn sử dụng hoặc đã quá hạn tại chi nhánh.
     *
     * @return Collection<int, LoHang>
     */
    public function layDanhSachCanhBaoCanDate(int $cuaHangId, int $soNgay = 3): Collection
    {
        $nguongNgay = Carbon::today()->addDays($soNgay)->toDateString();

        return LoHang::query()
            ->with(['sanPham.danhMuc', 'cuaHang'])
            ->where('cua_hang_id', $cuaHangId)
            ->where('trang_thai', 1)
            ->where('so_luong_con_lai', '>', 0)
            ->where('ngay_het_han', '<=', $nguongNgay)
            ->orderBy('ngay_het_han', 'asc')
            ->get();
    }

    /**
     * Sinh mã phiếu xuất chứng từ chuẩn: [TIENTO]-YYYYMMDD-XXXX.
     */
    public function sinhMaPhieu(string $tienTo = 'PX'): string
    {
        $dateStr = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return sprintf('%s-%s-%s', $tienTo, $dateStr, $random);
    }
}
