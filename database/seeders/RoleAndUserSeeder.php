<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat Roles
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleAdminOPD = Role::create(['name' => 'admin_opd']);
        $rolePegawai = Role::create(['name' => 'pegawai']);

        // Assign default user admin
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@rapat.local',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $adminReal = User::create([
            'name' => 'Admin Sistem',
            'email' => '199610072022031013@pegawai.sinjaikab.go.id',
            'nip' => '199610072022031013',
            'password' => bcrypt('password'),
        ]);
        $adminReal->assignRole('admin');

        // Assign default admin opd
        $adminOpd = User::create([
            'name' => 'Admin Kominfo',
            'email' => 'admin.opd@rapat.local',
            'password' => bcrypt('password'),
            'unit_name' => 'Dinas Kominfo',
        ]);
        $adminOpd->assignRole('admin_opd');

        // Assign default pegawai
        $pegawai = User::create([
            'name' => 'Pegawai Biasa',
            'email' => 'pegawai@rapat.local',
            'password' => bcrypt('password'),
            'unit_name' => 'Dinas Kominfo',
        ]);
        $pegawai->assignRole('pegawai');
    }
}
