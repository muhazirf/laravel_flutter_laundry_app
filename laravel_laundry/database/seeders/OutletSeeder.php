<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OutletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('user_outlets')->delete();
        Outlet::query()->delete();

        // Get test users first to assign as owner
        $testJwtUser = User::where('email', 'testjwt@example.com')->first();
        if (!$testJwtUser) {
            $testJwtUser = User::first();
        }

        // Create test outlets with owner
        $outlets = [];
        if ($testJwtUser) {
            $outlets = [
                [
                    'owner_user_id' => $testJwtUser->id,
                    'name' => 'Laundry Central',
                    'address' => 'Jl. Sudirman No. 123, Jakarta',
                    'phone' => '021-1234567',
                    'is_active' => true,
                ],
                [
                    'owner_user_id' => $testJwtUser->id,
                    'name' => 'Laundry Branch 1',
                    'address' => 'Jl. Gatot Subroto No. 456, Jakarta',
                    'phone' => '021-7654321',
                    'is_active' => true,
                ],
                [
                    'owner_user_id' => $testJwtUser->id,
                    'name' => 'Laundry Branch 2',
                    'address' => 'Jl. Thamrin No. 789, Jakarta',
                    'phone' => '021-9876543',
                    'is_active' => true,
                ],
            ];
        }

        $createdOutlets = !empty($outlets) ? Outlet::insert($outlets) : false;

        if ($createdOutlets) {
            // Get the created outlets
            $outlet1 = Outlet::where('name', 'Laundry Central')->first();
            $outlet2 = Outlet::where('name', 'Laundry Branch 1')->first();
            $outlet3 = Outlet::where('name', 'Laundry Branch 2')->first();

            // Get additional test users
            $testUser = User::where('email', 'test@example.com')->first();
            if (!$testUser) {
                $testUser = User::latest()->first() ?: $testJwtUser;
            }

            // Assign users to outlets with roles and permissions
            if ($testJwtUser && $outlet1) {
                DB::table('user_outlets')->insert([
                    'user_id' => $testJwtUser->id,
                    'outlet_id' => $outlet1->id,
                    'role' => 'owner',
                    'permissions_json' => json_encode([
                        'manage_users' => true,
                        'manage_outlets' => true,
                        'view_reports' => true,
                        'manage_transactions' => true,
                        'manage_settings' => true,
                    ]),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Also assign to second outlet as karyawan (staff)
                if ($outlet2) {
                    DB::table('user_outlets')->insert([
                        'user_id' => $testJwtUser->id,
                        'outlet_id' => $outlet2->id,
                        'role' => 'karyawan',
                        'permissions_json' => json_encode([
                            'manage_users' => true,
                            'view_reports' => true,
                            'manage_transactions' => true,
                        ]),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Assign second user if exists
            if ($testUser && $testUser->id !== $testJwtUser?->id && $outlet2) {
                DB::table('user_outlets')->insert([
                    'user_id' => $testUser->id,
                    'outlet_id' => $outlet2->id,
                    'role' => 'karyawan',
                    'permissions_json' => json_encode([
                        'view_reports' => true,
                        'manage_transactions' => true,
                    ]),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info('✅ Outlets and user assignments created successfully!');
        } else {
            $this->command->error('❌ Failed to create outlets');
        }
    }
}
