# CHÚ THÍCH HỆ THỐNG CÁC FILE ĐÃ TẠO (FILENOTE)
**Dự án:** Fresh Food - Hệ thống Chuỗi Cửa hàng Thực phẩm Tươi sạch  
**Framework:** Laravel 12 + Inertia.js v2 + Vue 3 + Tailwind CSS  
**Cập nhật lần cuối:** 01/11/2026

---

## MỤC LỤC
1. [Giai đoạn 1: Cơ sở Dữ liệu, Models, Migrations & Seeders](#1-giai-đoạn-1-cơ-sở-dữ-liệu-models-migrations--seeders)
2. [Giai đoạn 2: Phân quyền, Middleware & Routing](#2-giai-đoạn-2-phân-quyền-middleware--routing)
3. [Giai đoạn 3: Storefront Bán lẻ & Trải nghiệm Khách hàng](#3-giai-đoạn-3-storefront-bán-lẻ--trải-nghiệm-khách-hàng)
4. [Kiểm thử Tự động (Tests)](#4-kiểm-thử-tự-động-tests)
5. [Tài liệu Kế hoạch](#5-tài-liệu-kế-hoạch)
6. [Tài nguyên Thương hiệu & Hình ảnh (Brand Assets)](#6-tài-nguyên-thương-hiệu--hình-ảnh-brand-assets)

---

## 1. GIAI ĐOẠN 1: CƠ SỞ DỮ LIỆU, MODELS, MIGRATIONS & SEEDERS

### 1.1. Toàn bộ 27 Eloquent Models (`app/Models/`)
| STT | File Model | Bảng CSDL | Vai trò / Chức năng chính |
| :---: | :--- | :--- | :--- |
| 1 | [User.php](file:///d:/FreshFood/app/Models/User.php) | `users` | Quản lý người dùng, phân quyền 4 vai trò (`admin`, `quan_ly`, `nhan_vien`, `khach_hang`), điểm tích lũy `loyalty_points`, liên kết chi nhánh `store_id`. |
| 2 | [CuaHang.php](file:///d:/FreshFood/app/Models/CuaHang.php) | `cua_hang` | Quản lý 4 chi nhánh siêu thị/cửa hàng trong chuỗi (địa chỉ, tọa độ GPS, hotline, giờ mở/đóng cửa). Hỗ trợ hàm `layIdCuaHangHienTai()` xác định chi nhánh từ Request/Session/Cookie. |
| 3 | [DanhMuc.php](file:///d:/FreshFood/app/Models/DanhMuc.php) | `danh_muc` | Danh mục sản phẩm đa cấp (Rau củ, Thịt trứng, Thủy hải sản, Trái cây,...), hỗ trợ quan hệ cha-con (`danhMucCha`, `danhMucCon`). |
| 4 | [SanPham.php](file:///d:/FreshFood/app/Models/SanPham.php) | `san_pham` | Thông tin thực phẩm: Barcode, tên, giá bán, giá giảm, điều kiện bảo quản (mát/đông lạnh), hạn sử dụng (ngày), cờ hàng tươi sống `la_tuoi_song`. Phương thức `apDungGiaCuaHang($storeId)` ưu tiên giá theo từng chi nhánh. |
| 5 | [HinhAnhSanPham.php](file:///d:/FreshFood/app/Models/HinhAnhSanPham.php) | `hinh_anh_san_pham` | Bộ sưu tập ảnh sản phẩm, ảnh chụp đóng gói, thứ tự hiển thị. |
| 6 | [GiaSanPhamCuaHang.php](file:///d:/FreshFood/app/Models/GiaSanPhamCuaHang.php) | `gia_san_pham_cua_hang` | Bảng giá đặc thù theo từng chi nhánh/vùng miền (cấu hình giá bán riêng, giá khuyến mãi riêng theo cửa hàng). |
| 7 | [LoHang.php](file:///d:/FreshFood/app/Models/LoHang.php) | `lo_hang` | **Trọng tâm thực phẩm:** Quản lý số lô hàng, ngày sản xuất, ngày hết hạn, mã chứng nhận kiểm định ATTP/VietGAP. |
| 8 | [TonKho.php](file:///d:/FreshFood/app/Models/TonKho.php) | `ton_kho` | Quản lý số lượng tồn kho thực tế của từng sản phẩm tại từng chi nhánh theo từng lô hàng cụ thể. |
| 9 | [TheKho.php](file:///d:/FreshFood/app/Models/TheKho.php) | `the_kho` | Sổ thẻ kho theo dõi mọi biến động Nhập - Xuất - Kiểm kê - Hủy hàng theo thời gian thực. |
| 10 | [NhaCungCap.php](file:///d:/FreshFood/app/Models/NhaCungCap.php) | `nha_cung_cap` | Đối tác cung ứng trang trại rau hữu cơ, hợp tác xã chăn nuôi, nhà nhập khẩu thịt cá. |
| 11 | [PhieuNhap.php](file:///d:/FreshFood/app/Models/PhieuNhap.php) | `phieu_nhap` | Phiếu nhập kho từ Nhà cung cấp về chi nhánh, quản lý trạng thái duyệt và tổng tiền nhập. |
| 12 | [ChiTietPhieuNhap.php](file:///d:/FreshFood/app/Models/ChiTietPhieuNhap.php) | `chi_tiet_phieu_nhap` | Chi tiết số lượng nhập, đơn giá vốn, quy đổi lô hàng của từng sản phẩm trong phiếu nhập. |
| 13 | [PhieuXuat.php](file:///d:/FreshFood/app/Models/PhieuXuat.php) | `phieu_xuat` | Phiếu xuất kho theo các mục đích: Xuất bán đơn hàng, điều chuyển chi nhánh, xuất hủy hàng hỏng/hết date. |
| 14 | [ChiTietPhieuXuat.php](file:///d:/FreshFood/app/Models/ChiTietPhieuXuat.php) | `chi_tiet_phieu_xuat` | Chi tiết mặt hàng xuất kho trên chứng từ. |
| 15 | [ChiTietXuatKho.php](file:///d:/FreshFood/app/Models/ChiTietXuatKho.php) | `chi_tiet_xuat_kho` | Phân bổ xuất kho đến cấp độ **từng Lô hàng (LoHang)** để phục vụ nguyên tắc xuất kho FEFO (First Expired, First Out). |
| 16 | [PhieuKiemKe.php](file:///d:/FreshFood/app/Models/PhieuKiemKe.php) | `phieu_kiem_ke` | Biên bản kiểm kê định kỳ/hàng ngày tại kho/quầy kệ thực phẩm tươi sống. |
| 17 | [ChiTietKiemKe.php](file:///d:/FreshFood/app/Models/ChiTietKiemKe.php) | `chi_tiet_kiem_ke` | So sánh số lượng sổ sách vs thực tế kiểm đếm, ghi nhận hao hụt, mất mát, dập nát. |
| 18 | [DonHang.php](file:///d:/FreshFood/app/Models/DonHang.php) | `don_hang` | Đơn hàng mua sắm (Online & POS): Thông tin khách, cửa hàng xử lý, tổng tiền hàng, phí ship, voucher, điểm tích lũy, trạng thái giao/thanh toán. |
| 19 | [ChiTietDonHang.php](file:///d:/FreshFood/app/Models/ChiTietDonHang.php) | `chi_tiet_don_hang` | Từng dòng sản phẩm trong đơn: số lượng, đơn giá, giá vốn dự tính, thành tiền. |
| 20 | [GioHang.php](file:///d:/FreshFood/app/Models/GioHang.php) | `gio_hang` | Giỏ hàng lưu trữ trên máy chủ, hỗ trợ cả khách vãng lai qua `session_id`, thành viên qua `user_id` và phân loại theo `cua_hang_id`. |
| 21 | [DiaChiGiaoHang.php](file:///d:/FreshFood/app/Models/DiaChiGiaoHang.php) | `dia_chi_giao_hang` | Sổ địa chỉ giao hàng của người dùng (tên, SĐT nhận, địa chỉ chi tiết, cờ mặc định). |
| 22 | [MaGiamGia.php](file:///d:/FreshFood/app/Models/MaGiamGia.php) | `ma_giam_gia` | Chương trình khuyến mãi, voucher giảm giá theo số tiền hoặc phần trăm, đơn hàng tối thiểu, lượt sử dụng. |
| 23 | [PhieuDoiTra.php](file:///d:/FreshFood/app/Models/PhieuDoiTra.php) | `phieu_doi_tra` | **Chính sách 24h:** Tiếp nhận & xử lý yêu cầu đổi/trả thực phẩm dập nát, hỏng hóc hoặc giao sai. |
| 24 | [ChiTietDoiTra.php](file:///d:/FreshFood/app/Models/ChiTietDoiTra.php) | `chi_tiet_doi_tra` | Danh sách mặt hàng đổi trả, số lượng hoàn trả, tình trạng hàng hóa (hư hỏng hủy hay thu hồi). |
| 25 | [LichSuTichDiem.php](file:///d:/FreshFood/app/Models/LichSuTichDiem.php) | `lich_su_tich_diem` | Nhật ký cộng điểm khi mua hàng thành công và trừ điểm khi dùng điểm thanh toán đơn hàng. |
| 26 | [DanhGiaSanPham.php](file:///d:/FreshFood/app/Models/DanhGiaSanPham.php) | `danh_gia_san_pham` | Đánh giá sao (1-5★), nhận xét độ tươi ngon và phản hồi của người mua. |
| 27 | [ThongBao.php](file:///d:/FreshFood/app/Models/ThongBao.php) | `thong_bao` | Thông báo đơn hàng, biến động điểm thưởng, nhắc nhở lô hàng cận date cho quản lý. |

---

### 1.2. Migrations Nâng cấp Bổ sung (`database/migrations/`)
- [2026_10_31_163234_update_gio_hang_table_support_guest_and_session.php](file:///d:/FreshFood/database/migrations/2026_10_31_163234_update_gio_hang_table_support_guest_and_session.php):
  - Cho phép `user_id` nhận giá trị `NULL`.
  - Bổ sung cột `session_id` (string 255) phục vụ lưu giỏ hàng cho khách vãng lai chưa đăng nhập.
  - Bổ sung cột `cua_hang_id` (foreign key `cua_hang.id`) để khóa giỏ hàng theo một chi nhánh duy nhất.

---

### 1.3. Factories Phục vụ Kiểm thử (`database/factories/`)
- [CuaHangFactory.php](file:///d:/FreshFood/database/factories/CuaHangFactory.php): Tạo dữ liệu chi nhánh cửa hàng tươi sạch (tên, địa chỉ, số điện thoại, trạng thái).
- [SanPhamFactory.php](file:///d:/FreshFood/database/factories/SanPhamFactory.php): Tạo dữ liệu mẫu sản phẩm thực phẩm tươi sống có `danh_muc_id`, điều kiện bảo quản mát/đông lạnh và giá bán.

---

### 1.4. Bộ Seeders Dữ liệu Thực tế (`database/seeders/`)
- [CuaHangSeeder.php](file:///d:/FreshFood/database/seeders/CuaHangSeeder.php): Khởi tạo 4 chi nhánh Fresh Food tiêu chuẩn tại Quận 1, Cầu Giấy (Hà Nội), Hải Châu (Đà Nẵng) và Quận 7 (TP.HCM).
- [DanhMucSeeder.php](file:///d:/FreshFood/database/seeders/DanhMucSeeder.php): 6 danh mục lớn và 18 danh mục con chuyên sâu về rau củ VietGAP, thịt sinh học, hải sản tươi sống, trái cây nhập khẩu.
- [NhaCungCapSeeder.php](file:///d:/FreshFood/database/seeders/NhaCungCapSeeder.php): 6 nhà cung cấp nông trại uy tín (Dalat Hasfarm, Ba Huân, CP Foods, Vissan, VinEco).
- [SanPhamSeeder.php](file:///d:/FreshFood/database/seeders/SanPhamSeeder.php): 24 mặt hàng nông sản, thịt cá tươi sống thực tế có hình ảnh Unsplash chất lượng cao, hạn dùng và quy chuẩn bảo quản.
- [LoHangSeeder.php](file:///d:/FreshFood/database/seeders/LoHangSeeder.php): Tạo các lô hàng với ngày thu hoạch/đóng gói, ngày hết hạn và khởi tạo tồn kho `TonKho` cho từng chi nhánh.
- [MaGiamGiaSeeder.php](file:///d:/FreshFood/database/seeders/MaGiamGiaSeeder.php): Các mã voucher kích cầu (`FRESHWELCOME`, `RAUSACH15`, `FREESHIP300K`).
- [UserSeeder.php](file:///d:/FreshFood/database/seeders/UserSeeder.php): Khởi tạo tài khoản mẫu cho từng phân quyền (`admin@freshfood.vn`, `manager.q1@freshfood.vn`, `staff.pos@freshfood.vn`, `customer@freshfood.vn`).
- [DatabaseSeeder.php](file:///d:/FreshFood/database/seeders/DatabaseSeeder.php): Điều phối trình tự chạy toàn bộ các Seeder trên.

---

## 2. GIAI ĐOẠN 2: PHÂN QUYỀN, MIDDLEWARE & ROUTING

- [CheckRole.php](file:///d:/FreshFood/app/Http/Middleware/CheckRole.php): Middleware kiểm tra vai trò người dùng đa cấp (`admin`, `quan_ly`, `nhan_vien`, `khach_hang`), tự động từ chối truy cập (403) nếu không đủ quyền hạn.
- [HandleInertiaRequests.php](file:///d:/FreshFood/app/Http/Middleware/HandleInertiaRequests.php): Chia sẻ dữ liệu toàn cục cho Inertia: danh sách chi nhánh hoạt động, chi nhánh hiện tại, CSRF Token, giỏ hàng CSDL (`serverCart`).
- [bootstrap/app.php](file:///d:/FreshFood/bootstrap/app.php): Đăng ký alias middleware `role` và ngoại lệ CSRF token cho các endpoint giỏ hàng `/api/cart/*`.
- [routes/client.php](file:///d:/FreshFood/routes/client.php): Tuyến đường Storefront công khai (Trang chủ, Chọn chi nhánh, Sản phẩm, Tìm kiếm gợi ý, Chi tiết, Giỏ hàng & API Giỏ hàng, Thanh toán, Tra cứu) và Phân hệ Khách hàng cá nhân (`/tai-khoan/*`).
- [routes/admin.php](file:///d:/FreshFood/routes/admin.php): Tuyến đường Ban Quản trị hệ thống (`/admin/*`) quản lý chuỗi, cấu hình giá, nhà cung cấp, báo cáo.
- [routes/store.php](file:///d:/FreshFood/routes/store.php): Tuyến đường Vận hành Cửa hàng & POS bán lẻ (`/store/*`) quản lý xuất nhập tồn kho, quầy thu ngân.
- [routes/web.php](file:///d:/FreshFood/routes/web.php): Điểm tập trung include các file route chức năng chuyên biệt.

---

## 3. GIAI ĐOẠN 3: STOREFRONT BÁN LẺ & TRẢI NGHIỆM KHÁCH HÀNG

### 3.1. Controllers Bán lẻ (`app/Http/Controllers/Client/`)
- [HomeController.php](file:///d:/FreshFood/app/Http/Controllers/Client/HomeController.php):
  - `index`: Cung cấp dữ liệu Trang chủ theo chi nhánh đã chọn (Banner, Flash Sale đếm ngược, Hàng tươi hôm nay, Danh mục nổi bật, Voucher).
  - `selectStore`: Xử lý đổi chi nhánh siêu thị, lưu Session & Cookie 30 ngày. Hỗ trợ tham số `clear_cart: true` tự động xóa sạch toàn bộ giỏ hàng cũ trong database khi khách hàng xác nhận đổi chi nhánh.
- [ProductController.php](file:///d:/FreshFood/app/Http/Controllers/Client/ProductController.php):
  - `index`: Tìm kiếm sản phẩm theo từ khóa (live search), lọc theo danh mục, khoảng giá, điều kiện bảo quản mát/lạnh, cờ hàng tươi sống, phân trang.
  - `searchSuggestions`: API gợi ý tìm kiếm tức thì theo từng ký tự gõ vào, trả về hình ảnh, tên và giá sản phẩm.
  - `show`: Chi tiết sản phẩm, thư viện ảnh, bảng giá riêng của chi nhánh, tình trạng tồn kho tất cả chi nhánh trong chuỗi, sản phẩm gợi ý cùng loại.
  - `category`: Hiển thị danh mục chuyên sâu kèm subcategories.
- [CartController.php](file:///d:/FreshFood/app/Http/Controllers/Client/CartController.php):
  - `index`: Màn hình giỏ hàng, nạp giỏ hàng đã lưu trong database của khách vãng lai hoặc thành viên.
  - `add`: Thêm sản phẩm vào giỏ hàng lưu thẳng vào database table `gio_hang`. Hỗ trợ `reset_cart` và khóa giỏ hàng thuộc về 1 chi nhánh duy nhất.
  - `update`: Cập nhật số lượng sản phẩm trong database.
  - `remove`: Xóa một sản phẩm khỏi database.
  - `clear`: Xóa sạch toàn bộ giỏ hàng trong database.
  - `checkCoupon`: API xác thực mã giảm giá, kiểm tra thời hạn, số lượt và giá trị đơn hàng tối thiểu.
  - `sync`: API đồng bộ giỏ hàng từ trình duyệt lên cơ sở dữ liệu.
  - `refreshPrices`: API lấy lại bảng giá cập nhật theo chi nhánh mới từ table `gia_san_pham_cua_hang`.
- [CheckoutController.php](file:///d:/FreshFood/app/Http/Controllers/Client/CheckoutController.php):
  - `index`: Màn hình thanh toán, lấy danh sách chi nhánh, sổ địa chỉ, điểm tích lũy.
  - `store`: Tạo đơn hàng trong DB Transaction, tính phí ship (miễn phí từ 300k), trừ voucher, trừ điểm tích lũy, tự động xóa giỏ hàng sau khi đặt thành công.
  - `success`: Màn hình hoàn tất đơn hàng, sinh mã QR VietQR Napas247 tự động.
  - `tracking`: Tra cứu đơn hàng công khai theo mã đơn và số điện thoại.
- [AccountController.php](file:///d:/FreshFood/app/Http/Controllers/Client/AccountController.php):
  - `index`: Tổng quan tài khoản cá nhân, thống kê đơn mua và số dư điểm.
  - `orders`: Danh sách đơn hàng có phân loại theo trạng thái (Chờ xác nhận, Đang giao, Đã giao,...).
  - `orderDetail`: Xem hóa đơn chi tiết của đơn hàng.
  - `addresses` / `storeAddress` / `deleteAddress`: Quản lý sổ địa chỉ giao hàng của khách.
  - `points`: Lịch sử cộng/trừ điểm và xem thẻ thành viên VIP.
  - `requestReturn`: Gửi yêu cầu đổi trả thực phẩm hỏng/dập nát trong 24h, tự sinh phiếu `PhieuDoiTra`.

---

### 3.2. Quản lý Trạng thái & Composables
- [useCart.ts](file:///d:/FreshFood/resources/js/composables/useCart.ts):
  - Composable toàn cục quản lý giỏ hàng phía Client đồng bộ 2 chiều với database table `gio_hang`.
  - Lưu trữ `localStorage` song song với các API `/api/cart/*`.
  - Kiểm soát quy tắc **1 đơn hàng = 1 chi nhánh**: Khi người dùng thêm sản phẩm thuộc chi nhánh khác hoặc đổi chi nhánh, tự động hiển thị hộp thoại xác nhận và xóa sạch triệt để cả trên client lẫn database nếu người dùng đồng ý.
  - Cơ chế `syncWithServer()` thông minh: ngăn chặn khôi phục nhầm các sản phẩm của chi nhánh cũ.
  - Đọc và đính kèm CSRF Token và Cookie `XSRF-TOKEN` tự động trong mọi request fetch.

---

### 3.3. Layouts & Components Dùng chung (`resources/js/components/storefront/` & `layouts/`)
- [StoreLayout.vue](file:///d:/FreshFood/resources/js/layouts/StoreLayout.vue): Layout chuẩn cho toàn bộ trang bán hàng Storefront (kết nối Header, Footer, Toast thông báo toàn cục, đồng bộ giỏ hàng).
- [StoreHeader.vue](file:///d:/FreshFood/resources/js/components/storefront/StoreHeader.vue):
  - Thanh thông báo hotline giao hàng 2h.
  - Bộ chọn 4 chi nhánh: Xác nhận và tự động làm mới giỏ hàng khi chuyển chi nhánh.
  - Thanh tìm kiếm thông minh: Tự động lọc theo từng ký tự (debounce 300ms) kèm dropdown kết quả gợi ý tức thì.
  - Mini-cart preview dropdown hiển thị số lượng và tổng tiền cập nhật thời gian thực.
  - Menu tài khoản và điều hướng phân quyền.
- [StoreFooter.vue](file:///d:/FreshFood/resources/js/components/storefront/StoreFooter.vue): Chân trang hiển thị chứng nhận ATTP, cam kết chất lượng 100%, danh sách 4 chi nhánh và chính sách đổi hàng.
- [ProductCard.vue](file:///d:/FreshFood/resources/js/components/storefront/ProductCard.vue): Thẻ sản phẩm chuẩn giao diện thực phẩm sạch: nhãn giảm giá đỏ, nhãn "Tươi mới hôm nay", nhãn bảo quản mát, giá bán theo Kg/Khay (ưu tiên giá chi nhánh), nút thêm nhanh vào giỏ.
- [AccountSidebar.vue](file:///d:/FreshFood/resources/js/components/storefront/AccountSidebar.vue): Thanh bên trái điều hướng người dùng trong cổng Portal Khách hàng.

---

### 3.4. Trang Giao diện Storefront (`resources/js/pages/client/`)
- [Home.vue](file:///d:/FreshFood/resources/js/pages/client/Home.vue): Trang chủ với Hero Banner, Lưới danh mục thực phẩm, Flash Sale đếm ngược, Hàng tươi mới sáng nay, Bộ sưu tập Mã giảm giá.
- [Products.vue](file:///d:/FreshFood/resources/js/pages/client/Products.vue): Trang danh sách sản phẩm với bộ lọc bên trái (Mức giá, danh mục, bảo quản, tươi sống), live search tự động cập nhật danh sách khi gõ ký tự, đổi kiểu xem Grid/List, thanh sắp xếp giá.
- [Category.vue](file:///d:/FreshFood/resources/js/pages/client/Category.vue): Trang danh mục ngành hàng kèm các chip danh mục con.
- [ProductDetail.vue](file:///d:/FreshFood/resources/js/pages/client/ProductDetail.vue): Trang chi tiết sản phẩm, thư viện ảnh phóng to, thông tin bảo quản, hạn dùng, tab so sánh giá và tình trạng tồn kho giữa các chi nhánh, nút "Chọn chi nhánh này" có xác nhận làm mới giỏ hàng an toàn, nút Mua ngay / Thêm vào giỏ.
- [Cart.vue](file:///d:/FreshFood/resources/js/pages/client/Cart.vue): Trang giỏ hàng, bảng số lượng, thông báo chi nhánh phục vụ, nút "Xóa tất cả giỏ hàng" có xác nhận, thanh Freeship progress bar mốc 300.000đ, kiểm tra mã voucher trực tiếp.
- [Checkout.vue](file:///d:/FreshFood/resources/js/pages/client/Checkout.vue): Trang đặt hàng & thanh toán: chọn Giao hàng / Nhận tại quầy, chọn khung giờ tươi sống, chọn sổ địa chỉ, dùng điểm thưởng, chọn hình thức COD / VietQR / MoMo / VNPay.
- [OrderSuccess.vue](file:///d:/FreshFood/resources/js/pages/client/OrderSuccess.vue): Trang xác nhận đặt hàng thành công, hiển thị timeline đơn hàng và render mã VietQR động để chuyển khoản.
- [OrderTracking.vue](file:///d:/FreshFood/resources/js/pages/client/OrderTracking.vue): Trang tra cứu công khai tiến trình đơn hàng bằng mã đơn và SĐT.

---

### 3.5. Cổng Tài khoản Khách hàng (`resources/js/pages/client/account/`)
- [Index.vue](file:///d:/FreshFood/resources/js/pages/client/account/Index.vue): Dashboard tổng quan chi tiêu, tổng số đơn hàng, điểm tích lũy và đơn hàng mới nhất.
- [Orders.vue](file:///d:/FreshFood/resources/js/pages/client/account/Orders.vue): Danh sách đơn hàng lọc theo các tab trạng thái.
- [OrderDetail.vue](file:///d:/FreshFood/resources/js/pages/client/account/OrderDetail.vue): Xem chi tiết đơn hàng, thông tin giao nhận và mở modal gửi **Yêu cầu đổi trả hàng trong 24h**.
- [Addresses.vue](file:///d:/FreshFood/resources/js/pages/client/account/Addresses.vue): Quản lý danh sách sổ địa chỉ, modal thêm mới địa chỉ, xóa địa chỉ.
- [Points.vue](file:///d:/FreshFood/resources/js/pages/client/account/Points.vue): Thẻ khách hàng VIP, số dư điểm, bảng quy định tích/tiêu điểm và lịch sử giao dịch điểm.

---

## 4. KIỂM THỬ TỰ ĐỘNG (TESTS)

- [RoleBasedAccessControlTest.php](file:///d:/FreshFood/tests/Feature/RoleBasedAccessControlTest.php): Kiểm tra phân quyền truy cập giữa 4 nhóm người dùng (`admin`, `quan_ly`, `nhan_vien`, `khach_hang`) và bảo vệ các phân hệ `/admin`, `/store`, `/tai-khoan`.
- [CheckoutTest.php](file:///d:/FreshFood/tests/Feature/Client/CheckoutTest.php): Kiểm tra quy trình hoàn tất thanh toán và tự động xóa sạch giỏ hàng trong database cho cả khách vãng lai (guest session) và khách hàng đã đăng nhập.
- [StorefrontTest.php](file:///d:/FreshFood/tests/Feature/Client/StorefrontTest.php): Toàn bộ 19 test cases chi tiết cho luồng Storefront:
  1. Tải trang chủ với đầy đủ dữ liệu (Flash sale, hàng tươi, voucher).
  2. Tìm kiếm và lọc danh mục sản phẩm.
  3. Trang chi tiết sản phẩm và danh mục.
  4. Xác thực mã giảm giá voucher (`checkCoupon`).
  5. Luồng đặt hàng thanh toán và ghi nhận chi tiết đơn hàng.
  6. Cổng tài khoản khách hàng (Tổng quan, Đơn hàng, Điểm thưởng, Địa chỉ).
  7. Tính năng gửi yêu cầu đổi trả thực phẩm 24h sinh phiếu `PhieuDoiTra`.
  8. Ưu tiên giá chi nhánh từ bảng `gia_san_pham_cua_hang` trên trang catalog sản phẩm.
  9. Ưu tiên giá chi nhánh tại trang chi tiết sản phẩm.
  10. Endpoint chọn chi nhánh lưu ID vào Session & Cookie.
  11. Endpoint làm mới đơn giá giỏ hàng theo chi nhánh được chọn.
  12. Đơn hàng ưu tiên tính giá chi nhánh được chọn.
  13. Thêm sản phẩm vào giỏ hàng lưu vào database table `gio_hang`.
  14. Khách hàng đã đăng nhập thêm giỏ hàng được gán `user_id`.
  15. API cập nhật số lượng và xóa sản phẩm giỏ hàng trong CSDL.
  16. Hợp nhất giỏ hàng của khách vãng lai vào tài khoản thành viên khi đăng nhập (`AuthenticatedSessionController`).
  17. API tìm kiếm gợi ý tức thì trả về danh sách sản phẩm khớp từ khóa.
  18. Chuyển chi nhánh hoặc thêm sản phẩm từ chi nhánh mới đồng nhất toàn bộ giỏ hàng.
  19. Đổi chi nhánh với `clear_cart = true` hoặc gọi API `/api/cart/clear` xóa sạch 100% dữ liệu giỏ hàng trong CSDL.

---

## 5. TÀI LIỆU KẾ HOẠCH

- [KE_HOACH_HOAN_THIEN_WEB.md](file:///d:/FreshFood/KE_HOACH_HOAN_THIEN_WEB.md): Bản kế hoạch tổng thể 6 giai đoạn phát triển hệ thống Fresh Food.
- [filenote.md](file:///d:/FreshFood/filenote.md): Tài liệu chú thích chi tiết các file đã tạo trong toàn bộ dự án.

---

## 6. TÀI NGUYÊN THƯƠNG HIỆU & HÌNH ẢNH (BRAND ASSETS)

- [logo.png](file:///d:/FreshFood/logo.png): File gốc logo nhận diện chính thức của Fresh Food (biểu tượng cá cách điệu từ lá cây tươi hữu cơ và chữ "FRESH").
- [public/images/logo.png](file:///d:/FreshFood/public/images/logo.png): Logo triển khai hiển thị trên Header, Footer, Thanh điều hướng và các trang Auth.
- [public/logo.png](file:///d:/FreshFood/public/logo.png): Logo favicon và hiển thị biểu tượng ứng dụng trên trình duyệt.
- [AppLogo.vue](file:///d:/FreshFood/resources/js/components/AppLogo.vue): Component Vue hiển thị logo nhận diện cho ứng dụng.
- [AppLogoIcon.vue](file:///d:/FreshFood/resources/js/components/AppLogoIcon.vue): Component Vue hiển thị icon logo thu nhỏ.
