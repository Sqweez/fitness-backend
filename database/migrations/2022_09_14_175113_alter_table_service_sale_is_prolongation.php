<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableServiceSaleIsProlongation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_sale', function (Blueprint $table) {
            $table
                ->boolean('is_prolongation')
                ->after('active_until')
                ->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_sale', function (Blueprint $table) {
            $table->dropColumn('is_prolongation');
        });
    }
}
