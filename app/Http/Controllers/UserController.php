<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index');
    }

    public function list(Request $request)
    {
        // Get pagination parameters from DevExtreme
        $skip = $request->get('skip', 0);
        $take = $request->get('take', 10);
        $searchText = $request->get('searchValue', '');
        $filter = $request->get('filter', []);
        $sort = $request->get('sort', []);

        // Parse filter and sort if they are JSON strings
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }
        if (is_string($sort)) {
            $sort = json_decode($sort, true);
        }

        // Start building the query
        $query = User::query();

        // Apply global search if provided
        if (!empty($searchText)) {
            $query->where(function($q) use ($searchText) {
                $q->where('name', 'like', '%' . $searchText . '%')
                  ->orWhere('id', 'like', '%' . $searchText . '%');

                // For encrypted emails, we need to get all users and filter in PHP
                // This is not ideal for performance but necessary for encrypted fields
                $allUsers = User::all();
                $matchingIds = [];

                foreach ($allUsers as $user) {
                    $decryptedEmail = $user->email; // This will auto-decrypt via accessor
                    if (stripos($decryptedEmail, $searchText) !== false) {
                        $matchingIds[] = $user->id;
                    }
                }

                if (!empty($matchingIds)) {
                    $q->orWhereIn('id', $matchingIds);
                }
            });
        }

        // Apply column filters if provided
        if (!empty($filter) && is_array($filter)) {
            $query = $this->applyFilters($query, $filter);
        }

        // Apply sorting if provided
        if (!empty($sort) && is_array($sort)) {
            foreach ($sort as $sortItem) {
                if (isset($sortItem['selector'])) {
                    $field = $sortItem['selector'];
                    $direction = isset($sortItem['desc']) && $sortItem['desc'] ? 'desc' : 'asc';
                    $query->orderBy($field, $direction);
                }
            }
        } else {
            // Default sorting by ID
            $query->orderBy('id', 'desc');
        }

        // Get total count before applying pagination
        $totalCount = $query->count();

        // Apply pagination
        $users = $query->skip($skip)->take($take)->get();

        return response()->json([
            'data' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                ];
            }),
            'totalCount' => $totalCount
        ]);
    }

    /**
     * Apply DevExtreme filters to the query
     */
    private function applyFilters($query, $filters)
    {
        if (!is_array($filters)) {
            return $query;
        }

        // Handle simple filter array [field, operator, value]
        if (count($filters) === 3 && !is_array($filters[0])) {
            return $this->applySingleFilter($query, $filters);
        }

        // Handle complex filters (AND/OR operations)
        foreach ($filters as $filterItem) {
            if (is_array($filterItem)) {
                if (count($filterItem) === 3 && !is_array($filterItem[0])) {
                    // Simple filter [field, operator, value]
                    $query = $this->applySingleFilter($query, $filterItem);
                } else {
                    // Complex nested filter
                    $query = $this->applyFilters($query, $filterItem);
                }
            }
        }

        return $query;
    }

    /**
     * Apply a single filter condition
     */
    private function applySingleFilter($query, $filter)
    {
        if (!isset($filter[0], $filter[1], $filter[2])) {
            return $query;
        }

        $field = $filter[0];
        $operator = $filter[1];
        $value = $filter[2];

        // Handle encrypted email field specially
        if ($field === 'email') {
            return $this->applyEmailFilter($query, $operator, $value);
        }

        // Handle date fields
        if (in_array($field, ['created_at', 'updated_at', 'email_verified_at'])) {
            return $this->applyDateFilter($query, $field, $operator, $value);
        }

        switch ($operator) {
            case 'contains':
                $query->where($field, 'like', '%' . $value . '%');
                break;
            case '=':
                $query->where($field, $value);
                break;
            case '<>':
                $query->where($field, '!=', $value);
                break;
            case '>':
                $query->where($field, '>', $value);
                break;
            case '<':
                $query->where($field, '<', $value);
                break;
            case '>=':
                $query->where($field, '>=', $value);
                break;
            case '<=':
                $query->where($field, '<=', $value);
                break;
            case 'between':
                if (is_array($value) && count($value) >= 2) {
                    $query->whereBetween($field, [$value[0], $value[1]]);
                }
                break;
        }

        return $query;
    }

    /**
     * Apply filter to date fields
     */
    private function applyDateFilter($query, $field, $operator, $value)
    {
        // Convert the value to a proper date format if needed
        if (is_string($value)) {
            try {
                $value = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // If parsing fails, use the original value
            }
        }

        switch ($operator) {
            case '=':
                // For exact date matching, we might want to match the whole day
                if (is_string($value) && strlen($value) <= 10) {
                    $query->whereDate($field, $value);
                } else {
                    $query->where($field, $value);
                }
                break;
            case '<>':
                $query->where($field, '!=', $value);
                break;
            case '>':
                $query->where($field, '>', $value);
                break;
            case '<':
                $query->where($field, '<', $value);
                break;
            case '>=':
                $query->where($field, '>=', $value);
                break;
            case '<=':
                $query->where($field, '<=', $value);
                break;
            case 'between':
                if (is_array($value) && count($value) >= 2) {
                    $query->whereBetween($field, [$value[0], $value[1]]);
                }
                break;
        }

        return $query;
    }

    /**
     * Apply filter to encrypted email field
     */
    private function applyEmailFilter($query, $operator, $value)
    {
        // For encrypted emails, we need to get all users and filter in PHP
        // This is not ideal for performance but necessary for encrypted fields
        $allUsers = User::all();
        $matchingIds = [];

        foreach ($allUsers as $user) {
            $decryptedEmail = $user->email; // This will auto-decrypt via accessor

            $matches = false;
            switch ($operator) {
                case 'contains':
                    $matches = stripos($decryptedEmail, $value) !== false;
                    break;
                case '=':
                    $matches = strcasecmp($decryptedEmail, $value) === 0;
                    break;
                case '<>':
                    $matches = strcasecmp($decryptedEmail, $value) !== 0;
                    break;
            }

            if ($matches) {
                $matchingIds[] = $user->id;
            }
        }

        if (!empty($matchingIds)) {
            $query->whereIn('id', $matchingIds);
        } else {
            // No matches found, return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
