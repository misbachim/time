<?php

use Illuminate\Database\Seeder;

class DeltaDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call('DatabaseSeeder');
        $this->call('DeltaTimeGroupsTableSeeder');
        $this->call('DeltaLeavesTableSeeder');
        $this->call('DeltaLogSchedulesTableSeeder');
        $this->call('DeltaLogSchedulePatternsTableSeeder');
        $this->call('DeltaTimeAttributesTableSeeder');
        $this->call('DeltaTimesheetsTableSeeder');
    }
}
