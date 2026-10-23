<?php

namespace Database\Seeders;

use App\Models\DemandChannel;
use Illuminate\Database\Seeder;

class DemandChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DemandChannel::create([
            'title' => 'Retail',
        ]);

        DemandChannel::create([
            'title' => 'Direct',
        ]);
    }
}
