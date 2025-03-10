<?php

namespace App\Http\Controllers\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{


    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'mobile' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find user by mobile number
        $user = User::where('mobile', $request->mobile)->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status_code' => 2,
                'message' => 'Invalid credentials'
            ]);
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status_code' => 1,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }
    public function register(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required',
            'mobile' => 'required|string|unique:users,mobile',
            'password' => 'required|string|min:6|confirmed', // Ensure password confirmation
        ]);


        // Create user
        $user = User::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user'
        ]);

        // Generate API token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status_code' => 1,
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => $user
        ]);
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        // Fetch the authenticated user
        $user = Auth::user();

        if (!$user instanceof \App\Models\User) {
            return response()->json(['status_code' => 2,'message' => 'User model not found']);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['status_code' => 2,'message' => 'Current password is incorrect']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save(); // ✅ Use save() to store changes

        return response()->json(['status_code' => 1,'message' => 'Password changed successfully']);
    }


    private function sendOtpSMS($mobileNumber, $otp)
    {
        $fields = array(
            "message" => "Your OTP for registration is: $otp",
            "language" => "english",
            "route" => "q",
            "numbers" => $mobileNumber,
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($fields),
            CURLOPT_HTTPHEADER => array(
                "authorization: 1A5KFGtiU27gVQfnch8oZsjpauSBxvY0blTCDedJXHEk9ILPOmLiUSjEIoOgtM03yG1XZQHrWpsTucCB", // Your API key
                "accept: */*",
                "cache-control: no-cache",
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        return json_decode($response, true);
    }


    public function verifyUser(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'otp' => 'required'
        ]);

        $user = User::find($request->input('id'));
        if (!$user || $user->is_verified == 1) {
            return response()->json(['status_code' => 2, 'data' => [], 'message' => 'User not found']);
        }
        if ($user->otp == $request->input('otp')) {

            $user->update([
                'is_verified' => true,
                'otp' => '',
                'status' => 'ACTIVE'
            ]);
            $token = $user->createToken('api-token')->plainTextToken;
            // $this->createSubscription($user->id);
            return response()->json(['status_code' => 1, 'data' => ['user' => $user, 'token' => $token], 'message' => 'User verified successfully']);
        }
        return response()->json(['status_code' => 2, 'data' => [], 'message' => 'Invalid Otp']);
    }





    public function forgetPassword(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required',
        ]);

        // Fetch the user by mobile_number
        $user = User::where('mobile_number', $request->mobile_number)->first();
        $otp = mt_rand(1000, 9999);

        if ($user) {
            if (!$user->is_verified) {
                return response()->json(['status_code' => 2, 'data' => [], 'message' => 'User not verified.']);
            }
            $this->sendOtpSMS($request->mobile_number, $otp);
            $user->update([
                'otp' => $otp,

            ]);
            // Send OTP to the user's email
            $data = [
                'name' => $user->name,
                'otp' => $otp
            ];
            $body = view('email.otp_verification', $data)->render();
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $subject = 'Verify your email';
            // Helper::sendEmail($user->email, $subject, $body, $headers);

            return response()->json(['status_code' => 1, 'data' => ['id' => $user->id], 'message' => 'OTP has been sent to your registered email address. You can later change your password.', 'otp' => $otp]);
        } else {
            return response()->json(['status_code' => 2, 'data' => [], 'message' => 'User not registered.']);
        }
    }

    public function forgetPasswordVerifyUser(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'otp' => 'required'
        ]);

        $user = User::find($request->input('id'));

        if (!$user) {
            return response()->json(['status_code' => 2, 'data' => [], 'message' => 'User not found']);
        }
        if ($user->otp == $request->input('otp')) {
            $uid = Str::uuid()->toString();
            $user->update([
                'otp' => '',
                'verification_uid' => $uid
            ]);

            return response()->json(['status_code' => 1, 'data' => ['id' => $user->id, 'uid' => $uid], 'message' => 'Email verified. Continue to change your password']);
        }
        return response()->json(['status_code' => 2, 'data' => [], 'message' => 'Invalid Otp']);
    }


    public function forgetPasswordChangePassword(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'password' => 'required',
            'verification_uid' => 'required|string'
        ]);

        $user = User::where('id', $request->input('id'))
            ->where('verification_uid', $request->input('verification_uid'))
            ->first();

        if (!$user) {
            return response()->json(['status_code' => 2, 'data' => [], 'message' => 'User not found']);
        }

        $user->update([
            'password' =>  bcrypt($request->input('password')),
            'verification_uid' => ''
        ]);

        return response()->json(['status_code' => 1, 'data' => [], 'message' => 'Password changed.']);
    }






}
