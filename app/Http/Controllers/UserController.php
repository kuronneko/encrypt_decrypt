<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\DevExtremeOperations;

class UserController extends Controller
{
    use DevExtremeOperations;

    public function index()
    {
        return view('users.index');
    }

    public function list(Request $request)
    {
        // Start building the query
        $query = User::query();

        // Create a User instance to handle encrypted fields
        $userModel = new User();

        // Define configuration for DevExtreme operations
        $options = [
            'searchableFields' => ['id', 'name', 'email'],
            'encryptedFieldsHandler' => $userModel, // Handler for encrypted fields
            'defaultSort' => ['id' => 'desc'], // Default sorting
            'dataTransformer' => function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email, // Will be auto-decrypted by the model's accessor
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                ];
            }
        ];

        // Use the trait to handle the DevExtreme request
        return $this->handleDevExtremeRequest($request, $query, $options);
    }
}
