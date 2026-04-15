<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'content_strategist']);

        $firstUser = User::query()->orderBy('id')->first();

        if ($firstUser) {
            $firstUser->assignRole($superAdmin);
        }
    }
}
