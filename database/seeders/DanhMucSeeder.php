<?php

namespace Database\Seeders;

use App\Models\DanhMuc;
use Illuminate\Database\Seeder;

class DanhMucSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'ten_danh_muc' => 'Rau củ hữu cơ',
                'duong_dan_seo' => 'rau-cu-huu-co',
                'hinh_anh' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=600&auto=format&fit=crop',
                'children' => [
                    [
                        'ten_danh_muc' => 'Rau lá xanh hữu cơ',
                        'duong_dan_seo' => 'rau-la-xanh-huu-co',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=600&auto=format&fit=crop',
                    ],
                    [
                        'ten_danh_muc' => 'Củ quả tươi Đà Lạt',
                        'duong_dan_seo' => 'cu-qua-tuoi-da-lat',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1590779033100-9f60a05a013d?w=600&auto=format&fit=crop',
                    ],
                    [
                        'ten_danh_muc' => 'Nấm tươi các loại',
                        'duong_dan_seo' => 'nam-tuoi-cac-loai',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=600&auto=format&fit=crop',
                    ],
                ],
            ],
            [
                'ten_danh_muc' => 'Trái cây tươi ngon',
                'duong_dan_seo' => 'trai-cay-tuoi-ngon',
                'hinh_anh' => 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=600&auto=format&fit=crop',
                'children' => [
                    [
                        'ten_danh_muc' => 'Trái cây nhiệt đới Việt Nam',
                        'duong_dan_seo' => 'trai-cay-nhiet-doi-viet-nam',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1550258987-190a2d41a8ba?w=600&auto=format&fit=crop',
                    ],
                    [
                        'ten_danh_muc' => 'Trái cây nhập khẩu cao cấp',
                        'duong_dan_seo' => 'trai-cay-nhap-khau-cao-cap',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=600&auto=format&fit=crop',
                    ],
                ],
            ],
            [
                'ten_danh_muc' => 'Thịt tươi & Trứng sạch',
                'duong_dan_seo' => 'thit-tuoi-trung-sach',
                'hinh_anh' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=600&auto=format&fit=crop',
                'children' => [
                    [
                        'ten_danh_muc' => 'Thịt heo sinh học VietGAP',
                        'duong_dan_seo' => 'thit-heo-sinh-hoc-vietgap',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1602470520998-f4a52199a3d6?w=600&auto=format&fit=crop',
                    ],
                    [
                        'ten_danh_muc' => 'Thịt bò Úc - Mỹ thượng hạng',
                        'duong_dan_seo' => 'thit-bo-uc-my-thuong-hang',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1558030006-450675393462?w=600&auto=format&fit=crop',
                    ],
                    [
                        'ten_danh_muc' => 'Thịt gia cầm thả vườn',
                        'duong_dan_seo' => 'thit-gia-cam-tha-vuon',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1587593810167-a84920ea0781?w=600&auto=format&fit=crop',
                    ],
                    [
                        'ten_danh_muc' => 'Trứng gà, trứng vịt sạch',
                        'duong_dan_seo' => 'trung-ga-trung-vit-sach',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1506976785307-8732e854ad03?w=600&auto=format&fit=crop',
                    ],
                ],
            ],
            [
                'ten_danh_muc' => 'Thủy hải sản tươi sống',
                'duong_dan_seo' => 'thuy-hai-san-tuoi-song',
                'hinh_anh' => 'https://images.unsplash.com/photo-1534939561126-855b8675edd7?w=600&auto=format&fit=crop',
                'children' => [
                    [
                        'ten_danh_muc' => 'Cá hồi Nauy tươi phi lê',
                        'duong_dan_seo' => 'ca-hoi-nauy-tuoi-phi-le',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=600&auto=format&fit=crop',
                    ],
                    [
                        'ten_danh_muc' => 'Tôm, Cua, Mực tươi',
                        'duong_dan_seo' => 'tom-cua-muc-tuoi',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1565680018434-b513d5e5fd47?w=600&auto=format&fit=crop',
                    ],
                ],
            ],
            [
                'ten_danh_muc' => 'Sữa bơ & Thực phẩm chế biến',
                'duong_dan_seo' => 'sua-bo-thuc-pham-che-bien',
                'hinh_anh' => 'https://images.unsplash.com/photo-1528750997573-59b89d56f4f7?w=600&auto=format&fit=crop',
                'children' => [
                    [
                        'ten_danh_muc' => 'Sữa tươi thanh trùng & Sữa chua',
                        'duong_dan_seo' => 'sua-tuoi-thanh-trung-sua-chua',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=600&auto=format&fit=crop',
                    ],
                    [
                        'ten_danh_muc' => 'Bơ & Phô mai nhập khẩu',
                        'duong_dan_seo' => 'bo-pho-mai-nhap-khau',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?w=600&auto=format&fit=crop',
                    ],
                ],
            ],
            [
                'ten_danh_muc' => 'Gia vị & Nông sản khô',
                'duong_dan_seo' => 'gia-vi-nong-san-kho',
                'hinh_anh' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=600&auto=format&fit=crop',
                'children' => [
                    [
                        'ten_danh_muc' => 'Gia vị tự nhiên hữu cơ',
                        'duong_dan_seo' => 'gia-vi-tu-nhien-huu-co',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1532336414038-cf19250c5757?w=600&auto=format&fit=crop',
                    ],
                    [
                        'ten_danh_muc' => 'Hạt dinh dưỡng & Gạo sạch',
                        'duong_dan_seo' => 'hat-dinh-duong-gao-sach',
                        'hinh_anh' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600&auto=format&fit=crop',
                    ],
                ],
            ],
        ];

        foreach ($categories as $cat) {
            $parent = DanhMuc::updateOrCreate(
                ['duong_dan_seo' => $cat['duong_dan_seo']],
                [
                    'ten_danh_muc' => $cat['ten_danh_muc'],
                    'hinh_anh' => $cat['hinh_anh'],
                    'danh_muc_cha_id' => null,
                ]
            );

            if (isset($cat['children'])) {
                foreach ($cat['children'] as $child) {
                    DanhMuc::updateOrCreate(
                        ['duong_dan_seo' => $child['duong_dan_seo']],
                        [
                            'ten_danh_muc' => $child['ten_danh_muc'],
                            'hinh_anh' => $child['hinh_anh'],
                            'danh_muc_cha_id' => $parent->id,
                        ]
                    );
                }
            }
        }
    }
}
