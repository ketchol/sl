<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateViewHistoricalPricesIdEnterprise extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('
            CREATE VIEW historical_prices_id_enterprise
            AS (
                SELECT historical_prices.item_meta_id, MAX(id) id
                FROM historical_prices 
                GROUP BY historical_prices.item_meta_id, CEIL(UNIX_TIMESTAMP(created_at)/(1 * 60 * 60))
            )
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('
            DROP VIEW historical_prices_id_enterprise
        ');
    }
}