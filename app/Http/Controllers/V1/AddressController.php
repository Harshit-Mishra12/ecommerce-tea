<?php


namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user.
     */
    public function index()
    {
        $userId = Auth::id();
        $addresses = Address::where('user_id', $userId)->get();
        return response()->json([
            'status_code' => 1,
            'message' => 'fetch  successfully',
            'data' => $addresses,

        ]);
    }

    /**
     * Store a new address for the authenticated user.
     */
    public function store(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'mobile'       => 'required|string|max:15',
            'street'       => 'required|string|max:255',
            'city'         => 'required|string|max:100',
            'state'        => 'required|string|max:100',
            'postal_code'  => 'required|string|max:20',
            'country'      => 'required|string|max:100',
            'is_selected'  => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($userId, $validated, &$address) {
            // If `is_selected` is true, unselect other addresses first
            if (!empty($validated['is_selected']) && $validated['is_selected']) {
                Address::where('user_id', $userId)->update(['is_selected' => false]);
            }

            // Create new address
            $address = Address::create(array_merge($validated, ['user_id' => $userId]));

            // If `is_selected` is true or no address is selected, set this one as selected
            $hasSelected = Address::where('user_id', $userId)->where('is_selected', true)->exists();

            if (!empty($validated['is_selected']) || !$hasSelected) {
                $address->update(['is_selected' => true]);
            }
        });
        $addresses = Address::where('user_id', $userId)->get();
        return response()->json([
            'status_code' => 1,
            'message' => 'fetch  successfully',
            'data' => $addresses,

        ]);
    }



    /**
     * Update the selected address for the authenticated user.
     */
    public function selectAddress($id)
    {
        $userId = Auth::id();
        $address = Address::where('user_id', $userId)->findOrFail($id);
        DB::transaction(function () use ($userId, $address) {
            // Unselect all addresses
            Address::where('user_id', $userId)->update(['is_selected' => false]);
            // Select the new address
            $address->update(['is_selected' => true]);
        });

        $addresses = Address::where('user_id', $userId)->get();
        return response()->json([
            'status_code' => 1,
            'message' => 'fetch  successfully',
            'data' => $addresses,

        ]);
    }

    /**
     * Delete an address.
     */
    public function destroy($id)
    {
        $userId = Auth::id();
        $address = Address::where('user_id', $userId)->findOrFail($id);

        DB::transaction(function () use ($userId, $address) {
            $address->delete();
            // If deleted address was selected, choose another as default
            if ($address->is_selected) {
                $newDefault = Address::where('user_id', $userId)->first();
                if ($newDefault) {
                    $newDefault->update(['is_selected' => true]);
                }
            }
        });

        $addresses = Address::where('user_id', $userId)->get();
        return response()->json([
            'status_code' => 1,
            'message' => 'fetch  successfully',
            'data' => $addresses,

        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'dob'           => 'nullable|date',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Update User Data Directly with Fill & Save
        $user->fill([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ])->save();

        // Update or Create User Details
        $userDetails = UserDetail::updateOrCreate(
            ['user_id' => $user->id],  // Condition for finding record
            [
                'dob' => $validated['dob'],
            ]
        );

        // Handle Profile Image Upload Using Helper
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $dir = '/uploads/profile/';
            $mediaUrl = Helper::saveImageToServer($file, $dir);

            $userDetails->update(['profile_image' => $mediaUrl]);
        }

        // Merge data into a unified response format
        $userData = $user->load('details')->toArray();
        $userData['profile_image'] =  $userDetails->profile_image ?? null;;
        $userData['dob'] = $user->details->dob ?? null;

        return response()->json([
            'status_code' => 1,
            'message'     => 'Profile updated successfully',
            'data'        => $userData
        ]);
    }

    public function getProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status_code' => 2,
                'message' => 'User not found'
            ]);
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'dob' => optional($user->details)->dob,
            'profile_image' => optional($user->details)->profile_image,
        ];

        return response()->json([
            'status_code' => 1,
            'message' => 'Profile data fetched successfully',
            'data' => $userData
        ]);
    }
}
