<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramActivity extends Model
{
    use HasFactory;
    //boot
    public static function boot()
    {
        parent::boot();
        self::deleting(function ($m) {
            throw new \Exception("ProgramActivity cannot be deleted.");
        });

        //creating
        self::creating(function ($m) {
            $u = auth()->user();
            if ($u == null) {
                throw new \Exception("User not found.");
            }
            $ent = Enterprise::find($m->enterprise_id);
            if ($ent == null) {
                throw new \Exception("Enterprise not found.");
            }
            $m->enterprise_id = $ent->id;
        });
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    //belongs to class (program)
    public function academic_class()
    {
        return $this->belongsTo(AcademicClass::class);
    }

    //belongs to lecturer
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    //appends for lecturer_text
    public function getLecturerTextAttribute()
    {
        $l = User::find($this->lecturer_id);
        if ($l != null) {
            return $l->name;
        }
        return null;
    }
    //appends for academic_class_text
    public function getAcademicClassTextAttribute()
    {
        $c = AcademicClass::find($this->academic_class_id);
        if ($c != null) {
            return $c->name;
        }
        return null;
    }

    protected $appends = [
        'lecturer_text',
        'academic_class_text',
    ];
}
