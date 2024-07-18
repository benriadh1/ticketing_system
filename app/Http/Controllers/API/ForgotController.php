<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ResetRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ForgotController extends Controller
{
    public function forgot(Request $request)
    {
        $email = $request->input('email');

        if (User::where('email', $email)->doesntExist()) {
            return response([
                'message' => 'L\'utilisateur n\'esiste pas !'
            ], 404);
        }

        $token = Str::random(10);

        try {
            DB::table('password_resets')->insert([
                'email' => $email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]);

            //send email
            Mail::send('emails.forgot', ['token' => $token], function(Message $message) use ($email){
                $message->to($email);
                $message->subject('
Réinitialisez votre mot de passe');
            });

            return response([
                'message' => '
Vérifiez votre e-mail !'
            ]);
        } catch (\Exception $e) {
            return response([
                'message' => 'quelque chose s\'est mal passé'
            ], 400);
        }
    }

    public function reset(Request $request){
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response([
                'message' => 'quelque chose s\'est mal passé'
            ], 422);
        }

        $token = $request->input('token');

        if (!$passwordResets = DB::Table('password_resets')->where('token', $token)->first()) {
            return response([
                'message' => '
Jeton invalide!'
            ], 400);
        }

        if (!$passwordResets->created_at || Carbon::parse($passwordResets->created_at)->addHour()->isPast()) {
            return response([
                'message' => '
Jeton invalide!'
            ], 400);
        }

        if (!$user = User::where('email', $passwordResets->email)->first()) {
            return response([
                'message' => 'L\'utilisateur n\'esiste pas !'
            ], 404);
        }

        $user->password = Hash::make($request->input('password'));

        $user->save();

        DB::table('password_resets')->where('token', $token)->delete();

        return response([
            'message' => 'Succès'
        ]);
    }
}
