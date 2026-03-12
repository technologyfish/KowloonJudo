<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // 如果管理员已存在 → 重置密码；否则 → 创建
        $exists = DB::table('admins')->where('email', 'admin@kowloonjudo.com')->first();

        if ($exists) {
            DB::table('admins')
                ->where('email', 'admin@kowloonjudo.com')
                ->update([
                    'password'   => Hash::make('Admin@123456'),
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('admins')->insert([
                'name'       => '超级管理员',
                'email'      => 'admin@kowloonjudo.com',
                'password'   => Hash::make('Admin@123456'),
                'role'       => 'super_admin',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
