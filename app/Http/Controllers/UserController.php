<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Location;
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
        // Start building the query with location relationship
        $query = User::with('locations');

        // Create model instances to handle encrypted fields
        $userModel = new User();
        $locationModel = new Location();

        // Define configuration for DevExtreme operations
        $options = [
            'searchableFields' => ['id', 'name', 'email', 'city', 'locations.postal_code', 'locations.address'],
            'encryptedFieldsHandler' => $userModel, // Handler for encrypted fields
            'relatedModels' => [
                'locations' => $locationModel // Handler for location encrypted fields
            ],
            'defaultSort' => ['id' => 'desc'], // Default sorting
            'dataTransformer' => function($user) {

                $postalCode = $user->locations->first() ? $user->locations->first()->postal_code : null;
                $city = $user->locations->first() ? $user->locations->first()->city : null;
                $address = $user->locations->first() ? $user->locations->first()->address : null;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email, // Will be auto-decrypted by the model's accessor
                    'city' => $city, // Will be auto-decrypted by the Location model's accessor
                    'postal_code' => $postalCode, // Will be auto-decrypted by the Location model's accessor
                    'address' => $address, // Will be auto-decrypted by the Location model's accessor
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
