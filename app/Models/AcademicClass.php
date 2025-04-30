<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Administrator;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mockery\Matcher\Subset;

class AcademicClass extends Model
{
    use HasFactory;

    //get for select dropdwon
    public static function getAcademicClasses($conds)
    {
        $classes = AcademicClass::where($conds)->get();
        $arr = [];
        foreach ($classes as $key => $value) {
            $arr[$value->id] = $value->name_text;
        }
        return $arr;
    }

    public static function boot()
    {
        parent::boot();
        self::deleting(function ($m) {});
  
    }


  
    /* 
    "created_at" => "2022-09-17 06:33:43"
    "updated_at" => "2022-09-17 06:33:43"
    "enterprise_id" => 8
    "academic_year_id" => 1
    "class_teahcer_id" => 2207
    "name" => "P.1 - Muhindo Mubaraka"
    "short_name" => "P.1"
    "details" => "P.1 - Muhindo Mubaraka"
    "demo_id" => 0
    "compulsory_subjects" => 0
    "optional_subjects" => 0
    "class_type" => "Secondary"
    "academic_class_level_id" => 4
*/
    public static function get_academic_class_category($class)
    {
        if (
            $class == 'P.1' ||
            $class == 'P.2' ||
            $class == 'P.3' ||
            $class == 'P.4' ||
            $class == 'P.5' ||
            $class == 'P.6' ||
            $class == 'P.7'
        ) {
            return "Primary";
        } else if (
            $class == 'S.1' ||
            $class == 'S.2' ||
            $class == 'S.3' ||
            $class == 'S.4'
        ) {
            return "Secondary";
        } else if (
            $class == 'S.5' ||
            $class == 'S.6'
        ) {
            return "Advanced";
        } else {
            return "Other";
        }
    }

