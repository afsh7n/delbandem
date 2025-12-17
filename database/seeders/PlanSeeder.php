<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'پلن یک ماهه',
                'duration_days' => 30,
                'price' => 99000,
                'description' => 'دسترسی به تمام محتوای برنامه به مدت یک ماه',
                'is_active' => true,
            ],
            [
                'name' => 'پلن سه ماهه',
                'duration_days' => 90,
                'price' => 249000,
                'description' => 'دسترسی به تمام محتوای برنامه به مدت سه ماه با تخفیف ویژه',
                'is_active' => true,
            ],
            [
                'name' => 'پلن شش ماهه',
                'duration_days' => 180,
                'price' => 449000,
                'description' => 'دسترسی به تمام محتوای برنامه به مدت شش ماه با تخفیف عالی',
                'is_active' => true,
            ],
            [
                'name' => 'پلن یک ساله',
                'duration_days' => 365,
                'price' => 799000,
                'description' => 'دسترسی به تمام محتوای برنامه به مدت یک سال کامل با بیشترین تخفیف',
                'is_active' => true,
            ],
            [
                'name' => 'پلن آزمایشی',
                'duration_days' => 7,
                'price' => 0,
                'description' => 'دسترسی رایگان هفت روزه برای آزمایش امکانات برنامه',
                'is_active' => false, // غیرفعال - فقط برای موارد خاص
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}

