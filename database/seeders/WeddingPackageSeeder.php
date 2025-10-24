<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WeddingPackage;

class WeddingPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create wedding packages
        WeddingPackage::create([
            'name' => 'Basic Package',
            'description' => 'Simple and elegant wedding package for intimate ceremonies',
            'price' => 5000.00,
            'duration_hours' => 6,
            'max_guests' => 50,
            'features' => [
                'Photography (4 hours)',
                'Basic decoration',
                'Wedding cake',
                'Bridal bouquet'
            ],
            'status' => 'active',
        ]);

        WeddingPackage::create([
            'name' => 'Premium Package',
            'description' => 'Complete wedding package with premium services',
            'price' => 12000.00,
            'duration_hours' => 8,
            'max_guests' => 100,
            'features' => [
                'Photography & Videography (8 hours)',
                'Premium decoration',
                '3-tier wedding cake',
                'Bridal & bridesmaids bouquets',
                'DJ services',
                'Basic catering'
            ],
            'status' => 'active',
        ]);

        WeddingPackage::create([
            'name' => 'Luxury Package',
            'description' => 'Ultimate luxury wedding experience',
            'price' => 25000.00,
            'duration_hours' => 12,
            'max_guests' => 200,
            'features' => [
                'Full day photography & videography',
                'Luxury decoration & lighting',
                'Multi-tier designer cake',
                'Premium floral arrangements',
                'Live band',
                'Full catering service',
                'Wedding coordinator',
                'Transportation'
            ],
            'status' => 'active',
        ]);
    }
}
