<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataFieldToLogstashConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('logstash_config', function (Blueprint $table) {
            $table->text('context')->nullable()->after('protocol');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('logstash_config', function (Blueprint $table) {
            $table->dropColumn('context');
        });
    }
}
