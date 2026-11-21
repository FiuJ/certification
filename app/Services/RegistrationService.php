<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegistrationService
{
    public function registerRenter(array $data): User
    {
        $user = new User();

        $user->nik = $data['nik'];
        $user->name = $data['name'];
        $user->phone = $data['phone'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);

        $user->role = 'renter';
        $user->registration_status = 'Pending';

        $user->save();

        return $user;
    }
}
