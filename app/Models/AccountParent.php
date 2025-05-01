<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountParent extends Model
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        self::booting(function ($m) {
            die("You cannot delete this account.");
            if ($m->name == 'Other') {
            }
        });
    }


    public function getOtherClientsAttribute($value)
    {
        if ($value == null) {
            return [];
        }
        if (strlen($value) < 1) {
            return [];
        }
        try {
            //$value = explode(',', $value);
        } catch (\Exception $e) {
            $value = [];
        }
        return $value;
    }

    public function setOtherClientsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['other_clients'] = implode(',', $value);
        }
    }


    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function getBudget($term = null)
    {
        return FinancialRecord::where([
            'parent_account_id' => $this->id,
            'type' => 'BUDGET',
        ])->sum('amount');
    }

    public function getExpenditure($term = null)
    {
        return FinancialRecord::where([
            'parent_account_id' => $this->id,
            'type' => 'EXPENDITURE',
        ])->sum('amount');
    }

    //get balance
    public function getBalance($year = null)
    {
        $exp = FinancialRecord::where([
            'parent_account_id' => $this->id,
            'type' => 'EXPENDITURE',
        ])->sum('amount');
        $bud = FinancialRecord::where([
            'parent_account_id' => $this->id,
            'type' => 'BUDGET',
        ])->sum('amount');
        $tot = $bud + $exp;

        return $tot;
    }

    public function getSum($year)
    {

        $tot = 0;
        // $accs = "SELECT id FROM accounts WHERE account_parent_id = $this->id";
        // $sums = "SELECT id FROM accounts WHERE account_parent_id = $this->id";

        foreach (
            Account::where([
                'account_parent_id' => $this->id
            ])->get() as $key => $acc
        ) {
            $tot += Transaction::where([
                'account_id' => $acc->id,
                /* 'is_contra_entry' => 0, */
                'academic_year_id' => $year->id
            ])
                ->where('amount', '<', 0)
                ->sum('amount');
        }

        return $tot;
    }

    //belongs to company
    public function company()
    {
        return $this->belongsTo(Enterprise::class, 'enterprise_id');
    }

    //belongs to client (user)
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    //BELONGS TO manager (user)
    public function manager()
    {
        return $this->belongsTo(User::class, 'administrator_id');
    }

    //project_sections AS ACTIVITIES
    public function project_sections()
    {
        return $this->hasMany(Account::class, 'account_parent_id');
    }
}
