<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdminLoginGuard
{
    public function assertAllowed(?User $user, string $field = 'otpPhone'): void
    {
        if ($user === null || ! $user->is_admin || ! $user->status) {
            throw ValidationException::withMessages([
                $field => 'امکان ورود مدیریت با این شماره وجود ندارد.',
            ]);
        }
    }
}
