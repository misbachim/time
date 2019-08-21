<?php

use Flynsarmy\CsvSeeder\CsvSeeder;

class DemoLogSchedulesTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'log_schedules';
        $this->filename = base_path().'/database/seeds/csvs/demo-log_schedules.csv';
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Recommended when importing larger CSVs
        DB::disableQueryLog();

        // Wipe the table clean before populating
        DB::statement('DELETE FROM '.$this->table.' WHERE tenant_id=1234567890');

        parent::run();

        DB::select("SELECT setval('log_schedules_id_seq', (SELECT max(id) from log_schedules))");
    }
}
