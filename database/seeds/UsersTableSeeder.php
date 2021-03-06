<?php

use Illuminate\Database\Seeder;
use App\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $u1 = [
            'name' => 'Prajjwal Poudel',
            'email' => 'iamprazol@gmail.com',
            'address' => 'Sorhekhutte',
            'phone' => 9779845690436,
            'password' => bcrypt('prajjwal123'),
            'company_name' => 'Kuwa',
            'is_verified' => 1,
            'admin' => 1,
            'firebase_token' => 'askajskajskjas'
        ];

        $u2 = [
            'name' => 'Kushal Poudel',
            'email' => 'iampra@gmail.com',
            'address' => 'Soekhutte',
            'phone' => 9779845890436,
            'password' => bcrypt('prajjwal123'),
            'company_name' => 'tuuwa',
            'is_verified' => 1,
            'admin' => 0,
            'firebase_token' => 'askajskajskjas'
        ];

        User::create($u1);
        User::create($u2);
    }
}
