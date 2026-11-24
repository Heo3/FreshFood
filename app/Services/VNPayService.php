<?php

namespace App\Services;

use App\Models\DonHang;
use Illuminate\Support\Carbon;

class VNPayService
{
    protected string $tmnCode;

    protected string $hashSecret;

    protected string $url;

    protected ?string $returnUrl;

    public function __construct(
        ?string $tmnCode = null,
        ?string $hashSecret = null,
        ?string $url = null,
        ?string $returnUrl = null,
    ) {
        $this->tmnCode = $tmnCode ?? (string) config('services.vnpay.tmn_code', '2QXUI4B4');
        $this->hashSecret = $hashSecret ?? (string) config('services.vnpay.hash_secret', 'secret');
        $this->url = $url ?? (string) config('services.vnpay.url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        $this->returnUrl = $returnUrl ?? config('services.vnpay.return_url');
    }

    /**
     * Tạo URL điều hướng sang cổng thanh toán VNPay Sandbox.
     */
    public function createPaymentUrl(DonHang $order, ?string $ipAddress = null): string
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $vnpAmount = (int) round((float) $order->tong_thanhtoan * 100);
        $vnpCreateDate = $now->format('YmdHis');
        $vnpExpireDate = $now->copy()->addMinutes(15)->format('YmdHis');
        $returnUrl = $this->returnUrl ?: route('checkout.vnpay-callback');

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => $vnpAmount,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => $vnpCreateDate,
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $ipAddress ?: '127.0.0.1',
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don hang FreshFood #'.$order->ma_don_hang,
            'vnp_OrderType' => 'billpayment',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_TxnRef' => (string) $order->ma_don_hang,
            'vnp_ExpireDate' => $vnpExpireDate,
        ];

        ksort($inputData);

        $query = '';
        $i = 0;
        $hashdata = '';
        foreach ($inputData as $key => $value) {
            if ($value !== '' && $value !== null) {
                if ($i === 1) {
                    $query .= '&'.urlencode($key).'='.urlencode((string) $value);
                    $hashdata .= '&'.urlencode($key).'='.urlencode((string) $value);
                } else {
                    $query .= urlencode($key).'='.urlencode((string) $value);
                    $hashdata .= urlencode($key).'='.urlencode((string) $value);
                    $i = 1;
                }
            }
        }

        $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->hashSecret);

        return $this->url.'?'.$query.'&vnp_SecureHash='.$vnpSecureHash;
    }

    /**
     * Xác thực chữ ký và kết quả phản hồi từ VNPay.
     *
     * @param  array<string, mixed>  $params
     * @return array{is_valid: bool, success: bool, order_code: string|null, response_code: string|null, transaction_no: string|null, message: string}
     */
    public function verifyReturn(array $params): array
    {
        $vnpSecureHash = $params['vnp_SecureHash'] ?? '';
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        ksort($params);

        $i = 0;
        $hashData = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                if ($i === 1) {
                    $hashData .= '&'.urlencode($key).'='.urlencode((string) $value);
                } else {
                    $hashData .= urlencode($key).'='.urlencode((string) $value);
                    $i = 1;
                }
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $this->hashSecret);
        $isValid = hash_equals($secureHash, (string) $vnpSecureHash);

        $responseCode = $params['vnp_ResponseCode'] ?? null;
        $isSuccess = $isValid && ($responseCode === '00');
        $orderCode = $params['vnp_TxnRef'] ?? null;
        $transactionNo = $params['vnp_TransactionNo'] ?? null;

        $message = match ($responseCode) {
            '00' => 'Giao dịch thanh toán VNPay thành công.',
            '07' => 'Trừ tiền thành công nhưng giao dịch bị nghi ngờ (liên hệ VNPay).',
            '09' => 'Giao dịch không thành công do: Thẻ/Tài khoản chưa đăng ký dịch vụ InternetBanking.',
            '10' => 'Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần.',
            '11' => 'Giao dịch không thành công do: Đã hết hạn chờ thanh toán.',
            '12' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa.',
            '13' => 'Giao dịch không thành công do nhập sai mật khẩu xác thực OTP.',
            '24' => 'Khách hàng đã hủy giao dịch thanh toán.',
            '51' => 'Tài khoản không đủ số dư để thanh toán.',
            '65' => 'Tài khoản đã vượt quá hạn mức giao dịch trong ngày.',
            '75' => 'Ngân hàng thanh toán đang bảo trì.',
            '79' => 'Giao dịch không thành công do: Khách hàng nhập sai mật khẩu thanh toán quá số lần quy định.',
            default => 'Giao dịch thanh toán chưa hoàn tất hoặc bị hủy.',
        };

        return [
            'is_valid' => $isValid,
            'success' => $isSuccess,
            'order_code' => $orderCode,
            'response_code' => $responseCode,
            'transaction_no' => $transactionNo,
            'message' => $isValid ? $message : 'Chữ ký bảo mật không hợp lệ (Dữ liệu bị can thiệp).',
        ];
    }
}