    public static function update_fees($m)
    {
        if ($m == null) {
            return;
        }
        if ($m->status != 1) {
            return;
        }

        if (strtolower($m->user_type) != 'student') {
            return;
        }

        if ($m->status != 1) {
            return;
        }



        if ($m->ent == null) {
            return;
        }

        $active_term = $m->ent->active_term();
        if ($active_term == null) {
            return;
        }

        //billing for secular class
        foreach (
            StudentHasClass::where([
                'administrator_id' => $m->id,
            ])->get() as $key => $val
        ) {
            if ($val != null) {
                if ($val->class != null) {
                    if ($val->class->academic_class_fees != null) {
                        foreach ($val->class->academic_class_fees as $fee) {
                            /*  dd($fee->due_term_id);
                            dd($active_term->id . "<==>" . $fee->due_term_id); */
                            if ($fee != null) {
                                if ($active_term->id != $fee->due_term_id) {
                                    continue;
                                }

                                $has_fee = StudentHasFee::where([
                                    'administrator_id' => $m->id,
                                    'academic_class_fee_id' => $fee->id,
                                ])->first();
                                if ($has_fee == null) {

                                    Transaction::my_create([
                                        'academic_year_id' => $val->class->academic_year_id,
                                        'administrator_id' => $m->id,
                                        'type' => 'FEES_BILLING',
                                        'description' => "Debited {$fee->amount} for $fee->name",
                                        'amount' => ((-1) * ($fee->amount))
                                    ]);
                                    $has_fee =  new StudentHasFee();
                                    $has_fee->enterprise_id    = $m->enterprise_id;
                                    $has_fee->administrator_id    = $m->id;
                                    $has_fee->academic_class_fee_id    = $fee->id;
                                    $has_fee->academic_class_id    = $val->class->id;
                                    $has_fee->save();
                                }
                            }
                        }
                    }
                }
            }
        }


        //bulling theology classes
        foreach (
            StudentHasTheologyClass::where([
                'administrator_id' => $m->id,
            ])->get() as $key => $val
        ) {
            if ($val != null) {
                if ($val->class != null) {
                    if ($val->class->academic_class_fees != null) {
                        foreach ($val->class->academic_class_fees as $fee) {
                            if ($fee != null) {
                                $has_fee = StudentHasFee::where([
                                    'administrator_id' => $m->administrator_id,
                                    'academic_class_fee_id' => $fee->id,
                                ])->first();
                                if ($has_fee == null) {

                                    Transaction::my_create([
                                        'academic_year_id' => $val->class->academic_year_id,
                                        'administrator_id' => $m->id,
                                        'type' => 'FEES_BILLING',
                                        'description' => "Debited {$fee->amount} for $fee->name",
                                        'amount' => ((-1) * ($fee->amount))
                                    ]);

                                    $has_fee =  new StudentHasFee();
                                    $has_fee->enterprise_id    = $m->enterprise_id;
                                    $has_fee->administrator_id    = $m->administrator_id;
                                    $has_fee->academic_class_fee_id    = $fee->id;
                                    $has_fee->theology_class_id    = $fee->theology_class_id;
                                    $has_fee->save();
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    function subject()
    {
        return $this->belongsTo(SecondarySubject::class, 'subject_id');
    }
    function academic_class_fees()
    {
        return $this->hasMany(AcademicClassFee::class);
    }

    function streams()
    {
        return $this->hasMany(AcademicClassSctream::class, 'academic_class_id');
    }

    function competences()
    {
        return $this->hasMany(Competence::class);
    }

    function academic_class_sctreams()
    {
        return $this->hasMany(AcademicClassSctream::class);
    }

    function academic_year()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    function level()
    {
        return $this->belongsTo(AcademicClassLevel::class, 'academic_class_level_id');
    }
    function class_teacher()
    {
        return $this->belongsTo(Administrator::class, 'class_teahcer_id');
    }
    function ent()
    {
        return $this->belongsTo(Enterprise::class, 'enterprise_id');
    }

    function get_students_subjects($administrator_id)
    {


        if ($this->ent->type == 'Primary') {
            return $this->subjects;
        }
        $subs = [];
        $subs = Subject::where(
            'academic_class_id',
            $this->id,
        )
            ->where(
                'is_optional',
                '!=',
                1
            )
            ->get();


        $done_main_subs = [];
        $main_subs = [];

        $optionals = StudentHasOptionalSubject::where([
            'academic_class_id' => $this->id,
            'administrator_id' => $administrator_id
        ])->get();

        foreach ($optionals as $option) {

            if (in_array($option->main_course_id, $done_main_subs)) {
                continue;
            }
            $done_main_subs[] = $option->main_course_id;

            $course = MainCourse::find($option->main_course_id);
            if ($course == null) {
                continue;
            }

            $main_subs[] = $course;
        }



        foreach ($subs as $key => $sub) {
            if (in_array($sub->main_course_id, $done_main_subs)) {
                continue;
            }
            $done_main_subs[] = $sub->main_course_id;
            $course = MainCourse::find($sub->main_course_id);
            if ($course == null) {
                continue;
            }
            $main_subs[] = $course;
        }


        return $main_subs;
    }

    function get_students_subjects_papers($administrator_id)
    {

        $main_subs = [];
        $main_subs = Subject::where(
            'academic_class_id',
            $this->id,
        )
            ->where(
                'is_optional',
                '!=',
                1
            )
            ->get();

        $optionals = StudentHasOptionalSubject::where([
            'academic_class_id' => $this->id,
            'administrator_id' => $administrator_id
        ])->get();

        foreach ($optionals as $option) {
            $subject = Subject::find([
                'academic_class_id' => $option->academic_class_id,
                'course_id' => $option->course_id,
            ])->first();
            if ($subject == null) {
                die("Subjet not found.");
            }
            $main_subs[] = $subject;
        }

        return $main_subs;
    }

    function subjects()
    {
        return $this->hasMany(Subject::class, 'academic_class_id');
    }
    function secondary_subjects()
    {
        return $this->hasMany(SecondarySubject::class, 'academic_class_id');
    }

    function main_subjects()
    {
        $my_subs = DB::select("SELECT * FROM subjects WHERE academic_class_id =  $this->id");
        $subs = [];
        $done_ids = [];

        foreach ($my_subs as $sub) {

            if (in_array($sub->main_course_id, $done_ids)) {
                continue;
            }
            $subs[] = $sub;
            $done_ids[] = $sub->main_course_id;
        }
        return $subs;
    }

    function students()
    {
        return $this->hasMany(StudentHasClass::class, 'academic_class_id');
    }
    function get_active_students()
    {
        $students = [];
        foreach ($this->students as $key => $value) {
            if ($value->student == null) {
                continue;
            }
            if ($value->student->status != 1) {
                continue;
            }
            $students[] = $value->student;
        }
        return $students;
    }

     
    function getOptionalSubjectsItems()
    {
        $subs = SecondarySubject::where([
            'academic_class_id' => $this->id,
            'is_optional' => 1,
        ])->get();
        return $subs;

        $subs = [];
        foreach ($this->main_subjects() as $sub) {
            if ($sub->is_optional == 1) {
                $subs[] = $sub;
            }
        }
        return $subs;
    }

    function getNewCurriculumOptionalSubjectsItems()
    {
        $subs = SecondarySubject::where([
            'academic_class_id' => $this->id,
            'is_optional' => 1,
        ])->get();
        return $subs;
    }

    function getOptionalSubjectsAttribute($x)
    {
        $count = 0;

        foreach ($this->main_subjects() as $sub) {
            if (((bool)($sub->is_optional))) {
                $count++;
            }
        }
        return $count;
    }

    function getCompulsorySubjectsAttribute($x)
    {
        $count = 0;
        foreach ($this->main_subjects() as $sub) {
            if (!((bool)($sub->is_optional))) {
                $count++;
            }
        }
        return $count;
    }

    function report_cards()
    {
        return $this->hasMany(StudentReportCard::class);
    }

    //class_teahcer_text
    function getClassTeahcerTextAttribute()
    {
        if ($this->class_teacher == null) {
            return "";
        }
        return $this->class_teacher->name;
    }


    protected  $appends = [ 'class_teahcer_text'];
}
