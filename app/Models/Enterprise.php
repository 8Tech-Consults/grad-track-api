<?php

namespace App\Models;

use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Enterprise extends Model
{
    use HasFactory;

    public function owner()
    {
        return $this->belongsTo(Administrator::class, 'administrator_id');
    }
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-M-Y');
    }


    public static function boot()
    {
        parent::boot();
        self::deleting(function ($m) {
            throw new \Exception("Enterprise cannot be deleted.");
            if ($m->id == 1) {
                die("Default enterprise cannot be deleted.");
                return false;
            }
        });

        //creating
        self::creating(function ($m) {
            //check if owner is existing
            $owner = Administrator::find($m->administrator_id);
            if ($owner == null) {
                throw new \Exception("Owner not found.");
                return false;
            }
            //check if owner is does not have an enterprise
            $ent = Enterprise::where([
                'administrator_id' => $m->administrator_id,
            ])->first();
            if ($ent != null) {
                throw new \Exception("Owner already has an enterprise.");
                return false;
            }
            $m->type = 'university';
            $m->wallet_balance = 0;
            $m->can_send_messages = 'No';
        });

        //updating
        self::updating(function ($m) {
            //check if owner is existing
            $owner = Administrator::find($m->administrator_id);
            if ($owner == null) {
                throw new \Exception("Owner not found.");
                return false;
            }
            //check if owner is does not have an enterprise
            $ent = Enterprise::where([
                'administrator_id' => $m->administrator_id,
            ])->where('id', '!=', $m->id)->first();
            if ($ent != null && $ent->id != $m->id) {
                throw new \Exception("Owner already has an enterprise.");
                return false;
            }
        });

        self::created(function ($m) {
            if ($m->id != 1) {
                $owner = User::find($m->administrator_id);
                if ($owner != null) {
                    $owner->enterprise_id = $m->id;
                    $owner->user_type = 'employee';
                    $owner->status = 1;
                    $owner->save();
                }
            }

            Enterprise::my_update($m);
        });

        self::updated(function ($m) {


            if ($m->id != 1) {
                $owner = User::find($m->administrator_id);
                if ($owner != null) {
                    $owner->enterprise_id = $m->id;
                    $owner->user_type = 'employee';
                    $owner->status = 1;
                    $owner->save();
                }
            }

            Enterprise::my_update($m);
        });
    }

    public function updateWalletBalance()
    {
        $sql = "SELECT SUM(amount) as total FROM wallet_records WHERE enterprise_id = $this->id";
        $total = DB::select($sql);
        $this->wallet_balance = $total[0]->total;
        $this->save();
    }
    public function active_term()
    {
        $t = Term::where([
            'enterprise_id' => $this->id,
            'is_active' => 1,
        ])->orderBy('id', 'desc')->first();
        return $t;
    }

    public function active_academic_year()
    {
        $t = AcademicYear::where([
            'enterprise_id' => $this->id,
            'is_active' => 1,
        ])->first();
        return $t;
    }

    public function dpYear()
    {

        return $this->active_academic_year();
        $dp = AcademicYear::where([
            'enterprise_id' => $this->id,
            'id' => $this->dp_year,
        ])->first();

        if ($dp == null) {
            $t = AcademicYear::where([
                'enterprise_id' => $this->id,
                'is_active' => 1,
            ])->first();
            if ($t == null) {
                $t = AcademicYear::where([
                    'enterprise_id' => $this->id,
                ])->first();
            }
            if ($t != null) {
                DB::update("update enterprises set dp_year = ? where id = ? ", [$t->id, $this->id]);
            }
            $dp = AcademicYear::where([
                'enterprise_id' => $this->id,
                'id' => $this->dp_year,
            ])->first();
        }

        return $dp;
    }

    public function dpTerm()
    {

        $dt = Term::where([
            'enterprise_id' => $this->id,
            'id' => $this->dp_term_id,
        ])->first();

        if ($dt == null) {
            $t = Term::where([
                'enterprise_id' => $this->dp_term_id,
                'is_active' => 1,
            ])->first();
            if ($t == null) {
                $t = Term::where([
                    'enterprise_id' => $this->id,
                ])->first();
            }
            if ($t != null) {
                DB::update(
                    "update enterprises set dp_year = ?, dp_term_id = ? where id = ? ",
                    [
                        $t->academic_year_id,
                        $t->id,
                        $this->id,
                    ]
                );
            }
            $dt = Term::where([
                'enterprise_id' => $this->id,
                'id' => $t->id,
            ])->first();
        }

        return $dt;
    }

    public function academic_years()
    {
        return $this->hasMany(AcademicYear::class, 'enterprise_id');
    }

    public static function main_bank_account($m)
    {
        $fees_acc = Account::where([
            'type' => 'FEES_ACCOUNT',
            'enterprise_id' => $m->id,
        ])->first();
        if ($fees_acc == null) {
            $ac =  new Account();
            $ac->name = 'SCHOOL FEES ACCOUNT';
            $ac->enterprise_id = $m->id;
            $ac->type = 'FEES_ACCOUNT';
            $ac->administrator_id = $m->administrator_id;
            $ac->save();
        }
        $fees_acc = Account::where([
            'type' => 'FEES_ACCOUNT',
            'enterprise_id' => $m->id,
        ])->first();
        if ($fees_acc == null) {
            die("Fees account not found");
        }
        return $fees_acc;
    }
    public static function my_update($m)
    {
        if ($m->id == 1) {
            return;
        }
        $owner = Administrator::find($m->administrator_id);
        if ($owner != null) {
            $owner->enterprise_id = $m->id;
            $owner->user_type = 'employee';
            $owner->save();
        }
 
     
 

        /* academic year processing */

        $ay = AcademicYear::where([
            'enterprise_id' => $m->id
        ])->first();

        if ($ay == null) {
            $ay = new AcademicYear();
            $ay->enterprise_id = $m->id;
            $ay->name = date('Y');
            $ay->details = date('Y');
            $now = Carbon::now();
            $ay->starts = $now;
            $then =  $now->addYear(1);
            $ay->ends = $then;
            $ay->is_active = 1;
            $ay->process_data = 'Yes';
            $ay->save();
        } else {
        }
        //get classes in this academic year
        $classes = AcademicClass::where([
            'academic_year_id' => $ay->id,
        ])->get();
        //if no class, create a default class
        if ($classes->count() == 0) {
            AcademicYear::generate_classes($ay);
        }
    }

    //getter for dp_year
    public function getDpYearAttribute()
    {
        $d = $this->dpYear();
        if ($d == null) {
            return null;
        }
        return $d->id;
    }
}
