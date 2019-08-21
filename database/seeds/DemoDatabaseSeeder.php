<?php

use Illuminate\Database\Seeder;

class DemoDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call('DatabaseSeeder');
        $this->call('DemoTimeGroupsTableSeeder');
        $this->call('DemoLeavesTableSeeder');
        $this->call('DemoLogSchedulesTableSeeder');
        $this->call('DemoLogSchedulePatternsTableSeeder');
        $this->call('DemoTimeAttributesTableSeeder');
        $this->call('DemoTimesheetsTableSeeder');
    }
}
