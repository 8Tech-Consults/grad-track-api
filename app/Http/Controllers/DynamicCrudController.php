<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Traits\ApiResponser;

class DynamicCrudController extends Controller
{
    use ApiResponser;

    /**
     * Dynamically create or update a record in the specified model.
     *
     * @OA\Post(
     *     path="/api/dynamic-save",
     *     tags={"Dynamic CRUD"},
     *     summary="Create or update a record dynamically",
     *     description="If 'id' is provided, updates the existing record; otherwise creates a new one. Other fields map to model columns.",
     *     @OA\Parameter(
     *         name="model",
     *         in="query",
     *         required=true,
     *         description="Eloquent model name to operate on, e.g. 'User'",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Primary key of record to update (omit to create new)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON body with key-value pairs for model fields",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(type="object", @OA\AdditionalProperties(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Record created or updated successfully",
     *         @OA\JsonContent(type="object", @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string"))
     *     ),
     *     @OA\Response(response=400, description="Bad request: missing parameters or save error"),
     *     @OA\Response(response=401, description="Unauthorized: authentication required")
     * )
     */
    public function save(Request $request)
    {
        // 1) Check authentication
        $u = auth('api')->user();
        if (!$u) {
            return $this->error("User not found or not authenticated.");
        }

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

        // 3) Create an instance to retrieve table info
        $modelInstance = new $modelClass;
        $table = $modelInstance->getTable();

        // Ensure the table actually exists in DB
        if (!Schema::hasTable($table)) {
            return $this->error("Table [{$table}] for model [{$modelName}] does not exist.");
        }

        // We get an array of valid columns
        $validColumns = Schema::getColumnListing($table);

        // 4) Determine if we're creating or updating
        $recordId = $request->get('id');
        if ($recordId) {
            // Update mode: find existing record
            $record = $modelClass::find($recordId);
            if (!$record) {
                return $this->error("Record with ID [{$recordId}] not found in [{$modelName}].");
            }
        } else {
            // Create mode: a new instance
            $record = new $modelClass;
        }

        // 5) is_not_for_company & is_not_for_user logic
        //    if not present => set enterprise_id, user_id/administrator_id
        $isNotForCompany = $request->query('is_not_for_company');
        if ($isNotForCompany !== 'yes' && in_array('enterprise_id', $validColumns)) {
            // We forcibly set enterprise_id = current user's enterprise_id
            $record->enterprise_id = $u->enterprise_id;
        }

        $isNotForUser = $request->query('is_not_for_user');
        if ($isNotForUser !== 'yes') {
            // If table has 'administrator_id', set that. Else if 'user_id', set that.
            if (in_array('administrator_id', $validColumns)) {
                $record->administrator_id = $u->id;
            } elseif (in_array('user_id', $validColumns)) {
                $record->user_id = $u->id;
            }
        }

        // 6) Map request data to model fields
        // We'll only assign fields that appear in $validColumns to avoid mass assignment issues.
        // This approach is "white-listing" by virtue of validating column existence.
        // For better control, you might still use fillable or guarded in your model, but
        // here's a purely dynamic approach:
        foreach ($request->all() as $param => $value) {
            if ($param === 'model' || $param === 'id') {
                continue; // skip system params
            }
            if ($param === 'is_not_for_company' || $param === 'is_not_for_user') {
                continue; // skip special flags
            }

            // If it is a valid column, assign it
            if (in_array($param, $validColumns)) {
                if ($value == null) {
                    continue; // skip null values
                }
                $record->{$param} = $value;
            }
        }

        // 7) Save the record
        try {
            $record->save();
        } catch (\Exception $e) {
            return $this->error("Failed to save record: " . $e->getMessage());
        }

        // 8) Reload the record (optional), if you want the freshest data after triggers, etc.
        //    or if you used fillable for mass assignment, you might not need to.
        if ($record->id) {
            $record = $modelClass::find($record->id);
        }

        // 9) Return success
        $action = $recordId ? "updated" : "created";
        return $this->success($record, "{$modelName} record {$action} successfully.");
    }


  /**
     * Dynamically list, filter, sort, and paginate records of any model.
     *
     * @OA\Get(
     *     path="/api/dynamic-list",
     *     tags={"Dynamic CRUD"},
     *     summary="List or search records dynamically",
     *     description="Retrieve a paginated list of records for the given model, with optional filters, sorting, and pagination.",
     *     @OA\Parameter(
     *         name="model",
     *         in="query",
     *         required=true,
     *         description="Eloquent model name (without namespace) to query, e.g. 'User'",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="<field>_like",
     *         in="query",
     *         description="Filter where <field> LIKE %value%",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="<field>_gt",
     *         in="query",
     *         description="Filter where <field> > value",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Column name to sort by",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_dir",
     *         in="query",
     *         description="Sort direction: 'asc' or 'desc'",
     *         @OA\Schema(type="string", enum={"asc","desc"}, default="asc")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="pagination", type="object",
     *                  @OA\Property(property="current_page", type="integer"),
     *                  @OA\Property(property="per_page", type="integer"),
     *                  @OA\Property(property="total", type="integer"),
     *                  @OA\Property(property="last_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request: missing or invalid parameters"),
     *     @OA\Response(response=401, description="Unauthorized: authentication required")
     * )
     */
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
    /**
     * Delete a record dynamically from the specified model.
     *
     * @OA\Post(
     *     path="/api/dynamic-delete",
     *     tags={"Dynamic CRUD"},
     *     summary="Delete a record dynamically",
     *     description="Deletes the record with the given 'id' from the specified model.",
     *     @OA\Parameter(
     *         name="model",
     *         in="query",
     *         required=true,
     *         description="Eloquent model name to delete from, e.g. 'User'",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         required=true,
     *         description="Primary key of the record to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Record deleted successfully",
     *         @OA\JsonContent(type="object", @OA\Property(property="message", type="string"))
     *     ),
     *     @OA\Response(response=400, description="Bad request: missing 'id' parameter"),
     *     @OA\Response(response=401, description="Unauthorized: authentication required"),
     *     @OA\Response(response=404, description="Not found: record does not exist")
     * )
     */
    public function delete(Request $request)
    {
        // 1) Check authentication
        $u = auth('api')->user();
        if (!$u) {
            return $this->error("User not found or not authenticated.");
        }

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

        // 3) Create an instance to retrieve table info
        $modelInstance = new $modelClass;
        $table = $modelInstance->getTable();

        // Ensure the table actually exists in DB
        if (!Schema::hasTable($table)) {
            return $this->error("Table [{$table}] for model [{$modelName}] does not exist.");
        }

        // 4) Get the record ID to delete
        $recordId = $request->get('id');
        if (!$recordId) {
            return $this->error("Missing 'id' parameter for deletion.");
        }

        // Find the record to delete
        $record = $modelClass::find($recordId);
        if (!$record) {
            return $this->error("Record with ID [{$recordId}] not found in [{$modelName}].");
        }

        // 5) Delete the record
        try {
            $record->delete();
        } catch (\Exception $e) {
            return $this->error("Failed to delete record: " . $e->getMessage());
        }

        // 6) Return success message
        return $this->success(null, "{$modelName} record with ID [{$recordId}] deleted successfully.");
    }
}
