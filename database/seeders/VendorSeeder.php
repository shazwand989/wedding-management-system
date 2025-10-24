<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendor;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get vendor users by email
        $photographer = User::where('email', 'photographer@example.com')->first();
        $caterer = User::where('email', 'caterer@example.com')->first();
        $decorator = User::where('email', 'decorator@example.com')->first();

        // Create vendor profiles
        if ($photographer) {
            Vendor::create([
                'user_id' => $photographer->id,
                'business_name' => 'John Photography Studio',
                'service_type' => 'photography',
                'description' => 'Professional wedding photography with 10+ years experience',
                'price_range' => 'RM 2000 - RM 8000',
                'rating' => 4.8,
                'total_reviews' => 45,
                'status' => 'active',
            ]);
        }

        if ($caterer) {
            Vendor::create([
                'user_id' => $caterer->id,
                'business_name' => 'Delicious Catering',
                'service_type' => 'catering',
                'description' => 'Full-service catering for weddings and special events',
                'price_range' => 'RM 30 - RM 80 per person',
                'rating' => 4.6,
                'total_reviews' => 32,
                'status' => 'active',
            ]);
        }

        if ($decorator) {
            Vendor::create([
                'user_id' => $decorator->id,
                'business_name' => 'Beautiful Decorations',
                'service_type' => 'decoration',
                'description' => 'Creative wedding decoration and floral arrangements',
                'price_range' => 'RM 3000 - RM 15000',
                'rating' => 4.9,
                'total_reviews' => 28,
                'status' => 'active',
            ]);
        }
    }
}
