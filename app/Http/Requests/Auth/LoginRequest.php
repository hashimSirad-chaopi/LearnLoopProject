<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Admin-panel login.
 *
 * Order of checks matters: credentials are verified FIRST, and only then do we
 * look at role and status. That way nobody is ever signed in "just to be kicked
 * out again", and a wrong password never reveals whether an account is an
 * admin or is suspended.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Verify the credentials, confirm the account may use the admin panel,
     * and only then sign the user in.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $guard = Auth::guard('web');
        $provider = $guard->getProvider();

        $user = $provider->retrieveByCredentials(['email' => $this->input('email')]);

        // 1. Wrong email or wrong password. One shared message, on purpose.
        if (! $user || ! $provider->validateCredentials($user, ['password' => $this->input('password')])) {
            $this->failLogin('These credentials do not match our records.');
        }

        // 2. Correct password, but not an admin.
        if ($user->role !== 'admin') {
            $this->failLogin('This account does not have admin access.');
        }

        // 3. Correct password, admin, but suspended.
        if ($user->status !== 'active') {
            $this->failLogin('This account is suspended. Ask another administrator to reactivate it.');
        }

        $guard->login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Count the failed attempt against the rate limit and reject the request.
     *
     * @throws ValidationException
     */
    protected function failLogin(string $message): never
    {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages(['email' => $message]);
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'Too many login attempts. Please try again in '
                .$seconds.' '.Str::plural('second', $seconds).'.',
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
