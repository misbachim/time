<?php

use Flynsarmy\CsvSeeder\CsvSeeder;

class DeltaLeavesTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'leaves';
        $this->filename = base_path().'/database/seeds/csvs/delta-leaves.csv';
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

        // DB::select("SELECT setval('leaves_id_seq', (SELECT max(id) from leaves))");
    }
}
