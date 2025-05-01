<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreToAccountParents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_parents', function (Blueprint $table) {
            $table->text('client_id')->nullable();
            $table->text('name')->nullable()->change();
            $table->text('short_name')->nullable();
            $table->text('logo')->nullable();
            $table->text('other_clients')->nullable();
            $table->text('details')->nullable();
            $table->text('progress')->nullable();
            $table->text('budget_overview')->nullable();
            $table->text('schedule_overview')->nullable();
            $table->text('risks_issues')->nullable();
            $table->text('concerns_recommendations')->nullable();
            $table->text('status')->nullable();
            $table->text('start_date')->nullable();
            $table->text('end_date')->nullable();
            $table->text('files')->nullable();
            $table->text('team_members')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_parents', function (Blueprint $table) {
            //
        });
    }
}
