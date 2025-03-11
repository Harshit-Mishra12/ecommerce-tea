<?php


namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'street'      => 'required|string|max:255',
            'city'        => 'required|string|max:100',
            'state'       => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country'     => 'required|string|max:100',
        ]);

        DB::transaction(function () use ($userId, $validated, &$address) {
            // Create new address
            $address = Address::create(array_merge($validated, ['user_id' => $userId]));

            // Check if there is any selected address
            $hasSelected = Address::where('user_id', $userId)->where('is_selected', true)->exists();

            // If no address is selected, set this one as selected
            if (!$hasSelected) {
                $address->update(['is_selected' => true]);
            }
        });

        return response()->json([ 'status_code' => 1,'message' => 'Address added successfully', 'data' => $address]);
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

        return response()->json([ 'status_code' => 1,'message' => 'Address selected successfully', 'data' => $address]);
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

        return response()->json([ 'status_code' => 1,'message' => 'Address deleted successfully']);
    }
}
