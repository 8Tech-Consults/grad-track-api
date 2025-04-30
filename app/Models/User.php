<?php

namespace App\Models;

use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Form\Field\BelongsToMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as RelationsBelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Administrator implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'admin_users';

    //creating

    //boot
    public static function boot()
    {
        parent::boot();
        //deleting
        self::deleting(function ($m) {
            throw new \Exception("You cannot delete this user. Please contact the system administrator.", 1);
            return false;
        });

        self::creating(function ($m) {
            $user_with_same_email = User::where([
                'email' => $m->email,
            ])->first();

            if ($user_with_same_email != null) {
                throw new \Exception("User with same email (" . $m->email . ") already exists", 1);
            }
            $user_with_same_username = User::where([
                'username' => $m->email,
            ])->first();
            if ($user_with_same_username != null) {
                throw new \Exception("User with same username (" . $m->email . ") already exists", 1);
            }

            $m->username = $m->email;

            $m = self::do_prepare($m);

            return $m;
        });
        //updating
        self::updating(function ($m) {

            $user_with_same_email = User::where([
                'email' => $m->email,
            ])->where('id', '!=', $m->id)->first();
            if ($user_with_same_email != null) {
                throw new \Exception("User with same email (" . $m->email . ") already exists", 1);
            }
            $user_with_same_username = User::where([
                'username' => $m->email,
            ])->where('id', '!=', $m->id)->first();
            if ($user_with_same_username != null) {
                throw new \Exception("User with same username (" . $m->email . ") already exists", 1);
            }
            $m->username = $m->email;

            $m = self::do_prepare($m);
            return $m;
        });

        //updated
        self::updated(function ($m) {
            self::do_finalize($m);
        });

        //created
        self::created(function ($m) {
            self::do_finalize($m);
        });
    }


    public static function do_finalize($m)
    {
        //create_roles
        self::create_roles($m->id, $m->roles_text);
        //update roles
        $roles = AdminRoleUser::where([
            'user_id' => $m->id
        ])->get();
        $roles = AdminRoleUser::where([
            'user_id' => $m->id
        ])->get()->pluck('role_id')->toArray();
        //covert them to strings
        $roles = array_map('strval', $roles);
        //convert to json
        $roles_text = json_encode($roles);
        $sql = "UPDATE admin_users SET roles_text = '" . $roles_text . "' WHERE id = " . $m->id;
        DB::update($sql);
    }
    public static function do_prepare($m)
    {
        $user_with_same_email = User::where([
            'email' => $m->email,
        ])->where('id', '!=', $m->id)->first();
        if ($user_with_same_email != null) {
            throw new \Exception("User with same email (" . $m->email . ") already exists", 1);
        }
        $user_with_same_username = User::where([
            'username' => $m->email,
        ])->where('id', '!=', $m->id)->first();
        if ($user_with_same_username != null) {
            throw new \Exception("User with same username (" . $m->email . ") already exists", 1);
        }

        if (
            $m->password == null ||
            strlen($m->password) < 4
        ) {
            $m->password = password_hash('4321', PASSWORD_DEFAULT);
        }

        if (
            $m->password == null ||
            strlen($m->password) < 4
        ) {
            $m->password = password_hash('4321', PASSWORD_DEFAULT);
        }

        $m->name = $m->first_name . ' ' . $m->last_name;

        return $m;
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }


    /**
     * The attribootes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function bills()
    {
        return $this->hasMany(StudentHasFee::class);
    }
    public function ent()
    {
        return $this->belongsTo(Enterprise::class, 'enterprise_id');
    }

    public function stream()
    {
        return $this->belongsTo(AcademicClassSctream::class, 'stream_id');
    }

    public function services()
    {
        return $this->hasMany(ServiceSubscription::class, 'administrator_id');
    }

    public static function createParent($s)
    {
        $p = $s->getParent();
        if ($p != null) {
            $s->parent_id = $p->id;
            $s->save();
            return $s;
        }

        if (strtolower($s->user_type) != 'student') {
            return $p;
        }

        if ($p == null) {
            $p = new Administrator();
            $phone_number_1 = Utils::prepare_phone_number($s->phone_number_1);

            if (
                Utils::phone_number_is_valid($phone_number_1)
            ) {
                $p->username = $phone_number_1;
            }

            $p->password = password_hash('4321', PASSWORD_DEFAULT);
            if (
                $s->emergency_person_name != null &&
                strlen($s->emergency_person_name) > 2
            ) {
                $p->name = $s->emergency_person_name;
            }
            if (
                $s->mother_name != null &&
                strlen($s->mother_name) > 2
            ) {
                $p->name = $s->mother_name;
            }
            if (
                $s->father_name != null &&
                strlen($s->father_name) > 2
            ) {
                $p->name = $s->father_name;
            }

            if (
                $p->name == null ||
                strlen($p->name) < 2
            ) {
                $p->name = 'Parent of ' . $s->name;
            }

            $p->enterprise_id = $s->enterprise_id;
            $p->home_address = $s->home_address;
            $names = explode(' ', $p->name);
            if (isset($names[0])) {
                $p->first_name = $names[0];
            }
            if (isset($names[1])) {
                $p->given_name = $names[1];
            }
            if (isset($names[2])) {
                $p->last_name  =  $names[2];
            }

            $p->phone_number_1 = $phone_number_1;
            $p->nationality = $s->nationality;
            $p->religion = $s->religion;
            $p->emergency_person_name = $s->emergency_person_name;
            $p->emergency_person_phone = $s->emergency_person_phone;
            $p->status = 1;
            $p->user_type = 'parent';
            $p->email = 'p' . $s->email;
            $p->user_id = 'p' . $s->user_id;
            try {
                $p->save();
                $s->parent_id = $p->id;
                $s->save();
            } catch (\Throwable $th) {
                $s->parent_id = null;
                $s->save();
            }

            $p = User::find($p->id);
            if ($p != null) {
                //add role with id 17
                try {
                    $r = new AdminRoleUser();
                    $r->role_id = 17;
                    $r->user_id = $p->id;
                    $r->save();
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        }
        return  $p;
    }
    public function getParent()
    {
        $s = $this;
        $p = User::where([
            'user_type' => 'parent',
            'enterprise_id' => $s->enterprise_id,
            'id' => $s->parent_id,
        ])->first();

        $phone_number_1 = Utils::prepare_phone_number($s->phone_number_1);

        if (
            $p == null &&
            Utils::phone_number_is_valid($phone_number_1)
        ) {
            $p = User::where([
                'user_type' => 'parent',
                'enterprise_id' => $s->enterprise_id,
                'phone_number_1' => $phone_number_1,
            ])->first();
        }


        if (
            $p == null &&
            $s->user_id != null &&
            strlen($s->user_id) > 0
        ) {
            $p = User::where([
                'user_type' => 'parent',
                'enterprise_id' => $s->enterprise_id,
                'user_id' => $s->user_id,
            ])->first();
        }
        if (
            $p == null &&
            $s->school_pay_payment_code != null &&
            strlen($s->school_pay_payment_code) > 4
        ) {
            $p = User::where([
                'user_type' => 'parent',
                'enterprise_id' => $s->enterprise_id,
                'school_pay_payment_code' => $s->school_pay_payment_code,
            ])->first();
        }
        return $p;
    }


    public function report_cards()
    {
        return $this->hasMany(StudentReportCard::class, 'student_id');
    }

    public function active_term_services()
    {
        $term = $this->ent->active_term();
        if ($term == null) {
            return [];
        }
        return ServiceSubscription::where([
            'administrator_id' => $this->id,
            'due_term_id' => $term->id,
        ])->get();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }



    public function roles(): RelationsBelongsToMany
    {
        $pivotTable = config('admin.database.role_users_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'role_id');
    }
    //getter for name_text
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
    public function getNameTextAttribute()
    {
        //if is student, add current class
        if (strtolower($this->user_type) == 'student') {
            $class = AcademicClass::find($this->current_class_id);
            if ($class == null) {
                return $this->name;
            }
            return $this->name . ' - ' . $class->name_text;
        }
        return $this->name;
    }

    public function current_class()
    {
        return $this->belongsTo(AcademicClass::class, 'current_class_id');
    }

    //get my subjects
    public function my_subjects()
    {
        $active_term = $this->ent->active_term();
        $academic_year_id = $active_term->academic_year_id;
        $subjects = Subject::where([
            'academic_year_id' => $academic_year_id,
        ])->get();
        $my_subjects = [];
        foreach ($subjects as $key => $val) {
            $teacher_ids = [
                $val->subject_teacher,
                $val->teacher_1,
                $val->teacher_2,
                $val->teacher_3,
            ];
            if (in_array($this->id, $teacher_ids)) {
                $my_subjects[] = $val;
            }
        }
        return $my_subjects;
    }

    public function update_fees() {}


    //belings to theology_stream_id
    public function theology_stream()
    {
        return $this->belongsTo(TheologyStream::class, 'theology_stream_id');
    }

    //belongs to theology_class using current_theology_class_id
    public function theology_class()
    {
        return $this->belongsTo(TheologyClass::class, 'current_theology_class_id');
    }

    //make plain_password not returned in json or array or object
    public function getPlainPasswordAttribute()
    {
        return null;
    }


    public function sendEmailVerificationNotification()
    {
        $mail_verification_token =  rand(100000, 999999);
        $this->mail_verification_token = $mail_verification_token;
        $this->save();

        $url = url('verification-mail-verify?tok=' . $mail_verification_token . '&email=' . $this->email);
        $from = env('APP_NAME') . " Team.";

        $mail_body =
            <<<EOD
        <p>Dear <b>$this->name</b>,</p>
        <p>Please use the code below to verify your email address.</p><p style="font-size: 25px; font-weight: bold; text-align: center; color:rgb(7, 76, 194); "><b>$mail_verification_token</b></p>
        <p>Or clink on the link below to verify your email address.</p>
        <p><a href="{$url}">Verify Email Address</a></p>
        <p>Best regards,</p>
        <p>{$from}</p>
        EOD;

        // $full_mail = view('mails/mail-1', ['body' => $mail_body, 'title' => 'Email Verification']);

        try {
            $day = date('Y-m-d');
            $data['body'] = $mail_body;
            $data['data'] = $data['body'];
            $data['name'] = $this->name;
            $data['email'] = $this->email;
            $data['subject'] = 'Email Verification - ' . env('APP_NAME') . ' - ' . $day . ".";
            Utils::mail_sender($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    //function that sends password reset email. it is exacly the same as sendEmailVerificationNotification, only that the words are different
    public function sendPasswordResetNotification()
    {
        $mail_verification_token =  rand(100000, 999999);
        $this->mail_verification_token = $mail_verification_token;
        $this->save();

        $url = url('password-reset-screen?tok=' . $mail_verification_token . '&email=' . $this->email);
        $from = env('APP_NAME') . " Team.";

        $mail_body =
            <<<EOD
        <p>Dear <b>$this->name</b>,</p>
        <p>Please use the code below to reset your password.</p><p style="font-size: 25px; font-weight: bold; text-align: center; color:rgb(7, 76, 194); "><b>$mail_verification_token</b></p>
        <p>Or clink on the link below to reset your password.</p>
        <p><a href="{$url}">Reset Password</a></p>
        <p>Best regards,</p>
        <p>{$from}</p>
        EOD;

        try {
            $day = date('Y-m-d');
            $data['body'] = $mail_body;
            $data['data'] = $data['body'];
            $data['name'] = $this->name;
            $data['email'] = $this->email;
            $data['subject'] = 'Password Reset - ' . env('APP_NAME') . ' - ' . $day . ".";
            Utils::mail_sender($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    //create roles for user
    public static function create_roles($user_id, $roles_text)
    {
        if ($roles_text == null || strlen($roles_text) < 3) {
            return;
        }
        $roles = [];
        try {
            $roles = json_decode($roles_text);
        } catch (\Throwable $th) {
            //throw $th;
        }
        if ($roles == null) {
            return;
        }
        if (count($roles) < 1) {
            return;
        }
        //delete all roles for this user
        $get_current_roles = AdminRoleUser::where([
            'user_id' => $user_id
        ])->get();
        foreach ($get_current_roles as $role) {
            //check if role_id is not in new roles
            if (!in_array($role->role_id, $roles)) {
                AdminRoleUser::where([
                    'user_id' => $user_id,
                    'role_id' => $role->role_id
                ])->delete();
            }
        }

        //now add new roles
        foreach ($roles as $role) {
            $r = AdminRoleUser::where([
                'user_id' => $user_id,
                'role_id' => $role
            ])->first();
            if ($r == null) {
                //first chekc if role exisits
                $role_item = AdminRole::where([
                    'id' => $role
                ])->first();
                if ($role_item == null) {
                    continue;
                }
                $r = new AdminRoleUser();
                $r->user_id = $user_id;
                $r->role_id = $role;
                $r->save();
            }
        }
    }
}
