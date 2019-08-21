<?php

use Flynsarmy\CsvSeeder\CsvSeeder;

class DeltaTimeGroupsTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'time_groups';
        $this->filename = base_path().'/database/seeds/csvs/delta-time_groups.csv';
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

        // DB::select("SELECT setval('time_groups_id_seq', (SELECT max(id) from time_groups))");
    }
}
