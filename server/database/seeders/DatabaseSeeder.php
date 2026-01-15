<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProfile;
use App\Models\VendorPortfolio;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // First, run the Role and Permission seeder
        $this->call(RoleAndPermissionSeeder::class);

        // Create Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@eventreliability.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // Create Categories
        $categories = [
            ['name' => 'Photographer', 'slug' => 'photographer', 'icon' => '📷', 'description' => 'Professional event photographers'],
            ['name' => 'Videographer', 'slug' => 'videographer', 'icon' => '🎥', 'description' => 'Professional video coverage'],
            ['name' => 'Decorator', 'slug' => 'decorator', 'icon' => '🎨', 'description' => 'Event decoration specialists'],
            ['name' => 'Caterer', 'slug' => 'caterer', 'icon' => '🍽️', 'description' => 'Food and catering services'],
            ['name' => 'DJ/Music', 'slug' => 'dj-music', 'icon' => '🎵', 'description' => 'Music and entertainment'],
            ['name' => 'Makeup Artist', 'slug' => 'makeup-artist', 'icon' => '💄', 'description' => 'Bridal and event makeup'],
            ['name' => 'Florist', 'slug' => 'florist', 'icon' => '💐', 'description' => 'Floral arrangements'],
            ['name' => 'Event Planner', 'slug' => 'event-planner', 'icon' => '📋', 'description' => 'Full event planning services'],
        ];

        foreach ($categories as $index => $category) {
            Category::create(array_merge($category, ['sort_order' => $index]));
        }

        // Create sample vendors
        $vendorsData = [
            [
                'name' => 'Rajesh Kumar',
                'email' => 'rajesh@photography.com',
                'business_name' => 'Rajesh Photography Studio',
                'category' => 'photographer',
                'city' => 'Mumbai',
                'experience_years' => 12,
                'min_price' => 25000,
                'max_price' => 150000,
                'reliability_score' => 4.8,
                'total_events_completed' => 156,
                'description' => 'Award-winning wedding photographer with 12+ years of experience capturing beautiful moments. Specialized in candid photography and cinematic pre-wedding shoots.',
            ],
            [
                'name' => 'Priya Sharma',
                'email' => 'priya@decorations.com',
                'business_name' => 'Priya\'s Dream Decorations',
                'category' => 'decorator',
                'city' => 'Mumbai',
                'experience_years' => 8,
                'min_price' => 50000,
                'max_price' => 500000,
                'reliability_score' => 4.6,
                'total_events_completed' => 89,
                'description' => 'Transforming venues into magical spaces. Specialized in floral decorations, mandap designs, and theme-based event decoration.',
            ],
            [
                'name' => 'Amit Patel',
                'email' => 'amit@films.com',
                'business_name' => 'Amit Films & Productions',
                'category' => 'videographer',
                'city' => 'Delhi',
                'experience_years' => 10,
                'min_price' => 40000,
                'max_price' => 200000,
                'reliability_score' => 4.9,
                'total_events_completed' => 234,
                'description' => 'Cinematic wedding films that tell your love story. Using latest 4K equipment and drone coverage for stunning aerial shots.',
            ],
            [
                'name' => 'Sneha Reddy',
                'email' => 'sneha@makeup.com',
                'business_name' => 'Glamour by Sneha',
                'category' => 'makeup-artist',
                'city' => 'Bangalore',
                'experience_years' => 6,
                'min_price' => 15000,
                'max_price' => 75000,
                'reliability_score' => 4.7,
                'total_events_completed' => 312,
                'description' => 'Celebrity makeup artist specializing in bridal makeup, HD makeup, and airbrush techniques. Using only premium international brands.',
            ],
            [
                'name' => 'Vikram Singh',
                'email' => 'vikram@catering.com',
                'business_name' => 'Royal Feast Caterers',
                'category' => 'caterer',
                'city' => 'Delhi',
                'experience_years' => 15,
                'min_price' => 100000,
                'max_price' => 1000000,
                'reliability_score' => 4.5,
                'total_events_completed' => 178,
                'description' => 'Multi-cuisine catering with live counters. Specialized in North Indian, South Indian, Continental, and fusion cuisines.',
            ],
            [
                'name' => 'DJ Arjun',
                'email' => 'arjun@djmusic.com',
                'business_name' => 'DJ Arjun Entertainment',
                'category' => 'dj-music',
                'city' => 'Mumbai',
                'experience_years' => 9,
                'min_price' => 30000,
                'max_price' => 150000,
                'reliability_score' => 4.4,
                'total_events_completed' => 445,
                'description' => 'High-energy DJ sets with latest sound systems. Bollywood, EDM, Hip-hop, and customized playlists for your special day.',
            ],
            [
                'name' => 'Meera Joshi',
                'email' => 'meera@florals.com',
                'business_name' => 'Bloom & Blossom Florals',
                'category' => 'florist',
                'city' => 'Pune',
                'experience_years' => 7,
                'min_price' => 20000,
                'max_price' => 200000,
                'reliability_score' => 4.8,
                'total_events_completed' => 167,
                'description' => 'Fresh flower arrangements, bridal bouquets, and venue decoration. Importing exotic flowers for premium events.',
            ],
            [
                'name' => 'Karan Mehta',
                'email' => 'karan@events.com',
                'business_name' => 'Karan Events & Celebrations',
                'category' => 'event-planner',
                'city' => 'Bangalore',
                'experience_years' => 11,
                'min_price' => 75000,
                'max_price' => 500000,
                'reliability_score' => 4.9,
                'total_events_completed' => 98,
                'description' => 'End-to-end event planning and management. From intimate gatherings to grand weddings, we handle everything.',
            ],
        ];

        foreach ($vendorsData as $vendorData) {
            // Create user
            $user = User::create([
                'name' => $vendorData['name'],
                'email' => $vendorData['email'],
                'password' => Hash::make('password'),
                'city' => $vendorData['city'],
                'is_active' => true,
            ]);
            // Assign vendor role using Spatie
            $user->assignRole('vendor');

            // Create vendor
            $vendor = Vendor::create([
                'user_id' => $user->id,
                'business_name' => $vendorData['business_name'],
                'category' => $vendorData['category'],
                'city' => $vendorData['city'],
                'experience_years' => $vendorData['experience_years'],
                'min_price' => $vendorData['min_price'],
                'max_price' => $vendorData['max_price'],
                'description' => $vendorData['description'],
                'is_verified' => true,
                'is_active' => true,
                'accepts_emergency' => true,
                'backup_ready' => true,
                'reliability_score' => $vendorData['reliability_score'],
                'total_events_completed' => $vendorData['total_events_completed'],
                'verified_at' => now(),
            ]);

            // Create profile
            VendorProfile::create([
                'vendor_id' => $vendor->id,
                'phone' => '+91 ' . rand(7000000000, 9999999999),
                'whatsapp' => '+91 ' . rand(7000000000, 9999999999),
                'email' => $vendorData['email'],
                'services_offered' => ['Basic Package', 'Premium Package', 'Luxury Package'],
            ]);
        }

        // Create sample client
        $client = User::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'password' => Hash::make('password'),
            'city' => 'Mumbai',
            'is_active' => true,
        ]);
        $client->assignRole('client');

        $this->command->info('Database seeded successfully!');
        $this->command->info('Roles created: admin, vendor, client');
        $this->command->info('Admin: admin@eventreliability.com / password');
        $this->command->info('Client: client@test.com / password');
        $this->command->info('Vendors: rajesh@photography.com / password (and others)');
    }
}
