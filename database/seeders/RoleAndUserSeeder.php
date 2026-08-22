<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat Roles dasar
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'admin_opd']);
        Role::firstOrCreate(['name' => 'pegawai']);

        // Assign default Super Admin
        $admin = User::updateOrCreate(
            ['nip' => 'kalamangna'],
            [
                'name' => 'kalamangna',
                'password' => bcrypt('Syazani'),
            ]
        );
        $admin->syncRoles(['admin']);
    }
}

