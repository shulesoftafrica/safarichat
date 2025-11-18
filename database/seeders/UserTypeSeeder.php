<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserType;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userTypes = [
            [
                'name' => 'Small Business',
                'description' => 'Small businesses with 1-50 employees'
            ],
            [
                'name' => 'Medium Business',
                'description' => 'Medium-sized businesses with 51-250 employees'
            ],
            [
                'name' => 'Large Enterprise',
                'description' => 'Large enterprises with 250+ employees'
            ],
            [
                'name' => 'Retail',
                'description' => 'Retail and e-commerce businesses'
            ],
            [
                'name' => 'Healthcare',
                'description' => 'Healthcare and medical services'
            ],
            [
                'name' => 'Technology',
                'description' => 'Technology and software companies'
            ],
            [
                'name' => 'Education',
                'description' => 'Educational institutions and services'
            ],
            [
                'name' => 'Finance',
                'description' => 'Financial services and banking'
            ],
            [
                'name' => 'Manufacturing',
                'description' => 'Manufacturing and industrial businesses'
            ],
            [
                'name' => 'Professional Services',
                'description' => 'Consulting, legal, and professional services'
            ]
        ];

        foreach ($userTypes as $type) {
            UserType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
