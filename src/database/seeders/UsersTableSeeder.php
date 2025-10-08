<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // テスト・デバッグ用および一般ユーザーをまとめて定義
        $users = [
            [
                'name' => 'テストユーザーA',
                'email' => 'user_a@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'テストユーザーB',
                'email' => 'user_b@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'テストユーザーC',
                'email' => 'user_c@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'テストユーザーD',
                'email' => 'user_d@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ],
            // 個別に定義されていた一般ユーザーを統合
            [
                'name' => '一般ユーザ1',
                'email' => 'general1@gmail.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ],
            [
                'name' => '一般ユーザ2',
                'email' => 'general2@gmail.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ],
        ];

        // 定義した全てのユーザーをループで作成
        foreach ($users as $param) {
            User::create($param);
        }
    }
}
