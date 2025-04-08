<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColsSchoolFeesDemands extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('school_fees_demands', function (Blueprint $table) {
            $table->text('classes')->nullable();
            $table->string('sms_sent')->nullable()->default('No'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('school_fees_demands', function (Blueprint $table) {

            //
        });
    }
}
