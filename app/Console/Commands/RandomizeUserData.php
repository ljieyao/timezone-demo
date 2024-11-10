<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Faker\Factory as Faker;

class RandomizeUserData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:randomize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update users with random data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $faker = Faker::create();
        $timezones = ['CET', 'CST', 'GMT+1'];

        User::all()->each(function ($user) use ($faker, $timezones) {
            $nameParts = explode(' ', $faker->name());

            $user->update([
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1],
                'timezone' => $timezones[array_rand($timezones)],
            ]);
        });

        $this->info('Users updated successfully!');
    }
}
