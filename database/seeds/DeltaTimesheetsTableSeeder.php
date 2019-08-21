<?php

use Flynsarmy\CsvSeeder\CsvSeeder;

class DeltaTimesheetsTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'timesheets';
        $this->filename = base_path().'/database/seeds/csvs/delta-timesheets.csv';
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
