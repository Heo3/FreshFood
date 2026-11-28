<?php

use App\Services\CloudinaryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test('cloudinary service correctly determines if it is configured', function () {
    $service = new CloudinaryService;

    Config::set('cloudinary.cloud_name', null);
    expect($service->isConfigured())->toBeFalse();

    Config::set('cloudinary.cloud_name', 'test_cloud');
    expect($service->isConfigured())->toBeTrue();
});

test('cloudinary service uploads file successfully with preset', function () {
    Config::set('cloudinary.cloud_name', 'demo_cloud');
    Config::set('cloudinary.upload_preset', 'freshfood');

    Http::fake([
        'https://api.cloudinary.com/v1_1/demo_cloud/image/upload' => Http::response([
            'secure_url' => 'https://res.cloudinary.com/demo_cloud/image/upload/v12345/products/sample.jpg',
            'public_id' => 'products/sample',
        ], 200),
    ]);

    $service = new CloudinaryService;
    $file = UploadedFile::fake()->create('test_product.jpg', 100, 'image/jpeg');

    $url = $service->upload($file, 'products');

    expect($url)->toBe('https://res.cloudinary.com/demo_cloud/image/upload/v12345/products/sample.jpg');
});

test('cloudinary service uploads remote url successfully', function () {
    Config::set('cloudinary.cloud_name', 'demo_cloud');
    Config::set('cloudinary.upload_preset', 'freshfood');

    Http::fake([
        'https://api.cloudinary.com/v1_1/demo_cloud/image/upload' => Http::response([
            'secure_url' => 'https://res.cloudinary.com/demo_cloud/image/upload/v12345/categories/cat.jpg',
            'public_id' => 'categories/cat',
        ], 200),
    ]);

    $service = new CloudinaryService;
    $remoteUrl = 'https://images.unsplash.com/photo-1540420773420-3366772f4999';

    $url = $service->upload($remoteUrl, 'categories');

    expect($url)->toBe('https://res.cloudinary.com/demo_cloud/image/upload/v12345/categories/cat.jpg');
});

test('cloudinary service handles api errors gracefully and returns null', function () {
    Config::set('cloudinary.cloud_name', 'demo_cloud');
    Config::set('cloudinary.upload_preset', 'freshfood');

    Http::fake([
        'https://api.cloudinary.com/v1_1/demo_cloud/image/upload' => Http::response([
            'error' => ['message' => 'Upload preset not found'],
        ], 400),
    ]);

    $service = new CloudinaryService;
    $file = UploadedFile::fake()->create('error.jpg', 100, 'image/jpeg');

    $url = $service->upload($file, 'products');

    expect($url)->toBeNull();
});

test('cloudinary service extracts public id and deletes image', function () {
    Config::set('cloudinary.cloud_name', 'demo_cloud');
    Config::set('cloudinary.api_key', '123456');
    Config::set('cloudinary.api_secret', 'secret');

    Http::fake([
        'https://api.cloudinary.com/v1_1/demo_cloud/image/destroy' => Http::response([
            'result' => 'ok',
        ], 200),
    ]);

    $service = new CloudinaryService;
    $cloudinaryUrl = 'https://res.cloudinary.com/demo_cloud/image/upload/v1795846263/products/sample_img.jpg';

    expect($service->extractPublicId($cloudinaryUrl))->toBe('products/sample_img');
    expect($service->deleteByUrl($cloudinaryUrl))->toBeTrue();
});
