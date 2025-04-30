<?php

use App\Models\AcademicClass;
use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_activities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignIdFor(Enterprise::class)->nullable();
            $table->foreignIdFor(AcademicClass::class)->nullable();
            $table->foreignIdFor(User::class, 'lecturer_id')->nullable();
            $table->foreignIdFor(User::class, 'student_id')->nullable();
            $table->string('type')->nullable();
            $table->string('name')->nullable(); 
            $table->string('start_week')->nullable();
            $table->string('end_week')->nullable();
            $table->string('number_of_weeks')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('Active')->nullable();
            $table->string('update_existing_items')->default('No')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_activities');
    }
}
