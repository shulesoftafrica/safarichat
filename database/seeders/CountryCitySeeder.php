<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryCitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * NOTE: This seeder works with the existing countries table structure where:
     * - 'name' column contains phone codes (e.g., "255", "254")
     * - 'dialling_code' column contains country names (e.g., "Tanzania", "Kenya")
     * 
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting country and city seeding...');
        
        // Map of phone code => [iso_code, cities]
        $phoneCodeMapping = [
            // East Africa (Priority regions)
            '255' => [
                'iso_code' => 'TZ',
                'cities' => [
                    ['name' => 'Dar es Salaam', 'slug' => 'dar-es-salaam', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Dodoma', 'slug' => 'dodoma', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Arusha', 'slug' => 'arusha', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Mwanza', 'slug' => 'mwanza', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Mbeya', 'slug' => 'mbeya', 'is_major' => true, 'sort_order' => 5],
                    ['name' => 'Morogoro', 'slug' => 'morogoro', 'is_major' => true, 'sort_order' => 6],
                    ['name' => 'Tanga', 'slug' => 'tanga', 'is_major' => true, 'sort_order' => 7],
                    ['name' => 'Kilimanjaro', 'slug' => 'kilimanjaro', 'is_major' => true, 'sort_order' => 8],
                    ['name' => 'Tabora', 'slug' => 'tabora', 'is_major' => false, 'sort_order' => 9],
                    ['name' => 'Zanzibar', 'slug' => 'zanzibar', 'is_major' => true, 'sort_order' => 10],
                    ['name' => 'Kigoma', 'slug' => 'kigoma', 'is_major' => false, 'sort_order' => 11],
                    ['name' => 'Mtwara', 'slug' => 'mtwara', 'is_major' => false, 'sort_order' => 12],
                    ['name' => 'Iringa', 'slug' => 'iringa', 'is_major' => false, 'sort_order' => 13],
                    ['name' => 'Shinyanga', 'slug' => 'shinyanga', 'is_major' => false, 'sort_order' => 14],
                    ['name' => 'Other Region', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '254' => [
                'iso_code' => 'KE',
                'cities' => [
                    ['name' => 'Nairobi', 'slug' => 'nairobi', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Mombasa', 'slug' => 'mombasa', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Kisumu', 'slug' => 'kisumu', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Nakuru', 'slug' => 'nakuru', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Eldoret', 'slug' => 'eldoret', 'is_major' => true, 'sort_order' => 5],
                    ['name' => 'Thika', 'slug' => 'thika', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Malindi', 'slug' => 'malindi', 'is_major' => false, 'sort_order' => 7],
                    ['name' => 'Kitale', 'slug' => 'kitale', 'is_major' => false, 'sort_order' => 8],
                    ['name' => 'Garissa', 'slug' => 'garissa', 'is_major' => false, 'sort_order' => 9],
                    ['name' => 'Kakamega', 'slug' => 'kakamega', 'is_major' => false, 'sort_order' => 10],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '256' => [
                'iso_code' => 'UG',
                'cities' => [
                    ['name' => 'Kampala', 'slug' => 'kampala', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Entebbe', 'slug' => 'entebbe', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Gulu', 'slug' => 'gulu', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Jinja', 'slug' => 'jinja', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Mbarara', 'slug' => 'mbarara', 'is_major' => true, 'sort_order' => 5],
                    ['name' => 'Mbale', 'slug' => 'mbale', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Lira', 'slug' => 'lira', 'is_major' => false, 'sort_order' => 7],
                    ['name' => 'Fort Portal', 'slug' => 'fort-portal', 'is_major' => false, 'sort_order' => 8],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '250' => [
                'iso_code' => 'RW',
                'cities' => [
                    ['name' => 'Kigali', 'slug' => 'kigali', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Butare', 'slug' => 'butare', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Gitarama', 'slug' => 'gitarama', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Ruhengeri', 'slug' => 'ruhengeri', 'is_major' => false, 'sort_order' => 4],
                    ['name' => 'Gisenyi', 'slug' => 'gisenyi', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '257' => [
                'iso_code' => 'BI',
                'cities' => [
                    ['name' => 'Bujumbura', 'slug' => 'bujumbura', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Gitega', 'slug' => 'gitega', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Muyinga', 'slug' => 'muyinga', 'is_major' => false, 'sort_order' => 3],
                    ['name' => 'Ngozi', 'slug' => 'ngozi', 'is_major' => false, 'sort_order' => 4],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '27' => [
                'iso_code' => 'ZA',
                'cities' => [
                    ['name' => 'Johannesburg', 'slug' => 'johannesburg', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Cape Town', 'slug' => 'cape-town', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Durban', 'slug' => 'durban', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Pretoria', 'slug' => 'pretoria', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Port Elizabeth', 'slug' => 'port-elizabeth', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '234' => [
                'iso_code' => 'NG',
                'cities' => [
                    ['name' => 'Lagos', 'slug' => 'lagos', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Abuja', 'slug' => 'abuja', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Kano', 'slug' => 'kano', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Ibadan', 'slug' => 'ibadan', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Port Harcourt', 'slug' => 'port-harcourt', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '233' => [
                'iso_code' => 'GH',
                'cities' => [
                    ['name' => 'Accra', 'slug' => 'accra', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Kumasi', 'slug' => 'kumasi', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Tamale', 'slug' => 'tamale', 'is_major' => false, 'sort_order' => 3],
                    ['name' => 'Sekondi-Takoradi', 'slug' => 'sekondi-takoradi', 'is_major' => false, 'sort_order' => 4],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            
            // Asia Pacific
            '62' => [
                'iso_code' => 'ID',
                'cities' => [
                    ['name' => 'Jakarta', 'slug' => 'jakarta', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Surabaya', 'slug' => 'surabaya', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Bandung', 'slug' => 'bandung', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Medan', 'slug' => 'medan', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Semarang', 'slug' => 'semarang', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Makassar', 'slug' => 'makassar', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '86' => [
                'iso_code' => 'CN',
                'cities' => [
                    ['name' => 'Beijing', 'slug' => 'beijing', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Shanghai', 'slug' => 'shanghai', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Guangzhou', 'slug' => 'guangzhou', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Shenzhen', 'slug' => 'shenzhen', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Chengdu', 'slug' => 'chengdu', 'is_major' => true, 'sort_order' => 5],
                    ['name' => 'Hangzhou', 'slug' => 'hangzhou', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Wuhan', 'slug' => 'wuhan', 'is_major' => false, 'sort_order' => 7],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '91' => [
                'iso_code' => 'IN',
                'cities' => [
                    ['name' => 'Mumbai', 'slug' => 'mumbai', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Delhi', 'slug' => 'delhi', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Bangalore', 'slug' => 'bangalore', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Hyderabad', 'slug' => 'hyderabad', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Chennai', 'slug' => 'chennai', 'is_major' => true, 'sort_order' => 5],
                    ['name' => 'Kolkata', 'slug' => 'kolkata', 'is_major' => true, 'sort_order' => 6],
                    ['name' => 'Pune', 'slug' => 'pune', 'is_major' => false, 'sort_order' => 7],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '92' => [
                'iso_code' => 'PK',
                'cities' => [
                    ['name' => 'Karachi', 'slug' => 'karachi', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Lahore', 'slug' => 'lahore', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Islamabad', 'slug' => 'islamabad', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Rawalpindi', 'slug' => 'rawalpindi', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Faisalabad', 'slug' => 'faisalabad', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Multan', 'slug' => 'multan', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '63' => [
                'iso_code' => 'PH',
                'cities' => [
                    ['name' => 'Manila', 'slug' => 'manila', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Quezon City', 'slug' => 'quezon-city', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Davao City', 'slug' => 'davao-city', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Cebu City', 'slug' => 'cebu-city', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Makati', 'slug' => 'makati', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            
            // Americas
            '1' => [
                'iso_code' => 'US',
                'cities' => [
                    ['name' => 'New York', 'slug' => 'new-york', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Los Angeles', 'slug' => 'los-angeles', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Chicago', 'slug' => 'chicago', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Houston', 'slug' => 'houston', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Phoenix', 'slug' => 'phoenix', 'is_major' => true, 'sort_order' => 5],
                    ['name' => 'Philadelphia', 'slug' => 'philadelphia', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'San Antonio', 'slug' => 'san-antonio', 'is_major' => false, 'sort_order' => 7],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '55' => [
                'iso_code' => 'BR',
                'cities' => [
                    ['name' => 'São Paulo', 'slug' => 'sao-paulo', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Rio de Janeiro', 'slug' => 'rio-de-janeiro', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Brasília', 'slug' => 'brasilia', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Salvador', 'slug' => 'salvador', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Fortaleza', 'slug' => 'fortaleza', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Belo Horizonte', 'slug' => 'belo-horizonte', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '54' => [
                'iso_code' => 'AR',
                'cities' => [
                    ['name' => 'Buenos Aires', 'slug' => 'buenos-aires', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Córdoba', 'slug' => 'cordoba', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Rosario', 'slug' => 'rosario', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Mendoza', 'slug' => 'mendoza', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'La Plata', 'slug' => 'la-plata', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '52' => [
                'iso_code' => 'MX',
                'cities' => [
                    ['name' => 'Mexico City', 'slug' => 'mexico-city', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Guadalajara', 'slug' => 'guadalajara', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Monterrey', 'slug' => 'monterrey', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Puebla', 'slug' => 'puebla', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Cancún', 'slug' => 'cancun', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Tijuana', 'slug' => 'tijuana', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            
            // Europe & Middle East
            '44' => [
                'iso_code' => 'GB',
                'cities' => [
                    ['name' => 'London', 'slug' => 'london', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Manchester', 'slug' => 'manchester', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Birmingham', 'slug' => 'birmingham', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Leeds', 'slug' => 'leeds', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Glasgow', 'slug' => 'glasgow', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Liverpool', 'slug' => 'liverpool', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '7' => [
                'iso_code' => 'RU',
                'cities' => [
                    ['name' => 'Moscow', 'slug' => 'moscow', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Saint Petersburg', 'slug' => 'saint-petersburg', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Novosibirsk', 'slug' => 'novosibirsk', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Yekaterinburg', 'slug' => 'yekaterinburg', 'is_major' => false, 'sort_order' => 4],
                    ['name' => 'Kazan', 'slug' => 'kazan', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Nizhny Novgorod', 'slug' => 'nizhny-novgorod', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '90' => [
                'iso_code' => 'TR',
                'cities' => [
                    ['name' => 'Istanbul', 'slug' => 'istanbul', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Ankara', 'slug' => 'ankara', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Izmir', 'slug' => 'izmir', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Bursa', 'slug' => 'bursa', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Antalya', 'slug' => 'antalya', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Adana', 'slug' => 'adana', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '20' => [
                'iso_code' => 'EG',
                'cities' => [
                    ['name' => 'Cairo', 'slug' => 'cairo', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Alexandria', 'slug' => 'alexandria', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Giza', 'slug' => 'giza', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Luxor', 'slug' => 'luxor', 'is_major' => false, 'sort_order' => 4],
                    ['name' => 'Aswan', 'slug' => 'aswan', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Port Said', 'slug' => 'port-said', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
            '212' => [
                'iso_code' => 'MA',
                'cities' => [
                    ['name' => 'Casablanca', 'slug' => 'casablanca', 'is_major' => true, 'sort_order' => 1],
                    ['name' => 'Rabat', 'slug' => 'rabat', 'is_major' => true, 'sort_order' => 2],
                    ['name' => 'Marrakech', 'slug' => 'marrakech', 'is_major' => true, 'sort_order' => 3],
                    ['name' => 'Fes', 'slug' => 'fes', 'is_major' => true, 'sort_order' => 4],
                    ['name' => 'Tangier', 'slug' => 'tangier', 'is_major' => false, 'sort_order' => 5],
                    ['name' => 'Agadir', 'slug' => 'agadir', 'is_major' => false, 'sort_order' => 6],
                    ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
                ]
            ],
        ];

        $citiesInserted = 0;
        $countriesUpdated = 0;

        // Process each country
        foreach ($phoneCodeMapping as $phoneCode => $data) {
            // Find country by phone code in the 'name' column (unusual but that's the schema)
            $country = DB::table('countries')
                ->where('name', $phoneCode)
                ->first();

            if (!$country) {
                $this->command->warn("Country with phone code {$phoneCode} not found in database. Skipping.");
                continue;
            }

            // Update the country with proper ISO code
            DB::table('countries')
                ->where('id', $country->id)
                ->update([
                    'iso_code' => $data['iso_code'],
                ]);

            $countriesUpdated++;
            $this->command->info("Processing {$country->dialling_code} (Phone: +{$phoneCode}, ISO: {$data['iso_code']})");

            // Insert cities for this country
            foreach ($data['cities'] as $cityData) {
                // Check if city already exists
                $existingCity = DB::table('cities')
                    ->where('country_id', $country->id)
                    ->where('slug', $cityData['slug'])
                    ->exists();

                if (!$existingCity) {
                    DB::table('cities')->insert([
                        'country_id' => $country->id,
                        'name' => $cityData['name'],
                        'slug' => $cityData['slug'],
                        'is_major' => $cityData['is_major'],
                        'sort_order' => $cityData['sort_order'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $citiesInserted++;
                }
            }
        }

        $this->command->info("✓ Seeding complete!");
        $this->command->info("  - Countries updated with ISO codes: {$countriesUpdated}");
        $this->command->info("  - Cities inserted: {$citiesInserted}");
    }
}
