<?php

namespace Encore\Admin\Auth\Database;

use App\Models\AcademicClass;
use App\Models\AcademicClassSctream;
use App\Models\AcademicYear;
use App\Models\AdminRole;
use App\Models\AdminRoleUser;
use App\Models\Enterprise;
use App\Models\ServiceSubscription;
use App\Models\StudentHasClass;
use App\Models\StudentHasFee;
use App\Models\StudentHasTheologyClass;
use App\Models\Subject;
use App\Models\TheologyClass;
use App\Models\TheologyStream;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utils;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable1;
use Mockery\Matcher\Subset;

/**
 * Class Administrator.
 *
 * @property Role[] $roles
 */
class Administrator extends Model implements AuthenticatableContract, JWTSubject
{
    use Authenticatable;
    use HasPermissions;
    use DefaultDatetimeFormat;


    public function getParentPhonNumber()
    {
        if (
            $this->phone_number_1 != null &&
            strlen($this->phone_number_1) > 2
        ) {
            return $this->phone_number_1;
        }
        if (
            $this->phone_number_2 != null &&
            strlen($this->phone_number_2) > 2
        ) {
            return $this->phone_number_2;
        }
        if (
            $this->spouse_phone != null &&
            strlen($this->spouse_phone) > 2
        ) {
            return $this->spouse_phone;
        }
        if (
            $this->father_phone != null &&
            strlen($this->father_phone) > 2
        ) {
            return $this->father_phone;
        }
        if (
            $this->mother_phone != null &&
            strlen($this->mother_phone) > 2
        ) {
            return $this->mother_phone;
        }
        if (
            $this->emergency_person_phone == null &&
            strlen($this->emergency_person_phone) > 2
        ) {
            return $this->emergency_person_phone;
        }
        if ($this->username == null) {
            return $this->username;
        }

        if ($this->user_type == 'parent') {
            if ($this->kids != null) {
                if (!empty($this->kids)) {
                    if (isset($this->kids[0])) {
                        $k = $this->kids[0];
                        if ($k->user_type == 'student') {
                            $this->phone_number_1 = $k->getParentPhonNumber();
                            if ($this->phone_number_1 != null) {
                                if (strlen($this->phone_number_1) > 3) {
                                    try {
                                        $this->save();
                                    } catch (\Throwable $th) {
                                    }
                                    return $this->phone_number_1;
                                }
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function get_initials()
    {
        //get initials from first name, given name and last name if any not null or empty
        $initials = "";
        if ($this->first_name != null) {
            if (strlen($this->first_name) > 0) {
                $initials .= substr($this->first_name, 0, 1);
            }
        }
        if ($this->given_name != null) {
            if (strlen($this->given_name) > 0) {
                $initials .= substr($this->given_name, 0, 1);
            }
        }
        if ($this->last_name != null) {
            if (strlen($this->last_name) > 0) {
                $initials .= substr($this->last_name, 0, 1);
            }
        }
        //if $initials is empty, get initials from name
        if (strlen($initials) < 1) {
            if ($this->name != null) {
                if (strlen($this->name) > 1) {
                    $initials .= substr($this->name, 0, 2);
                }
            }
        }
        return strtoupper($initials);
    }
    public static function my_update($m) {}

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $connection = config('admin.database.connection') ?: config('database.default');

        $this->setConnection($connection);

        $this->setTable(config('admin.database.users_table'));

        parent::__construct($attributes);
    }

    /**
     * Get avatar attribute.
     *
     * @param string $avatar
     *
     * @return string
     */


    public function current_class()
    {
        return $this->belongsTo(AcademicClass::class, 'current_class_id');
    }

    public function stream()
    {
        return $this->belongsTo(AcademicClassSctream::class, 'stream_id');
    }

    public function current_theology_class()
    {
        return $this->belongsTo(TheologyClass::class, 'current_theology_class_id');
    }

    public function getAvatarAttribute($avatar)
    {

        if ($avatar == null || (strlen($avatar) < 3 || str_contains($avatar, 'laravel-admin'))) {
            $default = url('user.jpeg');
            return $default;
        }
        $avatar = str_replace('images/', '', $avatar);
        $link = 'storage/images/' . $avatar;

        if (!file_exists(public_path($link))) {
            $link = 'user.jpeg';
        }
        return url($link);
    }

    public function getAvatarPath()
    {
        $exps = explode('/', $this->avatar);
        if (empty($exps)) {
            return $this->avatar;
        }
        $avatar = $exps[(count($exps) - 1)];

        $link = 'storage/images/' . $avatar;

        if (!file_exists(public_path($link))) {
            $link = 'user.jpeg';
        }
        return  $link;
        //$real_avatar=
    }

    /**
     * A user has and belongs to many roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        $pivotTable = config('admin.database.role_users_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'role_id');
    }

    public function enterprise()
    {
        $e = Enterprise::find($this->enterprise_id);
        if ($e == null) {
            $this->enterprise_id = 1;
            $this->save();
        }
        return $this->belongsTo(Enterprise::class);
    }
    public function ent()
    {
        $e = Enterprise::find($this->enterprise_id);
        if ($e == null) {
            $this->enterprise_id = 1;
            $this->save();
        }
        return $this->belongsTo(Enterprise::class, 'enterprise_id');
    }

    public function parent()
    {
        return $this->belongsTo(Administrator::class, 'parent_id');
    }


    public function services()
    {
        return $this->hasMany(ServiceSubscription::class, 'administrator_id');
    }
    public function kids()
    {
        return $this->hasMany(Administrator::class, 'parent_id');
    }

    public function classes()
    {
        return $this->hasMany(StudentHasClass::class);
    }


    public function get_my_theology_classes()
    {

        $year =  $this->ent->active_academic_year();
        if ($year == null) {
            return [];
        }
        if ($this->user_type == 'employee') {
            $sql1 = "SELECT theology_classes.id FROM theology_subjects,theology_classes WHERE
                (
                    subject_teacher = {$this->id} OR
                    teacher_1 = {$this->id} OR
                    teacher_2 = {$this->id} OR
                    teacher_3 = {$this->id}
                ) AND (
                    theology_subjects.theology_class_id = theology_classes.id
                ) AND (
                    theology_classes.academic_year_id = {$year->id}
                )
            ";

            if (
                $this->isRole('dos') ||
                $this->isRole('bursar') ||
                $this->isRole('admin')
            ) {
                $sql1 = "SELECT theology_classes.id FROM theology_classes WHERE academic_year_id = {$year->id}";
            }

            $sql = "SELECT * FROM theology_classes WHERE id IN
            ( $sql1 )
            ";

            $clases = [];
            foreach (DB::select($sql) as $key => $v) {
                $u = Administrator::find($v->class_teahcer_id);
                if ($u != null) {
                    $v->class_teacher_name = $u->name;
                } else {
                    $v->class_teacher_name  = "";
                }
                $v->students_count = 0;
                foreach (
                    StudentHasTheologyClass::where([
                        'theology_class_id' => $v->id
                    ])->get() as $_value
                ) {
                    if ($_value->student == null) {
                        continue;
                    }
                    if ($_value->student->status != 1) {
                        continue;
                    }
                    $v->students_count++;
                }

                $clases[] = $v;
            }
            return $clases;
        }
    }


    public function get_my_all_classes()
    {
        //$theology_classes = $this->get_my_theology_classes();
        $classes = [];
        $secular_classes = $this->get_my_classes();
        foreach ($secular_classes as $key => $value) {
            $value->section = 'Secular';
            $classes[] = $value;
        }
        /*         foreach ($theology_classes as $key => $value) {
            $value->section = 'Theology';
            $classes[] = $value;
        } */
        return $classes;
    }

    public function get_my_classes()
    {

        $year =  $this->ent->active_academic_year();
        if ($year == null) {
            return [];
        }
        if ($this->user_type == 'employee') {
            $sql1 = "SELECT academic_classes.id FROM subjects,academic_classes WHERE
                (
                    subject_teacher = {$this->id} OR
                    teacher_1 = {$this->id} OR
                    teacher_2 = {$this->id} OR
                    teacher_3 = {$this->id}
                ) AND (
                    subjects.academic_class_id = academic_classes.id
                ) AND (
                    academic_classes.academic_year_id = {$year->id}
                )
            ";

            if (
                $this->isRole('dos') ||
                $this->isRole('bursar') ||
                $this->isRole('admin')
            ) {
                $sql1 = "SELECT academic_classes.id FROM academic_classes WHERE academic_year_id = {$year->id}";
            }

            $sql = "SELECT * FROM academic_classes WHERE id IN
            ( $sql1 )
            ";

            $clases = [];
            foreach (DB::select($sql) as $key => $v) {
                $u = Administrator::find($v->class_teahcer_id);
                if ($u != null) {
                    $v->class_teacher_name = $u->name;
                } else {
                    $v->class_teacher_name  = "";
                }
                $v->students_count = 0;
                foreach (
                    StudentHasClass::where([
                        'academic_class_id' => $v->id
                    ])->get() as $_value
                ) {
                    if ($_value->student == null) {
                        continue;
                    }
                    if ($_value->student->status != 1) {
                        continue;
                    }
                    $v->students_count++;
                }
                $clases[] = $v;
            }
            return $clases;
        }
        return [];
    }



    public function get_my_students($u)
    {
        if ($u == null) {
            return [];
        }

        $students = [];
        $isAdmin = false;


        return $students;
    }


    public function get_my_subjetcs()
    {

        $active_academic_year_id = 0;
        if ($this->ent != null) {
            $y = $this->ent->active_academic_year();
            if ($y != null) {
                $active_academic_year_id = $y->id;
            }
        }

        if ($this->user_type == 'employee') {


            $isAdmin = false;

            if (
                $this->isRole('admin') ||
                $this->isRole('dos') ||
                $this->isRole('hm')
            ) {
                $isAdmin = true;
            }

            if ($isAdmin) {
                $sql1 = "SELECT *, subjects.id as id FROM subjects,academic_classes WHERE  (
                    subjects.academic_class_id = academic_classes.id
                ) AND (
                    academic_classes.academic_year_id = $active_academic_year_id
                )
            ";
            } else {
                $sql1 = "SELECT *, subjects.id as id FROM subjects,academic_classes WHERE
                (
                    subject_teacher = {$this->id} OR
                    teacher_1 = {$this->id} OR
                    teacher_2 = {$this->id} OR
                    teacher_3 = {$this->id}
                ) AND (
                    subjects.academic_class_id = academic_classes.id
                ) AND (
                    academic_classes.academic_year_id = $active_academic_year_id
                )
            ";
            }



            $data = [];
            foreach (DB::select($sql1) as $key => $v) {

                $u = Administrator::where([
                    'id' => $v->subject_teacher
                ])
                    ->orWhere('id', $v->teacher_1)
                    ->orWhere('id', $v->teacher_2)
                    ->orWhere('id', $v->teacher_3)->first();

                if ($u != null) {
                    $v->subject_teacher_name = $u->name;
                } else {
                    $v->subject_teacher_name  = "";
                }
                $data[] = $v;
            }
            return $data;
        }
    }


    public function get_my_theology_subjetcs()
    {

        $active_academic_year_id = 0;
        if ($this->ent != null) {
            $y = $this->ent->active_academic_year();
            if ($y != null) {
                $active_academic_year_id = $y->id;
            }
        }

        if ($this->user_type == 'employee') {
            $isAdmin = false;
            if (
                $this->isRole('admin') ||
                $this->isRole('dos') ||
                $this->isRole('hm')
            ) {
                $isAdmin = true;
            }

            if ($isAdmin) {
                $sql1 = "SELECT *, theology_subjects.id as id FROM theology_subjects,theology_classes WHERE  (
                    theology_subjects.theology_class_id = theology_classes.id
                ) AND (
                    theology_classes.academic_year_id = $active_academic_year_id
                )
            ";
            } else {

                $sql1 = "SELECT *, theology_subjects.id as id FROM theology_subjects,theology_classes WHERE (
                    subject_teacher = {$this->id} OR
                    teacher_1 = {$this->id} OR
                    teacher_2 = {$this->id} OR
                    teacher_3 = {$this->id}
                ) AND (
                    theology_subjects.theology_class_id = theology_classes.id
                ) AND (
                    theology_classes.academic_year_id = $active_academic_year_id
                )
                ";
            }



            $data = [];
            foreach (DB::select($sql1) as $key => $v) {

                $u = Administrator::where([
                    'id' => $v->subject_teacher
                ])
                    ->orWhere('id', $v->teacher_1)
                    ->orWhere('id', $v->teacher_2)
                    ->orWhere('id', $v->teacher_3)->first();

                if ($u != null) {
                    $v->subject_teacher_name = $u->name;
                } else {
                    $v->subject_teacher_name  = "";
                }
                $data[] = $v;
            }
            return $data;
        }
    }



    public function theology_classes()
    {
        return $this->hasMany(StudentHasTheologyClass::class, 'administrator_id');
    }

    public function THEclasses()
    {
        return $this->hasMany(StudentHasClass::class);
    }

    public function bills()
    {
        return $this->hasMany(StudentHasFee::class);
    }




    public function getActiveClass()
    {
        $acc = null;
        $data = DB::select("SELECT * FROM academic_classes WHERE id = $this->current_class_id");
        if ($data != null) {
            if (isset($data[0])) {
                $acc = $data[0];
            }
        }
        return $acc;
    }


    public function main_role()
    {
        return $this->belongsTo(AdminRole::class, 'main_role_id');
    }

    /**
     * A User has and belongs to many permissions.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        $pivotTable = config('admin.database.user_permissions_table');

        $relatedModel = config('admin.database.permissions_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'permission_id');
    }

    public function getUserNumberAttribute($x)
    {
        if ($x == null || (strlen($x) < 3)) {
            if ($this->status != 1) return 'N/A';
            $created = Carbon::parse($this->created_at);
            $year = $created->format('Y');
            $x = $this->ent->short_name . "-" . $year . "-" . $this->id;
            $x = strtoupper($x);
            $this->user_number = $x;
            //$u->qr_code =  Utils::generate_qrcode($this->user_number);
            $this->save();
            return $x;
        }
        return $x;
    }

    public function get_finances() {}

    //GETTER FOR current_class_text
    public function getCurrentClassTextAttribute($x)
    {
        $class = AcademicClass::find($this->current_class_id);
        if ($class == null) {
            return 'N/A';
        }
        return $class->name;
    }

    //appends
    protected $appends = ['balance', 'current_class_text', 'verification'];

    //getter for balance
    public function getBalanceAttribute()
    {
        return 0;
    }

    //getter for verification
    public function getVerificationAttribute() {}


    public function update_theo_classes()
    {
        if (strtolower($this->user_type) != 'student') {
            return;
        }

        if ($this->status != 1) {
            return;
        }



        if ($this->theology_stream_id != null) {
            $theology_stream = TheologyStream::find($this->theology_stream_id);
            if ($theology_stream != null) {
                $this->current_theology_class_id = $theology_stream->theology_class_id;
            }
        }



        if ($this->current_theology_class_id != null) {
            $theology_class = TheologyClass::find($this->current_theology_class_id);
            if ($theology_class != null) {
                $student_has_theo_class = StudentHasTheologyClass::where([
                    'theology_class_id' => $theology_class->id,
                    'administrator_id' => $this->id,
                ])->first();


                if ($student_has_theo_class == null) {
                    $student_has_theo_class = new StudentHasTheologyClass();
                }

                $student_has_theo_class->theology_class_id = $theology_class->id;
                $student_has_theo_class->administrator_id = $this->id;
                $student_has_theo_class->theology_stream_id = $this->theology_stream_id;
                $student_has_theo_class->enterprise_id = $this->enterprise_id;
                $student_has_theo_class->save();
            }
        }
    }
}
