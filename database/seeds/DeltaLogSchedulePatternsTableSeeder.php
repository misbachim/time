<?php

use Flynsarmy\CsvSeeder\CsvSeeder;

class DeltaLogSchedulePatternsTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'log_schedule_patterns';
        $this->filename = base_path().'/database/seeds/csvs/delta-log_schedule_patterns.csv';
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
    }
}
