<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    /**
     * Kiểm tra xem cấu hình Cloudinary đã sẵn sàng chưa.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('cloudinary.cloud_name'));
    }

    /**
     * Tải ảnh lên Cloudinary và trả về đường dẫn an toàn (HTTPS secure_url).
     *
     * @param  UploadedFile|string  $file  Đối tượng UploadedFile, đường dẫn file local hoặc URL ảnh online
     * @param  string  $folder  Thư mục lưu trữ trên Cloudinary (mặc định: 'products')
     * @return string|null Đường dẫn ảnh trên Cloudinary hoặc null nếu thất bại
     */
    public function upload(UploadedFile|string $file, string $folder = 'products'): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cloudName = config('cloudinary.cloud_name');
        $uploadPreset = config('cloudinary.upload_preset');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";

        try {
            $http = Http::timeout(30);

            // 1. Ưu tiên sử dụng upload_preset nếu có
            if ($uploadPreset) {
                $payload = [
                    'upload_preset' => $uploadPreset,
                    'folder' => $folder,
                ];

                if ($file instanceof UploadedFile) {
                    $response = $http->attach(
                        'file',
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName()
                    )->post($endpoint, $payload);
                } elseif (filter_var($file, FILTER_VALIDATE_URL)) {
                    $payload['file'] = $file;
                    $response = $http->post($endpoint, $payload);
                } elseif (file_exists($file)) {
                    $response = $http->attach(
                        'file',
                        file_get_contents($file),
                        basename($file)
                    )->post($endpoint, $payload);
                } else {
                    return null;
                }
            } elseif ($apiKey && $apiSecret) {
                // 2. Ký request (signed upload) nếu không có preset nhưng có API Key & Secret
                $timestamp = time();
                $paramsToSign = [
                    'folder' => $folder,
                    'timestamp' => $timestamp,
                ];
                ksort($paramsToSign);

                $paramString = urldecode(http_build_query($paramsToSign));
                $signature = sha1($paramString.$apiSecret);

                $payload = [
                    'api_key' => $apiKey,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                    'folder' => $folder,
                ];

                if ($file instanceof UploadedFile) {
                    $response = $http->attach(
                        'file',
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName()
                    )->post($endpoint, $payload);
                } elseif (filter_var($file, FILTER_VALIDATE_URL)) {
                    $payload['file'] = $file;
                    $response = $http->post($endpoint, $payload);
                } elseif (file_exists($file)) {
                    $response = $http->attach(
                        'file',
                        file_get_contents($file),
                        basename($file)
                    )->post($endpoint, $payload);
                } else {
                    return null;
                }
            } else {
                Log::warning('[CloudinaryService] Thiếu thông tin upload_preset hoặc api_key/api_secret.');

                return null;
            }

            if ($response->successful()) {
                return $response->json('secure_url') ?? $response->json('url');
            }

            Log::error('[CloudinaryService] Lỗi tải ảnh lên Cloudinary', [
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('[CloudinaryService] Ngoại lệ khi tải ảnh lên: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Xóa ảnh khỏi Cloudinary qua Public ID.
     */
    public function delete(string $publicId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        if (! $apiKey || ! $apiSecret) {
            return false;
        }

        $timestamp = time();
        $paramsToSign = [
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];
        ksort($paramsToSign);

        $paramString = urldecode(http_build_query($paramsToSign));
        $signature = sha1($paramString.$apiSecret);

        $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy";

        try {
            $response = Http::timeout(15)->post($endpoint, [
                'public_id' => $publicId,
                'timestamp' => $timestamp,
                'api_key' => $apiKey,
                'signature' => $signature,
            ]);

            return $response->successful() && $response->json('result') === 'ok';
        } catch (\Throwable $e) {
            Log::warning('[CloudinaryService] Không thể xóa ảnh: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Trích xuất public_id từ đường dẫn Cloudinary và tiến hành xóa.
     */
    public function deleteByUrl(string $url): bool
    {
        $publicId = $this->extractPublicId($url);
        if (! $publicId) {
            return false;
        }

        return $this->delete($publicId);
    }

    /**
     * Lấy public_id từ URL Cloudinary.
     */
    public function extractPublicId(string $url): ?string
    {
        if (! str_contains($url, 'cloudinary.com')) {
            return null;
        }

        // Khớp dạng: /upload/(v12345/)?(folder/filename).ext
        if (preg_match('#/image/upload/(?:v\d+/)?(.+?)(?:\.[a-zA-Z0-9]+)?$#', $url, $matches)) {
            return $matches[1] ?? null;
        }

        return null;
    }
}
