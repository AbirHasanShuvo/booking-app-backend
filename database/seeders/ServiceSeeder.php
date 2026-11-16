<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['Haircut & Styling', 1, 25, 45, true],
            ['Facial Treatment', 1, 40, 60, false],
            ['Home Deep Cleaning', 2, 80, 180, true],
            ['Sofa Cleaning', 2, 35, 90, false],
            ['Full Body Massage', 3, 60, 60, true],
            ['Yoga Session', 3, 20, 60, false],
            ['AC Repair', 4, 30, 90, true],
            ['Plumbing Fix', 4, 25, 60, false],
        ];

        foreach ($items as [$name, $cat, $price, $mins, $featured]) {
            \App\Models\Service::create([
                'category_id'      => $cat,
                'name'             => $name,
                'description'      => "Professional {$name} service by trained experts. Book a slot that suits you.",
                'price'            => $price,
                'duration_minutes' => $mins,
                'image_url'        => 'https://picsum.photos/seed/' . \Illuminate\Support\Str::slug($name) . '/600/400',
                'is_featured'      => $featured,
            ]);
        }
    }
}
