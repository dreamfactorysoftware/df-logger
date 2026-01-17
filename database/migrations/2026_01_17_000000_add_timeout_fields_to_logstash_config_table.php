<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimeoutFieldsToLogstashConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('logstash_config', function (Blueprint $t) {
            $t->float('timeout')->default(2.0)->after('protocol');
            $t->string('on_failure')->default('ignore')->after('timeout');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('logstash_config', function (Blueprint $t) {
            $t->dropColumn('timeout');
            $t->dropColumn('on_failure');
        });
    }
}
