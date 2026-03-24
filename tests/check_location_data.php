<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking existing countries and cities...\n\n";

// Check schema first
echo "Checking countries table schema...\n";
$countryColumns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'countries' ORDER BY ordinal_position");
echo "Countries table columns:\n";
foreach ($countryColumns as $col) {
    echo "  - {$col->column_name} ({$col->data_type})\n";
}

echo "\nChecking cities table schema...\n";
$cityColumns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'cities' ORDER BY ordinal_position");
if (count($cityColumns) > 0) {
    echo "Cities table columns:\n";
    foreach ($cityColumns as $col) {
        echo "  - {$col->column_name} ({$col->data_type})\n";
    }
} else {
    echo "Cities table does not exist\n";
}

// Check countries
$countriesCount = DB::table('countries')->count();
echo "\nCountries in database: {$countriesCount}\n";

if ($countriesCount > 0) {
    echo "\nSample existing countries (checking all columns):\n";
    $countries = DB::table('countries')->whereIn('id', [1, 2, 5, 100, 150])->get();
    foreach ($countries as $country) {
        echo "  ID: {$country->id}\n";
        echo "  Name: {$country->name}\n";
        echo "  Country Code: " . ($country->country_code ?? 'NULL') . "\n";
        echo "  Dialling Code: " . ($country->dialling_code ?? 'NULL') . "\n";
        echo "  ---\n";
    }
}

echo "\n DONE!\n";
