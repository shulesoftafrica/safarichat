# Phone-Based Dynamic Location Detection

## Overview
This feature automatically detects the user's country from their phone number during registration and dynamically loads relevant cities for the "Primary Location" dropdown.

## How It Works

1. **User enters phone number** during login/registration (e.g., +254712345678 for Kenya)
2. **intlTelInput plugin** parses the phone number and detects country code (KE for Kenya)
3. **JavaScript fetches cities** from database via API endpoint `/api/cities-by-country?country_code=KE`
4. **Location dropdown** is populated with relevant cities for that country
5. **Fallback behavior**: If country not found, shows "Other City" option

## Features

✅ **Smart Detection**: Uses existing intlTelInput library (already in codebase)  
✅ **Database-Driven**: Cities stored in database, easy to add/update via seeder  
✅ **Multi-Country Support**: Includes East Africa (TZ, KE, UG, RW, BI) + Nigeria, Ghana, South Africa, US, UK  
✅ **Graceful Fallback**: Shows "Other City" if country not in database  
✅ **Performance**: AJAX-based loading with proper error handling

## Database Structure

### Tables Created

#### `countries` table
- `id` - Primary key
- `name` - Country name (e.g., "Tanzania")
- `code` - ISO 2-letter code (e.g., "TZ")
- `phone_code` - Phone prefix (e.g., "+255")
- `is_active` - Boolean flag
- `timestamps`

#### `cities` table
- `id` - Primary key
- `country_id` - Foreign key to countries
- `name` - City name (e.g., "Dar es Salaam")
- `slug` - URL-friendly name (e.g., "dar-es-salaam")
- `is_major` - Boolean flag for major cities
- `sort_order` - Custom sorting (major cities first)
- `is_active` - Boolean flag
- `timestamps`

## Installation Steps

### 1. Run Migration

```bash
php artisan migrate
```

This creates the `countries` and `cities` tables.

### 2. Run Seeder

```bash
php artisan db:seed --class=CountryCitySeeder
```

This populates the database with:
- **11 countries** (Tanzania, Kenya, Uganda, Rwanda, Burundi, South Africa, Nigeria, Ghana, US, UK, + "Other Country")
- **80+ cities** across all countries

### 3. Verify Data

```bash
php artisan tinker
```

```php
// Check countries count
DB::table('countries')->count(); // Should return 11

// Check cities count
DB::table('cities')->count(); // Should return 80+

// View Tanzania cities
DB::table('cities')
    ->join('countries', 'cities.country_id', '=', 'countries.id')
    ->where('countries.code', 'TZ')
    ->pluck('cities.name');
```

## Files Changed/Created

### New Files
1. **Migration**: `database/migrations/2026_03_24_100000_create_countries_cities_tables.php`
2. **Seeder**: `database/seeders/CountryCitySeeder.php`
3. **Documentation**: `PHONE_LOCATION_DETECTION.md` (this file)

### Modified Files
1. **Controller**: `app/Http/Controllers/Setup.php`
   - Added method: `getCitiesByCountry(Request $request)`

2. **Routes**: `routes/web.php`
   - Added route: `GET /api/cities-by-country`

3. **View**: `resources/views/auth/register.blade.php`
   - Added intlTelInput library (CSS + JS)
   - Added `initializeLocationByPhone()` function
   - Added `loadCitiesByCountry()` function
   - Updated location dropdown to be dynamically populated
   - Added loading states and user feedback

## API Endpoint Documentation

### GET `/api/cities-by-country`

**Parameters:**
- `country_code` (string, required) - ISO 2-letter country code (e.g., "TZ", "KE")

**Response (Success):**
```json
{
    "success": true,
    "country": {
        "name": "Tanzania",
        "code": "TZ",
        "phone_code": "+255"
    },
    "cities": [
        {
            "id": 1,
            "name": "Dar es Salaam",
            "slug": "dar-es-salaam",
            "is_major": true
        },
        {
            "id": 2,
            "name": "Dodoma",
            "slug": "dodoma",
            "is_major": true
        }
    ]
}
```

**Response (Country Not Found):**
```json
{
    "success": false,
    "message": "No cities available",
    "cities": []
}
```

## Testing the Feature

### Manual Testing

1. **Access Registration Page**
   ```
   http://localhost:8000/register
   ```

2. **Enter Phone Numbers from Different Countries**
   - Tanzania: `+255712345678` → Should show Tanzanian cities
   - Kenya: `+254712345678` → Should show Kenyan cities
   - Uganda: `+256712345678` → Should show Ugandan cities
   - Nigeria: `+234812345678` → Should show Nigerian cities

