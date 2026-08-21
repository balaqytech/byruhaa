<?php

namespace Database\Seeders;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            PublicPageSeeder::class,
            StoreCatalogSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'As3ad',
            'email' => 'as3ad.moh@gmail.com',
            'password' => bcrypt('123123123'),
        ]);
    }
}
