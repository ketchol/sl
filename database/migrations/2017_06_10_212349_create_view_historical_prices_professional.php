<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateViewHistoricalPricesProfessional extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('
            CREATE VIEW historical_prices_professional
            AS (
                SELECT historical_prices.* 
                FROM historical_prices 
                JOIN (
                    SELECT historical_prices.item_meta_id, MAX(id) id
                    FROM historical_prices 
                    WHERE 
                    created_at > (NOW() - INTERVAL 12 MONTH)
                    GROUP BY historical_prices.item_meta_id, CEIL(UNIX_TIMESTAMP(created_at)/(12 * 60 * 60))
                ) formatted_prices ON(formatted_prices.id=historical_prices.id)
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
            DROP VIEW historical_prices_professional
        ');
    }
}