3. **Check Browser Console**
   - Look for: `Detected phone country: XX from phone: +XXX...`
   - Look for: `Loaded XX cities for [Country Name]`

4. **Verify Dropdown**
   - Location dropdown should populate with cities from detected country
   - "Other City" should appear at the bottom
   - Dropdown should be enabled (not disabled)

### Test Cases

| Phone Number    | Expected Country | Expected Cities                          |
|-----------------|------------------|------------------------------------------|
| +255752123456   | Tanzania (TZ)    | Dar es Salaam, Dodoma, Arusha, etc.      |
| +254712345678   | Kenya (KE)       | Nairobi, Mombasa, Kisumu, etc.           |
| +256712345678   | Uganda (UG)      | Kampala, Entebbe, Gulu, etc.             |
| +250788123456   | Rwanda (RW)      | Kigali, Butare, Gitarama, etc.           |
| +1234567890     | USA (US)         | New York, Los Angeles, Chicago, etc.     |
| +999999999      | Unknown          | Other City (fallback)                    |

## Adding New Countries/Cities

### Option 1: Via Database Seeder (Recommended)

Edit `database/seeders/CountryCitySeeder.php` and add new country data:

```php
[
    'name' => 'Ethiopia',
    'code' => 'ET',
    'phone_code' => '+251',
    'cities' => [
        ['name' => 'Addis Ababa', 'slug' => 'addis-ababa', 'is_major' => true, 'sort_order' => 1],
        ['name' => 'Dire Dawa', 'slug' => 'dire-dawa', 'is_major' => true, 'sort_order' => 2],
        ['name' => 'Other City', 'slug' => 'other', 'is_major' => false, 'sort_order' => 999],
    ]
],
```

Then re-run the seeder:
```bash
php artisan db:seed --class=CountryCitySeeder
```

### Option 2: Direct Database Insert

```sql
-- Insert country
INSERT INTO countries (name, code, phone_code, is_active, created_at, updated_at)
VALUES ('Ethiopia', 'ET', '+251', true, NOW(), NOW());

-- Insert cities (replace XX with actual country_id)
INSERT INTO cities (country_id, name, slug, is_major, sort_order, is_active, created_at, updated_at)
VALUES 
(XX, 'Addis Ababa', 'addis-ababa', true, 1, true, NOW(), NOW()),
(XX, 'Dire Dawa', 'dire-dawa', true, 2, true, NOW(), NOW());
```

## Troubleshooting

### Issue: Location dropdown shows "Detecting location from phone..."
**Solution:** 
- Check browser console for JavaScript errors
- Verify phone number is being passed to view: `{{$phone}}`
- Check if intlTelInput library is loaded (view page source)

### Issue: Location dropdown shows "Other Location" for valid country
**Solution:**
- Check if country exists in database: `SELECT * FROM countries WHERE code = 'XX';`
- Run seeder if country is missing: `php artisan db:seed --class=CountryCitySeeder`

### Issue: API returns "No cities available"
**Solution:**
- Check if cities exist: `SELECT * FROM cities WHERE country_id = X;`
- Verify country code mapping: Phone +255 → TZ, +254 → KE, etc.

### Issue: intlTelInput not detecting country correctly
**Solution:**
- Ensure phone number includes country code (+255, not just 255)
- Check intlTelInput utils.js is loaded
- Verify phone format matches E.164 standard (+[country code][number])

## Performance Considerations

- **AJAX Call**: Single API call per registration (on page load)
- **Database Query**: Indexed on `country_id` and `is_active` for fast retrieval
- **Caching**: Consider adding Redis/Memcached for high-traffic sites
- **CDN**: intlTelInput loaded from Cloudflare CDN

## Future Enhancements

1. **Add More Countries**: Expand beyond current 11 countries
2. **State/Province Support**: Add intermediate level (e.g., US states)
3. **Multi-Language**: Translate city names (Swahili, French, etc.)
4. **Admin UI**: Build admin panel to manage countries/cities
5. **Geocoding**: Add latitude/longitude for mapping
6. **Population Data**: Include city population for sorting

## Credits

- **intlTelInput**: [github.com/jackocnr/intl-tel-input](https://github.com/jackocnr/intl-tel-input)
- **City Data Sources**: Wikipedia, GeoNames

## Support

For issues or questions:
1. Check browser console for JavaScript errors
2. Verify database tables exist: `SHOW TABLES LIKE '%cities%';`
3. Test API endpoint manually: `/api/cities-by-country?country_code=TZ`
4. Review Laravel logs: `storage/logs/laravel.log`
