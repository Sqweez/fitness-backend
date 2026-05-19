<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableTransactionMorphsColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->after('id', function (Blueprint $table) {
                $table->string('transactional_type');
                $table->unsignedInteger('transactional_id');
            });
            $table->dropColumn('payment_type');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['transactional_type', 'transactional_id']);
            $table->unsignedInteger('payment_type');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedInteger('transaction_id');
        });
    }
}
