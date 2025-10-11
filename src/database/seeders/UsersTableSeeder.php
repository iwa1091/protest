<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'ユーザーA（出品者）',
                'email' => 'seller_a@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'ユーザーB（出品者）',
                'email' => 'seller_b@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'ユーザーC（未紐づけ）',
                'email' => 'user_c@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($users as $param) {
            User::create($param);
        }
    }
}
