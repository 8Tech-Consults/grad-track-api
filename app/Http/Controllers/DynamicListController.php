<?php

namespace App\Http\Controllers;

use App\Models\AdminRoleUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Traits\ApiResponser;
use Encore\Admin\Auth\Database\Administrator;

class DynamicListController extends Controller
{
    use ApiResponser;

    /**
     * Handle dynamic listing of any model's data with filtering, sorting, and pagination.
     *
     * Expected Query Params:
     *  - model=SomeModel           (required) Eloquent model name in App\Models\
     *  - <field>_like=someValue    (optional) "WHERE <field> LIKE '%someValue%'"
     *  - <field>_gt=10             (optional) "WHERE <field> > 10"
     *  - <field>_lt=5              (optional) "WHERE <field> < 5"
     *  - <field>_gte=10            (optional) "WHERE <field> >= 10"
     *  - <field>_lte=5             (optional) "WHERE <field> <= 5"
     *  - <field>=value             (optional) Default "WHERE <field> = value"
     *  - sort_by=<field>           (optional) Sorting field
     *  - sort_dir=asc|desc         (optional) Sorting direction
     *  - page=<num>                (optional) Pagination page
     *  - per_page=<num>            (optional) Items per page
     *  - is_not_for_company=yes    (optional) Skip enterprise_id filter
     *  - is_not_for_user=yes       (optional) Skip user-based filter on user_id or administrator_id
     *
     * Usage Example:
     * GET /api/dynamic-list?model=User&first_name_like=John&sort_by=id&sort_dir=desc
     */
    public function enterprise_owners(Request $request)
    {
        $user_ids = AdminRoleUser::where('role_id', 2)->pluck('user_id')->toArray();
        $user_ids = array_unique($user_ids);

        $users = Administrator::whereIn('id', $user_ids)->get();
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->id,
                'name' => $user->name.' ('.$user->email.')',
            ];
        }
        $responseData = [
            'items' => $data,
            'pagination' => [
                'current_page' => 1,
                'per_page'     => count($users),
                'total'        => count($users),
                'last_page'    => 1,
            ],
        ];
        return $this->success($responseData, "Data retrieved successfully.");
    }
    public function index(Request $request)
    {
        // 1) Check authentication
        $u = auth('api')->user();
        if (!$u) {
            // return $this->error("User not found or not authenticated.");
        }
        $u   = Administrator::find(1);

        // 2) Get the 'model' parameter
        $modelName = $request->get('model');
        if (!$modelName) {
            return $this->error("Missing 'model' parameter.");
        }

        // Construct the FQCN, e.g. "\App\Models\User"
        $modelClass = "\\App\\Models\\" . Str::studly($modelName);
        if (!class_exists($modelClass)) {
            return $this->error("Model [{$modelName}] does not exist.");
        }

        // 3) Create an instance to retrieve table information
        $modelInstance = new $modelClass;
        $table = $modelInstance->getTable();

        // Ensure the table actually exists in the database
        if (!Schema::hasTable($table)) {
            return $this->error("Table [{$table}] for model [{$modelName}] does not exist.");
        }

        // Fetch the valid columns
        $validColumns = Schema::getColumnListing($table);

        // Initialize query builder
        $query = $modelClass::query();

        // 4) Additional Automatic Filters
        // a) If "is_not_for_company=yes" is NOT present, filter by enterprise_id
        $isNotForCompany = $request->query('is_not_for_company');
        if ($isNotForCompany !== 'yes') {
            //if user is not $u->isRole('super-admin')
            if (!$u->isRole('super-admin')) {
                if (in_array('enterprise_id', $validColumns)) {
                    $query->where('enterprise_id', $u->enterprise_id);
                }
            }
        }

        // b) If "is_not_for_user=yes" is NOT present, check for 'administrator_id' or 'user_id'
        $isNotForUser = $request->query('is_not_for_user');
        if ($isNotForUser !== 'yes') {
            if (!$u->isRole('super-admin')) {
                if (in_array('administrator_id', $validColumns)) {
                    $query->where('administrator_id', $u->id);
                } elseif (in_array('user_id', $validColumns)) {
                    $query->where('user_id', $u->id);
                }
            }
        }


        // 5) Parse Additional Query Parameters
        $allQueryParams = $request->query();

        // Reserved keys that we skip from filter logic
        $reservedKeys = [
            'model',
            'sort_by',
            'sort_dir',
            'page',
            'per_page',
            'is_not_for_company',
            'is_not_for_user'
        ];

        foreach ($allQueryParams as $param => $value) {
            // Skip if it's a reserved key
            if (in_array($param, $reservedKeys)) {
                continue;
            }

            // Check for pattern <field>_like, <field>_gt, etc.
            if (preg_match('/^(.*)_like$/', $param, $matches)) {
                $field = $matches[1];
                if (in_array($field, $validColumns)) {
                    $query->where($field, 'LIKE', "%{$value}%");
                }
            } elseif (preg_match('/^(.*)_gt$/', $param, $matches)) {
                $field = $matches[1];
                if (in_array($field, $validColumns)) {
                    $query->where($field, '>', $value);
                }
            } elseif (preg_match('/^(.*)_lt$/', $param, $matches)) {
                $field = $matches[1];
                if (in_array($field, $validColumns)) {
                    $query->where($field, '<', $value);
                }
            } elseif (preg_match('/^(.*)_gte$/', $param, $matches)) {
                $field = $matches[1];
                if (in_array($field, $validColumns)) {
                    $query->where($field, '>=', $value);
                }
            } elseif (preg_match('/^(.*)_lte$/', $param, $matches)) {
                $field = $matches[1];
                if (in_array($field, $validColumns)) {
                    $query->where($field, '<=', $value);
                }
            } else {
                // Could be direct equality if <field>=value is in columns
                if (in_array($param, $validColumns)) {
                    $query->where($param, '=', $value);
                }
            }
        }

        // 6) Sorting
        $sortBy = $request->get('sort_by');
        $sortDir = $request->get('sort_dir', 'asc');
        if ($sortBy && in_array($sortBy, $validColumns)) {
            $sortDir = strtolower($sortDir);
            // Ensure the direction is either 'asc' or 'desc'
            if (!in_array($sortDir, ['asc', 'desc'])) {
                $sortDir = 'asc';
            }
            $query->orderBy($sortBy, $sortDir);
        }

        // 7) Pagination
        $perPage = (int) $request->get('per_page', 15);
        if ($perPage <= 0) {
            $perPage = 15; // Or clamp to a maximum if you prefer
        }
        $results = $query->paginate($perPage);

        // Prepare the data for the response
        $responseData = [
            'items' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
                'last_page'    => $results->lastPage(),
            ],
        ];

        // 8) Return success with the data
        return $this->success($responseData, "Data retrieved successfully.");
    }
}
