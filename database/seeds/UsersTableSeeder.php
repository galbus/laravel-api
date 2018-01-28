<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $data = $this->getData();
        foreach ($data as $i => $user) {
            $user['created_at'] = date('Y-m-d H:i:s');
            DB::table('users')->insert($user);
        }
    }

    private function getData() {
        return [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Diziet Sma',
                'email' => 'diziet.sma@example.com',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Testy Test',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ],
        ];
    }
}
