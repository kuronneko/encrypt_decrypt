<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUserRequest;
use App\Components\DevExtremeHandler;
use App\Components\DevExtremeConfig;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index');
    }

    /**
     * Handle DevExtreme list request with encrypted fields support
     *
     * This method demonstrates the new streamlined approach using
     * DevExtremeHandler and DevExtremeConfig components.
     */
    public function list(Request $request)
    {
        $query = User::with('locations');

        $config = DevExtremeConfig::make()
            ->encryptedFieldsHandler(new User())
            ->relatedModel('locations', new Location())
            ->searchableFields(['id', 'name', 'email', 'locations.city', 'locations.postal_code']) // Limited search fields
            ->sortBy('id', 'desc') // Different default sort
            ->transform(function ($user) {
                $location = $user->locations->first();
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email, // Auto-decrypted by User model accessor
                    'city' => $location?->city,
                    'postal_code' => $location?->postal_code, // Auto-decrypted by Location model
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                ];
            })
            ->build();

        return DevExtremeHandler::create($query, $config)->handle($request);
    }

    /**
     * Store a newly created user with location
     */
    public function store(StoreUserRequest $request)
    {
        try {
            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email, // Will be auto-encrypted by the model
                'password' => Hash::make($request->password),
            ]);

            // Create location if any location data is provided
            $locationData = collect([
                'location_name' => $request->location_name,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'postal_code' => $request->postal_code,
                'notes' => $request->notes,
            ])->filter()->toArray();

            if (!empty($locationData)) {
                Location::create([
                    'user_id' => $user->id,
                    'name' => $request->location_name ?: 'Main Location',
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'postal_code' => $request->postal_code,
                    'notes' => $request->notes,
                    'is_active' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User created successfully!',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email, // Auto-decrypted
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }
}
