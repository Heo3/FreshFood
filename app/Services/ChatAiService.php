<?php

namespace App\Services;

use App\Models\DonHang;
use App\Models\SanPham;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatAiService
{
    protected ?string $apiKey;

    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Tạo câu trả lời AI theo cơ chế Hybrid:
     * - Nếu có GEMINI_API_KEY -> gọi Gemini API kết hợp dữ liệu Fresh Food thời gian thực
     * - Nếu không có hoặc API lỗi -> Fallback sang Rule-based Knowledge Base thông minh
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{content: string, cards: array<string, mixed>|null}
     */
    public function generateReply(string $userMessage, array $history = [], ?User $user = null): array
    {
        $cleanMessage = trim($userMessage);

        // 1. Kiểm tra nhanh nếu là tra cứu đơn hàng bằng regex mã đơn
        if (preg_match('/(FF[0-9A-Z]{6,16}|ORD-[0-9A-Z-]+)/i', $cleanMessage, $matches)) {
            $orderCode = strtoupper($matches[1]);
            $order = DonHang::where('ma_don_hang', $orderCode)->first();
            if ($order) {
                return $this->formatOrderTrackingReply($order);
            }
        }

        // 2. Thử gọi Gemini AI nếu có API Key
        if (! empty($this->apiKey)) {
            try {
                $geminiReply = $this->callGeminiApi($cleanMessage, $history, $user);
                if ($geminiReply !== null) {
                    return $geminiReply;
                }
            } catch (\Throwable $e) {
                Log::warning('Lỗi gọi Gemini API, chuyển sang Knowledge Base Fallback: '.$e->getMessage());
            }
        }

        // 3. Fallback sang Smart Knowledge Engine
        return $this->knowledgeBaseReply($cleanMessage, $user);
    }

    /**
     * Gọi Google Gemini API với context dữ liệu Fresh Food.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{content: string, cards: array<string, mixed>|null}|null
     */
    protected function callGeminiApi(string $message, array $history, ?User $user): ?array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        // Chuẩn bị bối cảnh sản phẩm liên quan
        $relevantProducts = $this->searchRelevantProducts($message, 5);
        $productContext = '';
        if ($relevantProducts->isNotEmpty()) {
            $productContext = "Danh sách sản phẩm tươi sạch Fresh Food đang có sẵn phù hợp:\n";
            foreach ($relevantProducts as $p) {
                $price = number_format((float) ($p->gia_giam ?? $p->gia_ban), 0, ',', '.');
                $productContext .= "- {$p->ten_san_pham} (Giá: {$price}đ/{$p->don_vi_tinh}, Quy cách: {$p->quy_cach})\n";
            }
        }

        $systemInstruction = <<<TEXT
Bạn là Trợ lý AI Fresh Food - chuỗi siêu thị thực phẩm tươi sống hữu cơ chuẩn VietGAP & GlobalGAP hàng đầu tại Việt Nam.
Tính cách: Thân thiện, chu đáo, hiểu biết sâu sắc về dinh dưỡng, ẩm thực gia đình và bảo quản thực phẩm tươi sống.
Nhiệm vụ trọng tâm:
1. TRẢ LỜI ĐÚNG TRỌNG TÂM câu hỏi của khách hàng:
   - Khi khách hỏi mua một loại thực phẩm (như thịt bò, cá hồi, thịt heo, rau củ...): Hãy giới thiệu trực tiếp các sản phẩm cụ thể Fresh Food đang có sẵn (nêu rõ tên, giá, quy cách, đặc điểm độ tươi ngon và món ăn phù hợp nhất). Không trả lời chung chung!
   - Khi khách hỏi muốn nấu hoặc làm một món ăn (ví dụ: làm bò bít tết, nấu canh chua, bò xào...):
     a. Nêu tên món, thời gian chế biến và khẩu phần.
     b. Liệt kê rõ ràng 2 nhóm nguyên liệu:
        * 🛒 NGUYÊN LIỆU CÓ BÁN TẠI FRESH FOOD: Liệt kê các sản phẩm lấy từ danh sách hàng đang có sẵn dưới đây, kèm giá bán.
        * 🧺 NGUYÊN LIỆU & GIA VỊ CẦN TỰ CHUẨN BỊ THÊM Ở NHÀ: Liệt kê chi tiết các gia vị (muối biển, tiêu xay, lá hương thảo, chanh, giấm, v.v.) hoặc nguyên liệu phụ mà siêu thị không bán để khách biết tự đi mua.
     c. Hướng dẫn 3-4 bước sơ chế & nấu nhanh chuẩn vị đầu bếp.
2. Hướng dẫn cách bảo quản thực phẩm tươi sống đúng nhiệt độ.
3. Giải đáp chính sách: Giao hàng hỏa tốc trong 2 giờ, Miễn phí vận chuyển từ 250.000đ, Đổi trả trong 24 giờ nếu hàng không tươi ngon.
4. Khiếu nại / Gặp nhân viên: Nếu khách hàng gặp sự cố hàng hỏng hoặc muốn gặp nhân viên, hướng dẫn khách nhấn nút "Gặp Nhân Viên" hoặc chuyển sang tab "Chat Với Nhân Viên".

{$productContext}
Hãy trình bày bằng Tiếng Việt, câu từ ấm áp, định dạng Markdown rõ ràng, danh sách dễ nhìn.
TEXT;

        $contents = [];
        // Thêm một số tin nhắn lịch sử gần nhất (tối đa 4 tin nhắn)
        $recentHistory = array_slice($history, -4);
        foreach ($recentHistory as $msg) {
            $role = ($msg['role'] === 'user' || $msg['role'] === 'khach_hang') ? 'user' : 'model';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 800,
            ],
        ];

        $response = Http::timeout(8)->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();
            $replyText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($replyText) {
                $cards = null;
                if ($relevantProducts->isNotEmpty()) {
                    $cards = [
                        'type' => 'products',
                        'title' => 'Sản phẩm gợi ý cho bạn',
                        'items' => $relevantProducts->map(fn ($p) => [
                            'id' => $p->id,
                            'ten_san_pham' => $p->ten_san_pham,
                            'gia_ban' => (float) $p->gia_ban,
                            'gia_giam' => $p->gia_giam ? (float) $p->gia_giam : null,
                            'don_vi_tinh' => $p->don_vi_tinh,
                            'hinh_anh_chinh' => $p->hinh_anh_chinh,
                            'duong_dan_seo' => $p->duong_dan_seo,
                        ])->values()->all(),
                    ];
                }

                return [
                    'content' => $replyText,
                    'cards' => $cards,
                ];
            }
        }

        return null;
    }

    /**
     * Bộ máy tri thức nội bộ thông minh (Rule-based Knowledge Engine).
     *
     * @return array{content: string, cards: array<string, mixed>|null}
     */
    protected function knowledgeBaseReply(string $message, ?User $user): array
    {
        $lower = mb_strtolower($message);

        // 1. Khiếu nại / Gặp nhân viên / Đổi trả
        if ($this->containsAny($lower, ['khiếu nại', 'nhân viên', 'tổng đài', 'gặp người', 'hỏng', 'dập', 'thối', 'chết', 'giao thiếu', 'sai hàng', 'hoàn tiền'])) {
            return [
                'content' => "Dạ em rất tiếc về sự cố bạn đang gặp phải! 🌿\n\nFresh Food luôn cam kết **100% đổi trả hoặc hoàn tiền trong vòng 24h** đối với thực phẩm tươi sống có bất kỳ vấn đề nào về chất lượng.\n\n👉 Bạn hãy nhấn chuyển sang tab **\"Chat với Nhân viên\"** ngay trên thanh phía trên khung chat này, chọn chủ đề **\"Khiếu nại sản phẩm/đơn hàng\"** và nhập mã đơn, chuyên viên hỗ trợ của cửa hàng sẽ tiếp nhận xử lý ngay cho bạn nhé!",
                'cards' => [
                    'type' => 'action',
                    'action' => 'switch_to_staff',
                    'button_text' => 'Chuyển sang Chat với Nhân viên ngay',
                ],
            ];
        }

        // 2. Tra cứu đơn hàng khi hỏi chung chung
        if ($this->containsAny($lower, ['tra cứu đơn', 'đơn hàng của tôi', 'kiểm tra đơn', 'tình trạng đơn'])) {
            if ($user) {
                $latestOrder = DonHang::where('user_id', $user->id)->latest()->first();
                if ($latestOrder) {
                    return $this->formatOrderTrackingReply($latestOrder);
                }
            }

            return [
                'content' => 'Để tra cứu thông tin đơn hàng, bạn chỉ cần **gửi trực tiếp Mã Đơn Hàng** (ví dụ: `FF261116ABCD`) vào ô chat này, em sẽ kiểm tra trạng thái giao hàng tức thì cho bạn nhé! 🛵',
                'cards' => null,
            ];
        }

        // 3. Tư vấn món ăn cụ thể & gợi ý nguyên liệu / công thức nấu nướng
        $cookingReply = $this->handleCookingDishIntent($lower);
        if ($cookingReply !== null) {
            return $cookingReply;
        }

        // 4. Tư vấn nhóm sản phẩm theo nhu cầu mua cụ thể (Thịt bò, Cá hồi, Thịt heo, Rau củ, Trái cây...)
        $categoryReply = $this->handleProductCategoryIntent($lower);
        if ($categoryReply !== null) {
            return $categoryReply;
        }

        // 5. Hướng dẫn bảo quản thực phẩm
        if ($this->containsAny($lower, ['bảo quản', 'để được bao lâu', 'tươi lâu', 'ngăn mát', 'ngăn đông', 'hạn sử dụng', 'nhiệt độ'])) {
            return [
                'content' => "❄️ **Cẩm nang bảo quản thực phẩm tươi sống từ chuyên gia Fresh Food:**\n\n"
                    ."1. **Rau củ hữu cơ:** Không nên rửa trước khi cho vào tủ lạnh. Nhặt bỏ lá úa, gói trong giấy thấm ẩm hoặc túi zip có lỗ thoáng, bảo quản ở ngăn mát (3-6°C), dùng tốt nhất trong 3-5 ngày.\n"
                    ."2. **Thịt heo, bò, gà tươi:** Nếu dùng trong ngày để ngăn mát (0-2°C). Nếu dùng sau, chia nhỏ từng khẩu phần và cấp đông (-18°C) ngay khi nhận hàng.\n"
                    ."3. **Hải sản & Cá hồi phi-lê:** Thấm khô bề mặt, bọc màng thực phẩm kín hoặc hút chân không để giữ trọn độ ngọt và vị tươi rói.\n"
                    ."4. **Trái cây nhập khẩu (Táo, Cam, Nho):** Bảo quản ngăn mát, tránh để gần các loại quả chín nhanh như chuối.\n\n"
                    .'Nếu cần thêm tư vấn về loại thực phẩm cụ thể, bạn cứ nhắn tên món đó cho em nhé! 🍏',
                'cards' => null,
            ];
        }

        // 6. Chính sách giao hàng & Đổi trả
        if ($this->containsAny($lower, ['giao hàng', 'vận chuyển', 'ship', 'phí ship', 'đổi trả', 'chính sách', 'bao lâu thì nhận'])) {
            return [
                'content' => "🚚 **Chính sách dịch vụ tại chuỗi Fresh Food:**\n\n"
                    ."* ⚡ **Giao hàng siêu tốc 2 giờ:** Áp dụng cho các quận nội thành gần chi nhánh cửa hàng.\n"
                    ."* 🎁 **Miễn phí vận chuyển:** Áp dụng cho mọi đơn hàng từ **250.000đ** trở lên.\n"
                    ."* 🔄 **Chính sách đổi trả 24h:** Cam kết hoàn tiền hoặc 1 đổi 1 ngay trong 24h nếu thực phẩm tươi sống không đạt độ tươi ngon, dập nát.\n"
                    .'* 💳 **Phương thức thanh toán:** Tiền mặt khi nhận hàng (COD), Chuyển khoản VietQR và Cổng VNPay.',
                'cards' => null,
            ];
        }

        // 7. Tìm kiếm sản phẩm theo từ khóa
        $matchingProducts = $this->searchRelevantProducts($message, 4);
        if ($matchingProducts->isNotEmpty()) {
            $content = "Dạ tại siêu thị Fresh Food hiện đang có sẵn các sản phẩm tươi sạch phù hợp với nhu cầu của bạn, được kiểm định chất lượng nghiêm ngặt mỗi sáng:\n\n";
            foreach ($matchingProducts as $p) {
                $price = number_format((float) ($p->gia_giam ?? $p->gia_ban), 0, ',', '.');
                $content .= "* **{$p->ten_san_pham}**: `{$price}đ/{$p->don_vi_tinh}`".($p->quy_cach ? " ({$p->quy_cach})" : '')."\n";
            }
            $content .= "\n👉 Bạn có thể xem chi tiết và bấm **'Mua ngay'** vào giỏ hàng ngay bên dưới nhé!";

            return [
                'content' => $content,
                'cards' => [
                    'type' => 'products',
                    'title' => 'Sản phẩm Fresh Food có sẵn',
                    'items' => $matchingProducts->map(fn ($p) => [
                        'id' => $p->id,
                        'ten_san_pham' => $p->ten_san_pham,
                        'gia_ban' => (float) $p->gia_ban,
                        'gia_giam' => $p->gia_giam ? (float) $p->gia_giam : null,
                        'don_vi_tinh' => $p->don_vi_tinh,
                        'hinh_anh_chinh' => $p->hinh_anh_chinh,
                        'duong_dan_seo' => $p->duong_dan_seo,
                    ])->values()->all(),
                ],
            ];
        }

        // 8. Lời chào mặc định & các gợi ý tương tác
        return [
            'content' => "Xin chào bạn! Em là **Trợ lý AI của Fresh Food** 🥦\n\nEm luôn sẵn sàng hỗ trợ bạn 24/7 với các nội dung:\n"
                ."* 🥩 **Tư vấn thực phẩm & nguyên liệu:** *Muốn mua thịt bò, cá hồi, rau sạch...*\n"
                ."* 👨‍🍳 **Công thức & chuẩn bị nấu món:** *Hôm nay làm bò bít tết, lẩu bò, cá hồi áp chảo...*\n"
                ."* 📦 **Tra cứu đơn hàng:** *Gõ mã đơn hàng FF... để kiểm tra*\n"
                ."* 💬 **Khiếu nại / Gặp nhân viên:** Chuyển sang tab *\"Chat với Nhân viên\"* để được hỗ trợ trực tiếp.\n\nBạn cần em giúp gì hôm nay nè?",
            'cards' => [
                'type' => 'suggestions',
                'items' => [
                    'Muốn mua thịt bò tươi',
                    'Nay tôi muốn làm bò bít tết',
                    'Cá hồi áp chảo sốt bơ chanh',
                    'Chính sách giao hàng siêu tốc',
                ],
            ],
        ];
    }

    /**
     * Nhận diện và xử lý yêu cầu nấu một món ăn cụ thể:
     * - Liệt kê nguyên liệu CÓ BÁN tại siêu thị Fresh Food (kèm thẻ để bấm Mua ngay).
     * - Liệt kê nguyên liệu CÒN THIẾU cần tự mua thêm ở nhà/ngoài chợ.
     * - Cung cấp các bước nấu nhanh chuẩn vị đầu bếp.
     *
     * @return array{content: string, cards: array<string, mixed>}|null
     */
    protected function handleCookingDishIntent(string $lower): ?array
    {
        // Danh sách các công thức món ăn đặc trưng
        $dishes = [
            'bo_bit_tet' => [
                'triggers' => ['bít tết', 'bit tet', 'steak', 'beefsteak', 'bò tết', 'bò áp chảo', 'bò sốt tiêu', 'beef steak'],
                'name' => 'Bò Bít Tết Thăn Úc Sốt Bơ Tỏi (Beefsteak Chuẩn Vị Nhà Hàng)',
                'time' => '15 - 20 phút',
                'portion' => '2 - 3 người',
                'available_keywords' => ['thăn bò', 'bơ nhạt', 'tỏi lý sơn', 'dầu oliu'],
                'available_notes' => [
                    'Thăn Bò Úc Black Angus Cắt Dày Steak: Thớ thịt mềm ngọt, vân mỡ hoàn hảo, giữ trọn độ ẩm khi áp chảo.',
                    'Bơ Nhạt Tự Nhiên Anchor New Zealand: Tạo độ béo ngậy thơm nức mũi khi rưới basting lên mặt thịt.',
                    'Tỏi Lý Sơn: Đập dập phi thơm ngấm sâu vào từng thớ thịt bò.',
                    'Dầu Oliu Extra Virgin Địa Trung Hải: Chịu nhiệt tốt, tốt cho sức khỏe tim mạch.',
                ],
                'missing_ingredients' => [
                    'Muối biển hạt thô (hoặc muối tinh hạt to)',
                    'Hạt tiêu đen xay tươi',
                    '1-2 nhánh lá hương thảo (Rosemary) tươi hoặc khô',
                    'Măng tây hoặc khoai tây ăn kèm (tùy sở thích)',
                ],
                'steps' => [
                    'Sơ chế & Thấm khô: Rã đông tự nhiên thịt thăn bò Úc, dùng khăn giấy chuyên dụng thấm thật khô cả 2 mặt thịt (tuyệt đối không rửa nước). Rắc đều muối biển và tiêu đen lên 2 bề mặt trước khi áp chảo 5 phút.',
                    'Áp chảo lửa lớn: Đun nóng chảo chống dính dày hoặc chảo gang với 1 thìa dầu oliu trên lửa lớn. Khi chảo thật nóng và bốc hơi nhẹ, cho miếng thăn bò vào áp chảo 2 - 2.5 phút mỗi mặt để tạo lớp vỏ xém nâu vàng giòn (độ chín Medium Rare lý tưởng).',
                    'Rưới bơ (Basting): Hạ nhỏ lửa, thả 1 viên bơ lạt Anchor, 3-4 tép tỏi Lý Sơn đập dập và nhánh lá rosemary vào chảo. Dùng thìa múc liên tục bơ nóng rưới đều lên mặt thịt bò trong 1 phút để thịt ngấm trọn vị bơ béo thơm.',
                    'Nghỉ thịt (Resting): Gắp miếng steak ra đĩa và để thịt nghỉ khoảng 5 phút trước khi cắt. Bước này giúp nước ngọt phân bổ ngược lại vào từng thớ thịt, giữ steak luôn mềm mọng mọng nước.',
                ],
                'chef_tip' => 'Thấm thật khô mặt thịt trước khi chiên và luôn để thịt nghỉ 5 phút sau khi nấu, không cắt ngay để nước ngọt không bị chảy ra ngoài.',
            ],
            'ba_chi_bo_cuon_nam' => [
                'triggers' => ['ba chỉ bò', 'bò cuộn', 'cuộn nấm', 'lẩu bò', 'nhúng lẩu', 'nướng bò'],
                'name' => 'Ba Chỉ Bò Mỹ Cuộn Nấm Kim Châm Nướng / Nhúng Lẩu Chua Cay',
                'time' => '15 phút',
                'portion' => '3 - 4 người',
                'available_keywords' => ['ba chỉ bò', 'nấm đùi gà', 'ớt chuông'],
                'available_notes' => [
                    'Ba Chỉ Bò Mỹ Cắt Cuộn: Tỷ lệ nạc mỡ đan xen hoàn hảo, mềm ngọt béo ngậy.',
                    'Nấm Đùi Gà Tươi Cao Cấp: Giòn ngọt sần sật, có thể cắt lát nướng cùng thịt bò.',
                    'Ớt Chuông Đà Lạt Đủ Màu: Nướng kèm tạo màu sắc bắt mắt và vị ngọt thanh.',
                ],
                'missing_ingredients' => [
                    'Nấm kim châm tươi (mua ở chợ/siêu thị gần nhà)',
                    'Hành lá tươi (chần nước sôi dùng để buộc cuộn thịt)',
                    'Sốt ướp BBQ tiêu đen hoặc gói gia vị cốt lẩu Thái chua cay',
                ],
                'steps' => [
                    'Sơ chế: Trải từng lát Ba Chỉ Bò Mỹ đã cắt cuộn sẵn ra đĩa. Nấm kim châm cắt gốc, rửa sạch và chia thành từng nhúm nhỏ.',
                    'Cuộn thịt: Đặt nhúm nấm vào một đầu lát thịt bò rồi cuộn tròn thật chặt tay. Dùng cọng hành lá chần buộc cố định.',
                    'Chế biến: Nếu nướng, quét nhẹ một lớp dầu ăn/dầu hào rồi nướng trên bếp than hoặc nồi chiên không dầu ở 180°C trong 8 phút. Nếu ăn lẩu, thả trực tiếp vào nồi nước lẩu chua cay đang sôi sùng sục khoảng 1-2 phút là thưởng thức được ngay.',
                ],
                'chef_tip' => 'Không nên nướng hoặc nhúng lẩu quá lâu để thịt ba chỉ bò giữ được độ mềm béo ngậy tự nhiên.',
            ],
            'bo_xao_bong_cai' => [
                'triggers' => ['bò xào', 'xào bông cải', 'bò xào súp lơ', 'súp lơ xào', 'bò xào rau'],
                'name' => 'Thịt Bò Úc Xào Bông Cải Xanh Đà Lạt Giòn Ngọt',
                'time' => '15 phút',
                'portion' => '3 - 4 người',
                'available_keywords' => ['thăn bò', 'bông cải xanh', 'tỏi lý sơn', 'dầu oliu'],
                'available_notes' => [
                    'Thăn Bò Úc Black Angus: Thái lát mỏng ngang thớ thịt mềm ngọt.',
                    'Bông Cải Xanh Đà Lạt: Tươi giòn giàu vitamin C và chất chống oxy hóa.',
                    'Tỏi Lý Sơn: Thơm nức khi phi xào lửa lớn.',
                    'Dầu Oliu Extra Virgin: Tốt cho sức khỏe.',
                ],
                'missing_ingredients' => [
                    'Dầu hào Maggi',
                    'Nước tương (xì dầu), hạt nêm, tiêu xay',
                    'Hành tây (tùy chọn)',
                ],
                'steps' => [
                    'Ướp thịt bò: Thịt thăn bò thái lát mỏng ngang thớ, ướp với 1 thìa tỏi băm, 1 thìa dầu hào, 1/2 thìa dầu oliu trong 10 phút.',
                    'Chần bông cải: Bông cải cắt miếng vừa ăn, chần qua nước sôi có chút muối trong 45 giây rồi ngâm ngay vào nước đá lạnh để giữ màu xanh mướt và độ giòn.',
                    'Xào thịt: Phi thơm tỏi Lý Sơn, cho thịt bò vào xào trên lửa lớn trong 1-2 phút cho vừa chín tới thì trút ra đĩa riêng.',
                    'Hoàn thiện: Tiếp tục phi tỏi, cho bông cải vào xào nhanh, trút thịt bò vào đảo đều tay thêm 30 giây, rắc tiêu xay và thưởng thức nóng sốt.',
                ],
                'chef_tip' => 'Xào thịt bò ở lửa thật lớn và không xào quá lâu để thịt không bị dai.',
            ],
            'ca_hoi_ap_chao' => [
                'triggers' => ['cá hồi áp chảo', 'áp chảo cá hồi', 'sốt bơ chanh', 'cá hồi bơ tỏi', 'cá hồi sốt cam', 'cá hồi nướng'],
                'name' => 'Cá Hồi Nauy Phi Lê Áp Chảo Sốt Bơ Tỏi Chanh',
                'time' => '15 - 20 phút',
                'portion' => '2 người',
                'available_keywords' => ['cá hồi', 'bơ nhạt', 'tỏi lý sơn', 'dầu oliu'],
                'available_notes' => [
                    'Cá Hồi Nauy Tươi Phi Lê Sashimi Class: Thịt cam tươi rói, vân mỡ đều, giàu Omega-3.',
                    'Bơ Nhạt Anchor New Zealand: Hòa quyện cùng tỏi và chanh tạo nên nước sốt bơ chanh sánh mịn.',
                    'Tỏi Lý Sơn & Dầu Oliu: Giúp áp chảo thơm lừng không bị khét.',
                ],
                'missing_ingredients' => [
                    '1 quả chanh vàng hoặc chanh tươi (vắt lấy nước cốt)',
                    'Hạt tiêu đen xay, muối tinh',
                    'Rau mùi tây (ngò tây/parsley) hoặc măng tây trang trí',
                ],
                'steps' => [
                    'Sơ chế cá: Miếng phi lê cá hồi thấm thật khô bằng khăn giấy, rắc chút muối và tiêu lên 2 mặt trong 5 phút.',
                    'Áp chảo cá: Cho 1 muỗng dầu oliu vào chảo, áp chảo mặt da cá trước ở lửa vừa trong 3-4 phút cho giòn rụm, lật mặt thịt áp thêm 2 phút rồi gắp ra đĩa.',
                    'Làm sốt bơ chanh: Cho viên bơ Anchor, tỏi Lý Sơn băm nhỏ và 2 thìa nước cốt chanh vào chảo đảo đều cho bơ tan chảy sánh mịn.',
                    'Hoàn thiện: Rưới sốt bơ tỏi chanh óng ánh lên miếng cá hồi, rắc rau mùi tây và thưởng thức cùng salad tươi giòn.',
                ],
                'chef_tip' => 'Áp chảo mặt da thật phẳng và giòn trước, tránh lật cá nhiều lần làm vỡ thớ cá hồi.',
            ],
            'canh_chua_ca_hoi' => [
                'triggers' => ['canh chua cá hồi', 'canh cá hồi', 'canh chua cá'],
                'name' => 'Canh Chua Cá Hồi Thanh Mát Cà Chua Bi Đà Lạt',
                'time' => '20 phút',
                'portion' => '3 - 4 người',
                'available_keywords' => ['cá hồi', 'cà chua bi', 'tỏi lý sơn'],
                'available_notes' => [
                    'Cá Hồi Nauy Tươi Phi Lê: Thịt béo ngậy, ngọt thanh, không tanh khi nấu đúng cách.',
                    'Cà Chua Bi Cherry Đà Lạt: Vị ngọt thanh chua nhẹ tự nhiên, tạo màu canh đẹp mắt.',
                    'Tỏi Lý Sơn: Phi vàng thơm khử sạch mùi tanh của cá.',
                ],
                'missing_ingredients' => [
                    'Quả me chua hoặc dứa thơm (khóm)',
                    'Bạc hà (dọc mùng), đậu bắp, giá đỗ',
                    'Rau ngổ, ngò gai, ớt tươi, nước mắm ngon',
                ],
                'steps' => [
                    'Sơ chế cá: Cá hồi cắt khúc vừa ăn, rửa qua chút rượu trắng hoặc nước muối loãng, để ráo.',
                    'Xào cà chua: Phi thơm tỏi Lý Sơn băm, cho cà chua bi bổ đôi vào xào mềm tạo màu nước dùng hấp dẫn.',
                    'Nấu nước dùng: Đổ nước lọc vừa ăn đun sôi, cho nước cốt me vào nêm đường, nước mắm vừa vị chua ngọt thanh mát.',
                    'Nấu cá: Nước sôi bùng thì thả cá hồi vào nấu trong 3 phút, cho giá đỗ bạc hà và rau ngổ ngò gai rồi tắt bếp ngay.',
                ],
                'chef_tip' => 'Không đảo mạnh tay khi thả cá hồi vào nồi để miếng cá giữ nguyên vẹn không bị nát.',
            ],
            'canh_cai_thit_bam' => [
                'triggers' => ['canh cải', 'cải bó xôi', 'canh rau', 'thịt bằm', 'thịt băm', 'thịt heo bằm'],
                'name' => 'Canh Cải Bó Xôi Hữu Cơ Đà Lạt Nấu Thịt Heo Bằm',
                'time' => '15 phút',
                'portion' => '3 - 4 người',
                'available_keywords' => ['cải bó xôi', 'thịt ba rọi', 'tỏi lý sơn'],
                'available_notes' => [
                    'Cải Bó Xôi Hữu Cơ Đà Lạt: Giàu sắt, canxi và vitamin, vị ngọt thanh tự nhiên.',
                    'Thịt Ba Rọi Heo Quế VietGAP: Bằm nhỏ xào săn ngọt nước.',
                ],
                'missing_ingredients' => [
                    'Hành khô (hành tím băm)',
                    'Nước mắm ngon, hạt nêm, tiêu xay',
                ],
                'steps' => [
                    'Sơ chế: Cải bó xôi rửa sạch từng bẹ lá, cắt khúc 3-4cm. Thịt heo băm nhỏ ướp với chút hạt nêm và tiêu.',
                    'Xào thịt: Phi thơm hành tím băm, cho thịt heo bằm vào đảo săn.',
                    'Nấu canh: Đổ nước lọc vừa đủ đun sôi bùng, hớt sạch bọt.',
                    'Thả rau: Cho cải bó xôi vào nấu trong 1-2 phút vừa chín tới để giữ trọn vitamin, nêm nếm lại và múc ra tô.',
                ],
                'chef_tip' => 'Rau cải bó xôi rất nhanh chín, chỉ cần nấu 1-2 phút khi nước sôi là tắt bếp ngay.',
            ],
            'suon_non_heo' => [
                'triggers' => ['sườn non', 'sườn heo', 'sườn rim', 'sườn xào chua ngọt', 'nướng sườn', 'canh sườn'],
                'name' => 'Sườn Non Heo Thảo Mộc Rim Mặn Ngọt / Xào Chua Ngọt',
                'time' => '30 phút',
                'portion' => '3 - 4 người',
                'available_keywords' => ['sườn non', 'tỏi lý sơn', 'ớt chuông'],
                'available_notes' => [
                    'Sườn Non Heo Thảo Mộc Thượng Hạng: Sườn dẻ nhiều nạc, thịt mềm ngọt thơm tự nhiên.',
                    'Tỏi Lý Sơn: Phi vàng dậy mùi thơm đặc trưng.',
                ],
                'missing_ingredients' => [
                    'Nước mắm cốt ngon, đường phèn hoặc đường thốt nốt',
                    'Hành tím băm, tiêu đen, ớt tươi',
                    'Giấm gạo & tương cà (nếu làm sườn xào chua ngọt)',
                ],
                'steps' => [
                    'Chần sườn: Sườn non chặt miếng vừa ăn, chần qua nước sôi 2 phút có chút muối để khử mùi và sạch bọt bẩn.',
                    'Ướp gia vị: Ướp sườn với tỏi băm, hành tím, nước mắm, tiêu và 1 thìa đường trong 20 phút.',
                    'Rán vàng sườn: Phi thơm tỏi, cho sườn vào đảo trên lửa vừa cho săn và xém vàng đều các mặt.',
                    'Rim sườn: Thêm nước sốt mặn ngọt (hoặc nước dừa tươi), đậy vung hạ nhỏ lửa rim trong 20-25 phút đến khi sốt sánh sệt bám đều quanh từng miếng sườn.',
                ],
                'chef_tip' => 'Chần sườn trước khi ướp giúp thịt sườn sạch thơm và thấm trọn vị sốt keo mặn ngọt.',
            ],
            'tom_su_nuong' => [
                'triggers' => ['tôm sú', 'tôm nướng', 'tôm hấp', 'tôm bơ tỏi', 'tôm chiên'],
                'name' => 'Tôm Sú Biển Cà Mau Nướng Bơ Tỏi Thơm Nức Mũi',
                'time' => '15 - 20 phút',
                'portion' => '3 người',
                'available_keywords' => ['tôm sú', 'bơ nhạt', 'tỏi lý sơn'],
                'available_notes' => [
                    'Tôm Sú Biển Cà Mau Tươi Sống: Thịt giòn ngọt sần sật, size lớn 20 con/kg.',
                    'Bơ Nhạt Anchor New Zealand: Tạo vị béo ngậy thơm nức.',
                    'Tỏi Lý Sơn: Băm nhỏ phi vàng rưới đều thân tôm.',
                ],
                'missing_ingredients' => [
                    'Hạt tiêu xay, muối ớt chanh để chấm',
                    'Giấy bạc nướng hoặc xiên tre',
                ],
                'steps' => [
                    'Sơ chế tôm: Tôm sú rửa sạch, cắt bớt râu và chân, rạch một đường dọc sống lưng rút chỉ đen.',
                    'Làm sốt bơ tỏi: Làm tan chảy bơ Anchor, phi thơm tỏi Lý Sơn băm nhỏ cùng chút hạt nêm và tiêu xay.',
                    'Ướp & Nướng: Phết đều sốt bơ tỏi vào rãnh lưng tôm, xếp vào khay có lót giấy bạc.',
                    'Nướng chín: Nướng ở nhiệt độ 180°C trong nồi chiên không dầu hoặc lò nướng khoảng 10-12 phút cho vỏ tôm đỏ au thơm phức.',
                ],
                'chef_tip' => 'Không nướng quá lâu khiến tôm bị khô xác mất đi vị ngọt biển tự nhiên.',
            ],
            'salad_rau_cu' => [
                'triggers' => ['salad', 'rau trộn', 'giảm cân', 'eat clean', 'ăn kiêng', 'healthy'],
                'name' => 'Salad Rau Củ Đà Lạt Giòn Mát Trộn Dầu Oliu Extra Virgin',
                'time' => '10 phút',
                'portion' => '2 người',
                'available_keywords' => ['xà lách', 'cà chua bi', 'ớt chuông', 'dầu oliu'],
                'available_notes' => [
                    'Xà Lách Thủy Canh Mỡ Mềm Lô Lô: Giòn tươi thanh mát.',
                    'Cà Chua Bi Cherry Đỏ Ngọt: Căng mọng, ngọt đậm tự nhiên.',
                    'Ớt Chuông Đà Lạt: Cung cấp vitamin A & C dồi dào.',
                    'Dầu Oliu Extra Virgin: Tạo độ bóng mượt và tốt cho vóc dáng.',
                ],
                'missing_ingredients' => [
                    'Giấm táo hoặc nước cốt chanh vàng',
                    'Mật ong nguyên chất, muối tiêu',
                    'Sốt mè rang Kewpie (tùy sở thích)',
                ],
                'steps' => [
                    'Sơ chế rau: Rửa nhẹ rau xà lách, cà chua bi, ớt chuông trong nước muối loãng, để ráo nước hoàn toàn.',
                    'Cắt rau: Xà lách cắt khúc vừa ăn, cà chua bi bổ đôi, ớt chuông thái lát mỏng.',
                    'Pha sốt: Hòa 2 muỗng dầu Oliu + 1 muỗng giấm táo + 1 muỗng mật ong + chút muối tiêu, đánh tan.',
                    'Trộn salad: Rưới sốt lên tô rau củ và trộn nhẹ tay ngay trước khi ăn để rau giữ độ tươi giòn.',
                ],
                'chef_tip' => 'Rau phải thật ráo nước trước khi trộn sốt để salad không bị chảy nước và mất độ giòn.',
            ],
        ];

        // 1. Kiểm tra xem người dùng có nhắc đến món ăn cụ thể trong danh sách không
        $matchedDish = null;
        foreach ($dishes as $dish) {
            if ($this->containsAny($lower, $dish['triggers'])) {
                $matchedDish = $dish;
                break;
            }
        }

        // 2. Nếu không trúng trực tiếp từ khóa món nhưng hỏi chung về nấu nướng (VD: "hôm nay ăn gì", "gợi ý món")
        if (! $matchedDish && $this->containsAny($lower, ['hôm nay ăn gì', 'nấu gì', 'thực đơn', 'công thức', 'gợi ý món', 'bữa tối', 'bữa trưa'])) {
            $dishKeys = array_keys($dishes);
            $randomKey = $dishKeys[array_rand($dishKeys)];
            $matchedDish = $dishes[$randomKey];
        }

        if (! $matchedDish) {
            return null;
        }

        // 3. Tìm các nguyên liệu CÓ BÁN tại siêu thị Fresh Food trong cơ sở dữ liệu
        $allProducts = SanPham::where('trang_thai', 1)->get();
        $availableProducts = $allProducts->filter(function ($p) use ($matchedDish) {
            $pName = mb_strtolower($p->ten_san_pham);
            foreach ($matchedDish['available_keywords'] as $kw) {
                if (mb_stripos($pName, $kw) !== false) {
                    return true;
                }
            }

            return false;
        })->values();

        // 4. Xây dựng nội dung phản hồi Markdown chi tiết và chuẩn xác
        $content = "🍽️ **Gợi ý món ngon: {$matchedDish['name']}**\n";
        $content .= "⏱️ *Thời gian:* ~{$matchedDish['time']} | 👨‍👩‍👧‍👦 *Khẩu phần:* {$matchedDish['portion']}\n\n";

        // Nhóm 1: Nguyên liệu CÓ BÁN tại siêu thị Fresh Food
        $content .= "🛒 **1. Nguyên liệu CÓ BÁN tại siêu thị Fresh Food:** *(bạn có thể bấm chọn Mua ngay bên dưới)*\n";
        if (! empty($matchedDish['available_notes'])) {
            foreach ($matchedDish['available_notes'] as $note) {
                $content .= "- **{$note}**\n";
            }
        } else {
            foreach ($availableProducts as $p) {
                $price = number_format((float) ($p->gia_giam ?? $p->gia_ban), 0, ',', '.');
                $content .= "- **{$p->ten_san_pham}** (`{$price}đ/{$p->don_vi_tinh}`)\n";
            }
        }

        // Nhóm 2: Nguyên liệu CÒN THIẾU cần tự chuẩn bị thêm ở nhà
        $content .= "\n🧺 **2. Gia vị & nguyên liệu bạn cần chuẩn bị thêm ở nhà / ngoài chợ:**\n";
        foreach ($matchedDish['missing_ingredients'] as $missing) {
            $content .= "- {$missing}\n";
        }

        // Nhóm 3: Các bước thực hiện
        $content .= "\n👨‍🍳 **3. Các bước chế biến nhanh chuẩn vị đầu bếp:**\n";
        foreach ($matchedDish['steps'] as $idx => $step) {
            $stepNum = $idx + 1;
            $content .= "{$stepNum}. {$step}\n";
        }

        // Mẹo đầu bếp
        if (! empty($matchedDish['chef_tip'])) {
            $content .= "\n💡 **Bí quyết đầu bếp:** *{$matchedDish['chef_tip']}*";
        }

        return [
            'content' => $content,
            'cards' => [
                'type' => 'products',
                'title' => 'Nguyên liệu món ăn có sẵn tại Fresh Food',
                'missing_ingredients' => $matchedDish['missing_ingredients'],
                'items' => $availableProducts->map(fn ($p) => [
                    'id' => $p->id,
                    'ten_san_pham' => $p->ten_san_pham,
                    'gia_ban' => (float) $p->gia_ban,
                    'gia_giam' => $p->gia_giam ? (float) $p->gia_giam : null,
                    'don_vi_tinh' => $p->don_vi_tinh,
                    'hinh_anh_chinh' => $p->hinh_anh_chinh,
                    'duong_dan_seo' => $p->duong_dan_seo,
                ])->values()->all(),
            ],
        ];
    }

    /**
     * Nhận diện và tư vấn sâu các danh mục thực phẩm người dùng muốn mua:
     * Thịt bò, Cá hồi, Thịt heo, Tôm mực, Rau củ, Trái cây...
     *
     * @return array{content: string, cards: array<string, mixed>}|null
     */
    protected function handleProductCategoryIntent(string $lower): ?array
    {
        // 1. THỊT BÒ
        if ($this->containsAny($lower, ['thịt bò', 'mua bò', 'thăn bò', 'ba chỉ bò', 'bò úc', 'bò mỹ', 'bò tươi'])) {
            $products = $this->searchRelevantProducts('thịt bò', 4);
            $content = "🥩 **Dạ tại siêu thị Fresh Food hiện đang có sẵn 2 dòng thịt bò nhập khẩu tươi ngon thượng hạng, đạt chuẩn kiểm dịch nghiêm ngặt:**\n\n"
                ."1. **Thăn Bò Úc Black Angus Cắt Dày Steak** (`360.000đ/Khay 500g`):\n"
                ."   * *Đặc điểm:* Bò Úc ăn cỏ tự nhiên, thớ thịt dày mọng nước, vân mỡ lý tưởng đan xen.\n"
                ."   * *Món ngon gợi ý:* Cực kỳ xuất sắc cho món **Bò Bít Tết (Beefsteak)** áp chảo bơ tỏi, nướng đá hoặc bò lúc lắc.\n\n"
                ."2. **Ba Chỉ Bò Mỹ Cắt Cuộn Nhúng Lẩu / Nướng** (`220.000đ/Khay 500g`):\n"
                ."   * *Đặc điểm:* Cắt lát cuộn tròn đều đẹp, tỷ lệ nạc mỡ đan xen mềm béo ngọt thịt.\n"
                ."   * *Món ngon gợi ý:* Chuyên dùng cho **nhúng lẩu chua cay**, cuốn nấm kim châm nướng BBQ hoặc xào bông cải xanh.\n\n"
                ."👉 Bạn có thể xem chi tiết và bấm **'Mua ngay'** vào giỏ hàng ngay bên dưới nhé!\n"
                .'*(Nếu bạn dự định làm món gì như Bò Bít Tết hay Lẩu Bò, cứ nhắn tên món em sẽ hướng dẫn trọn gói nguyên liệu và công thức ạ!)* 😊';

            return [
                'content' => $content,
                'cards' => [
                    'type' => 'products',
                    'title' => 'Thịt bò tươi ngon có sẵn tại Fresh Food',
                    'items' => $products->map(fn ($p) => [
                        'id' => $p->id,
                        'ten_san_pham' => $p->ten_san_pham,
                        'gia_ban' => (float) $p->gia_ban,
                        'gia_giam' => $p->gia_giam ? (float) $p->gia_giam : null,
                        'don_vi_tinh' => $p->don_vi_tinh,
                        'hinh_anh_chinh' => $p->hinh_anh_chinh,
                        'duong_dan_seo' => $p->duong_dan_seo,
                    ])->values()->all(),
                ],
            ];
        }

        // 2. CÁ HỒI & HẢI SẢN
        if ($this->containsAny($lower, ['cá hồi', 'hải sản', 'tôm', 'mực', 'tôm sú'])) {
            $products = $this->searchRelevantProducts('cá hồi tôm mực', 4);
            $content = "🐟 **Dạ siêu thị Fresh Food luôn có sẵn nguồn hải sản tươi rói nhập mới mỗi ngày:**\n\n"
                ."* **Cá Hồi Nauy Tươi Phi Lê Sashimi Class** (`580.000đ/Khay 500g`): Nhập khẩu đường hàng không từ Nauy, đạt chuẩn ăn sống Sashimi, giàu Omega-3, thịt ngọt ngậy không hề tanh.\n"
                ."* **Tôm Sú Biển Cà Mau Tươi Sống (Size 20 con/kg)** (`320.000đ/Hộp 500g`): Tôm tự nhiên thịt chắc giòn ngọt sần sật, hoàn hảo cho món nướng bơ tỏi hoặc hấp bia sả.\n"
                ."* **Mực Ống Tươi Phan Thiết Câu Đêm** (`240.000đ/Khay 500g`): Mực ống dày mình, xào sa tế hay hấp gừng đều ngon ngọt.\n\n"
                ."👉 Bạn bấm **'Mua ngay'** bên dưới để được giao hỏa tốc đóng đá giữ lạnh trong 2 giờ nhé!";

            return [
                'content' => $content,
                'cards' => [
                    'type' => 'products',
                    'title' => 'Hải sản tươi sống có sẵn tại Fresh Food',
                    'items' => $products->map(fn ($p) => [
                        'id' => $p->id,
                        'ten_san_pham' => $p->ten_san_pham,
                        'gia_ban' => (float) $p->gia_ban,
                        'gia_giam' => $p->gia_giam ? (float) $p->gia_giam : null,
                        'don_vi_tinh' => $p->don_vi_tinh,
                        'hinh_anh_chinh' => $p->hinh_anh_chinh,
                        'duong_dan_seo' => $p->duong_dan_seo,
                    ])->values()->all(),
                ],
            ];
        }

        // 3. THỊT HEO & SƯỜN
        if ($this->containsAny($lower, ['thịt heo', 'thịt lợn', 'ba rọi', 'sườn non', 'sườn heo'])) {
            $products = $this->searchRelevantProducts('ba rọi sườn non', 3);
            $content = "🐖 **Dạ tại Fresh Food có sẵn các phần thịt heo tươi sạch VietGAP & Thảo Mộc mỗi sáng:**\n\n"
                ."* **Thịt Ba Rọi Heo Quế VietGAP Rút Sườn** (`175.000đ/Khay 500g`): Tỷ lệ nạc - mỡ cân đối, bì mỏng, thích hợp làm thịt kho tàu, luộc chấm mắm tôm hoặc bằm nấu canh.\n"
                ."* **Sườn Non Heo Thảo Mộc Thượng Hạng** (`195.000đ/Khay 500g`): Sườn dẻ nhiều nạc, thịt mềm ngọt tự nhiên, chuyên dùng rim chua ngọt, nướng BBQ hoặc nấu canh.\n\n"
                .'👉 Bạn chọn mua các sản phẩm thịt heo tươi sạch ngay bên dưới nhé!';

            return [
                'content' => $content,
                'cards' => [
                    'type' => 'products',
                    'title' => 'Thịt heo tươi sạch VietGAP',
                    'items' => $products->map(fn ($p) => [
                        'id' => $p->id,
                        'ten_san_pham' => $p->ten_san_pham,
                        'gia_ban' => (float) $p->gia_ban,
                        'gia_giam' => $p->gia_giam ? (float) $p->gia_giam : null,
                        'don_vi_tinh' => $p->don_vi_tinh,
                        'hinh_anh_chinh' => $p->hinh_anh_chinh,
                        'duong_dan_seo' => $p->duong_dan_seo,
                    ])->values()->all(),
                ],
            ];
        }

        // 4. RAU CỦ HỮU CƠ & ĐÀ LẠT
        if ($this->containsAny($lower, ['rau củ', 'rau sạch', 'rau hữu cơ', 'bông cải', 'cải bó xôi', 'cà chua', 'xà lách', 'nấm'])) {
            $products = $this->searchRelevantProducts('bông cải cải bó xôi cà chua xà lách', 4);
            $content = "🥬 **Dạ toàn bộ rau củ tại Fresh Food được thu hoạch trực tiếp từ nông trại Đà Lạt chuẩn Hữu Cơ & VietGAP mỗi sáng sớm:**\n\n"
                ."* **Bông Cải Xanh Đà Lạt** (`48.000đ/Bông 500g`): Giòn ngọt, rất giàu chất chống oxy hóa.\n"
                ."* **Cải Bó Xôi (Rau Chân Vịt) Hữu Cơ** (`38.000đ/Bó 350g`): Bổ máu, thanh nhiệt, tuyệt vời để nấu canh hoặc ép nước.\n"
                ."* **Cà Chua Bi Cherry Đỏ Ngọt** (`45.000đ/Hộp 400g`): Căng mọng, ngọt đậm tự nhiên ăn sống hoặc trộn salad.\n"
                ."* **Nấm Đùi Gà Tươi Cao Cấp** (`35.000đ/Khay 300g`): Thân giòn ngọt sần sật, xào hoặc nướng lẩu đều ngon.\n\n"
                ."👉 Em gửi danh sách rau củ tươi sạch bên dưới để bạn bấm **'Mua ngay'** vào giỏ hàng nhé!";

            return [
                'content' => $content,
                'cards' => [
                    'type' => 'products',
                    'title' => 'Rau củ hữu cơ Đà Lạt tươi sạch',
                    'items' => $products->map(fn ($p) => [
                        'id' => $p->id,
                        'ten_san_pham' => $p->ten_san_pham,
                        'gia_ban' => (float) $p->gia_ban,
                        'gia_giam' => $p->gia_giam ? (float) $p->gia_giam : null,
                        'don_vi_tinh' => $p->don_vi_tinh,
                        'hinh_anh_chinh' => $p->hinh_anh_chinh,
                        'duong_dan_seo' => $p->duong_dan_seo,
                    ])->values()->all(),
                ],
            ];
        }

        // 5. TRÁI CÂY & HOA QUẢ
        if ($this->containsAny($lower, ['trái cây', 'hoa quả', 'táo', 'nho', 'dưa lưới', 'xoài'])) {
            $products = $this->searchRelevantProducts('táo nho dưa lưới xoài', 4);
            $content = "🍎 **Dạ Fresh Food luôn có sẵn các loại trái cây nhập khẩu thượng hạng và đặc sản vùng miền tươi ngon:**\n\n"
                ."* **Táo Envy New Zealand Size 35** (`165.000đ/Kg`): Giòn đanh, ngọt đậm, thơm đặc trưng.\n"
                ."* **Nho Mẫu Đơn Shine Muscat Hàn Quốc** (`450.000đ/Chùm 600g`): Quả to đều, không hạt, thơm ngát mùi sữa.\n"
                ."* **Dưa Lưới Huỳnh Long Ruột Cam** (`85.000đ/Kg`): Giòn ngọt thanh mát, giải nhiệt cực đã.\n"
                ."* **Xoài Cát Hòa Lộc Tiền Giang** (`95.000đ/Kg`): Vàng ươm, thơm lừng, thịt dẻo mịn không xơ.\n\n"
                ."👉 Bạn bấm **'Mua ngay'** bên dưới để thưởng thức hoa quả tươi ngon hôm nay nhé!";

            return [
                'content' => $content,
                'cards' => [
                    'type' => 'products',
                    'title' => 'Trái cây nhập khẩu & Đặc sản tươi ngọt',
                    'items' => $products->map(fn ($p) => [
                        'id' => $p->id,
                        'ten_san_pham' => $p->ten_san_pham,
                        'gia_ban' => (float) $p->gia_ban,
                        'gia_giam' => $p->gia_giam ? (float) $p->gia_giam : null,
                        'don_vi_tinh' => $p->don_vi_tinh,
                        'hinh_anh_chinh' => $p->hinh_anh_chinh,
                        'duong_dan_seo' => $p->duong_dan_seo,
                    ])->values()->all(),
                ],
            ];
        }

        return null;
    }

    /**
     * Tìm sản phẩm theo từ khóa trong tin nhắn một cách chính xác.
     *
     * @return Collection<int, SanPham>
     */
    protected function searchRelevantProducts(string $message, int $limit = 4)
    {
        $lower = mb_strtolower(trim($message));

        // Bản đồ từ khóa danh mục & cụm từ đặc trưng
        $phraseMap = [
            'thịt bò' => ['thăn bò', 'ba chỉ bò'],
            'bò úc' => ['thăn bò'],
            'bò mỹ' => ['ba chỉ bò'],
            'bò' => ['thăn bò', 'ba chỉ bò'],
            'thăn bò' => ['thăn bò'],
            'ba chỉ bò' => ['ba chỉ bò'],
            'cá hồi' => ['cá hồi'],
            'tôm' => ['tôm sú'],
            'mực' => ['mực ống'],
            'thịt heo' => ['ba rọi', 'sườn non'],
            'thịt lợn' => ['ba rọi', 'sườn non'],
            'ba rọi' => ['ba rọi'],
            'sườn non' => ['sườn non'],
            'sườn heo' => ['sườn non'],
            'gà' => ['gà mái', 'trứng gà'],
            'trứng' => ['trứng gà'],
            'bông cải' => ['bông cải xanh'],
            'súp lơ' => ['bông cải xanh'],
            'cải bó xôi' => ['cải bó xôi'],
            'rau cải' => ['cải bó xôi', 'xà lách', 'bông cải xanh'],
            'rau củ' => ['cải bó xôi', 'xà lách', 'bông cải xanh', 'cà chua bi'],
            'cà chua' => ['cà chua bi'],
            'ớt chuông' => ['ớt chuông'],
            'nấm' => ['nấm đùi gà'],
            'táo' => ['táo envy'],
            'nho' => ['nho mẫu đơn'],
            'dưa lưới' => ['dưa lưới'],
            'xoài' => ['xoài cát'],
            'trái cây' => ['táo envy', 'nho mẫu đơn', 'dưa lưới', 'xoài cát'],
            'hoa quả' => ['táo envy', 'nho mẫu đơn', 'dưa lưới', 'xoài cát'],
            'sữa' => ['dalatmilk', 'sữa tươi'],
            'bơ' => ['bơ sáp', 'bơ nhạt'],
            'phô mai' => ['phô mai'],
            'dầu oliu' => ['dầu oliu'],
            'tỏi' => ['tỏi lý sơn'],
            'gạo' => ['st25', 'gạo'],
        ];

        $matchedPhrases = [];
        foreach ($phraseMap as $key => $targets) {
            if (mb_strpos($lower, $key) !== false) {
                $matchedPhrases = array_merge($matchedPhrases, $targets);
            }
        }

        $allProducts = SanPham::where('trang_thai', 1)->get();

        if (! empty($matchedPhrases)) {
            $filtered = $allProducts->filter(function ($p) use ($matchedPhrases) {
                $pName = mb_strtolower($p->ten_san_pham);
                foreach ($matchedPhrases as $term) {
                    if (mb_stripos($pName, $term) !== false) {
                        return true;
                    }
                }

                return false;
            });

            if ($filtered->isNotEmpty()) {
                return $filtered->take($limit)->values();
            }
        }

        // Tách từ khóa loại trừ stop-words
        $words = preg_split('/\s+/', $lower);
        $stopWords = ['tôi', 'muốn', 'mua', 'có', 'bán', 'không', 'giá', 'nhiêu', 'bao', 'nào', 'cho', 'xin', 'hỏi', 'cần', 'ở', 'tại', 'nay', 'hôm', 'nay'];
        $keywords = array_values(array_filter($words, fn ($w) => mb_strlen($w) >= 2 && ! in_array($w, $stopWords, true)));

        if (empty($keywords)) {
            return collect();
        }

        $filtered = $allProducts->filter(function ($p) use ($keywords) {
            $pName = mb_strtolower($p->ten_san_pham);
            foreach ($keywords as $kw) {
                if (mb_stripos($pName, $kw) !== false) {
                    return true;
                }
            }

            return false;
        });

        return $filtered->take($limit)->values();
    }

    /**
     * Định dạng kết quả tra cứu đơn hàng.
     *
     * @return array{content: string, cards: array<string, mixed>}
     */
    protected function formatOrderTrackingReply(DonHang $order): array
    {
        $statusLabels = [
            'cho_xac_nhan' => '⏳ Chờ xác nhận',
            'dang_chuan_bi' => '👨‍🍳 Đang chuẩn bị hàng tươi',
            'dang_giao' => '🛵 Đang giao hỏa tốc',
            'da_giao' => '✅ Đã giao thành công',
            'da_huy' => '❌ Đã hủy',
        ];

        $paymentLabels = [
            'chua_thanh_toan' => 'Chưa thanh toán',
            'da_thanh_toan' => 'Đã thanh toán',
            'hoan_tien' => 'Đã hoàn tiền',
        ];

        $statusText = $statusLabels[$order->trang_thai_don_hang] ?? $order->trang_thai_don_hang;
        $paymentText = $paymentLabels[$order->trang_thai_thanh_toan] ?? $order->trang_thai_thanh_toan;
        $totalFormatted = number_format((float) $order->tong_thanhtoan, 0, ',', '.').'đ';

        $content = "📦 **Thông tin tra cứu đơn hàng #{$order->ma_don_hang}:**\n\n"
            ."- **Trạng thái:** {$statusText}\n"
            ."- **Thanh toán:** {$paymentText}\n"
            ."- **Tổng tiền:** {$totalFormatted}\n"
            ."- **Người nhận:** {$order->ten_nguoi_nhan} ({$order->so_dien_thoai_nhan})\n"
            ."- **Địa chỉ giao:** {$order->dia_chi_giao}\n"
            .'- **Thời gian đặt:** '.$order->created_at->format('H:i d/m/Y');

        return [
            'content' => $content,
            'cards' => [
                'type' => 'order',
                'order_code' => $order->ma_don_hang,
                'status' => $order->trang_thai_don_hang,
                'status_label' => $statusText,
                'payment_status' => $paymentText,
                'total' => (float) $order->tong_thanhtoan,
                'total_formatted' => $totalFormatted,
            ],
        ];
    }

    protected function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
