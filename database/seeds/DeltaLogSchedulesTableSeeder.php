<?php

use Flynsarmy\CsvSeeder\CsvSeeder;

class DeltaLogSchedulesTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'log_schedules';
        $this->filename = base_path().'/database/seeds/csvs/delta-log_schedules.csv';
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

        DB::statement('DELETE FROM '.$this->table.' WHERE tenant_id=12345');

        // parent::run();

        // DB::select("SELECT setval('log_schedules_id_seq', (SELECT max(id) from log_schedules))");
    }
}
