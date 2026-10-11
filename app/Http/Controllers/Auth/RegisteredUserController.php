<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationOtpMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $pending = $request->session()->get('pending_registration');

        return view('auth.register', [
            'pending' => $pending,
        ]);
    }

    /**
     * Handle an incoming registration request: validate and issue OTP.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $request->has('first_name') && $request->filled('name')) {
            $parts = explode(' ', trim((string) $request->name), 2);
            $request->merge([
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
            ]);
        }

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted', 'required'],
        ], [
            'terms.accepted' => 'You must agree to the Terms and Conditions and Privacy Policy to register.',
            'terms.required' => 'You must agree to the Terms and Conditions and Privacy Policy to register.',
        ]);

        $firstName = User::titleCaseName($request->first_name);
        $middleName = User::titleCaseName($request->middle_name);
        $lastName = User::titleCaseName($request->last_name);

        $parts = array_filter(
            [$firstName, $middleName, $lastName],
            fn ($part) => ! empty(trim((string) $part))
        );
        $fullName = implode(' ', $parts);

        $otp = (string) random_int(100000, 999999);

        $pendingData = [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'name' => $fullName,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'otp_hash' => Hash::make($otp),
            'otp_plain_dev' => app()->environment('local', 'testing') ? $otp : null,
            'expires_at' => now()->addMinutes(10)->timestamp,
            'resend_available_at' => now()->addSeconds(60)->timestamp,
            'attempts' => 0,
        ];

        $mailSent = false;
        try {
            if (config('mail.default') && config('mail.default') !== 'log' && config('mail.default') !== 'array') {
                Mail::to($request->email)->send(new RegistrationOtpMail($otp, $fullName, 10));
                $mailSent = true;
            } else {
                Mail::to($request->email)->send(new RegistrationOtpMail($otp, $fullName, 10));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if (! $mailSent) {
            $pendingData['otp_plain_dev'] = $otp;
        }

        $request->session()->put('pending_registration', $pendingData);

        return redirect()->route('register.otp.show');
    }

    /**
     * Display the OTP verification screen.
     */
    public function showOtp(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('pending_registration');

        if (! $pending) {
            return redirect()->route('register');
        }

        if (now()->timestamp > $pending['expires_at']) {
            $request->session()->forget('pending_registration');
            return redirect()->route('register')->withErrors([
                'email' => 'Verification code expired. Please register again.',
            ]);
        }

        $email = $pending['email'];
        $atPos = strpos($email, '@');
        $maskedEmail = $atPos > 2
            ? substr($email, 0, 2) . str_repeat('*', max(3, $atPos - 3)) . substr($email, $atPos - 1)
            : $email;

        return view('auth.verify-otp', [
            'email' => $pending['email'],
            'maskedEmail' => $maskedEmail,
            'name' => $pending['name'],
            'expiresAt' => $pending['expires_at'],
            'resendAvailableAt' => $pending['resend_available_at'],
            'devOtp' => $pending['otp_plain_dev'] ?? null,
        ]);
    }

    /**
     * Verify the submitted OTP and complete registration.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('pending_registration');

        if (! $pending) {
            return redirect()->route('register')->withErrors([
                'email' => 'No registration in progress. Please start over.',
            ]);
        }

        if (now()->timestamp > $pending['expires_at']) {
            $request->session()->forget('pending_registration');
            return redirect()->route('register')->withErrors([
                'email' => 'Verification code expired. Please register again.',
            ]);
        }

        $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ], [
            'otp.required' => 'Please enter the 6-digit verification code.',
            'otp.size' => 'The verification code must be exactly 6 digits.',
            'otp.regex' => 'The verification code must contain only numbers.',
        ]);

        if (! Hash::check($request->otp, $pending['otp_hash'])) {
            $pending['attempts'] = ($pending['attempts'] ?? 0) + 1;
            $remaining = 5 - $pending['attempts'];
            $request->session()->put('pending_registration', $pending);

            if ($remaining <= 0) {
                $request->session()->forget('pending_registration');
                return redirect()->route('register')->withErrors([
                    'email' => 'Too many invalid attempts. Your registration session was cancelled.',
                ]);
            }

            return back()->withErrors([
                'otp' => "Incorrect verification code. {$remaining} attempt(s) remaining.",
            ]);
        }

        // OTP is correct! Create user now.
        $user = User::create([
            'first_name' => $pending['first_name'],
            'middle_name' => $pending['middle_name'],
            'last_name' => $pending['last_name'],
            'name' => $pending['name'],
            'email' => $pending['email'],
            'password' => $pending['password_hash'],
            'role' => 'player',
            'email_verified_at' => now(),
        ]);

        $request->session()->forget('pending_registration');

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('welcome')->with('status', 'Your account has been verified and created successfully!');
    }

    /**
     * Resend a fresh OTP with 60-second throttling.
     */
    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('pending_registration');

        if (! $pending) {
            return redirect()->route('register');
        }

        if (now()->timestamp < ($pending['resend_available_at'] ?? 0)) {
            $remaining = ($pending['resend_available_at'] - now()->timestamp);
            return back()->withErrors([
                'otp' => "Please wait {$remaining} seconds before requesting another code.",
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $pending['otp_hash'] = Hash::make($otp);
        $pending['otp_plain_dev'] = app()->environment('local', 'testing') ? $otp : null;
        $pending['expires_at'] = now()->addMinutes(10)->timestamp;
        $pending['resend_available_at'] = now()->addSeconds(60)->timestamp;
        $pending['attempts'] = 0;

        $mailSent = false;
        try {
            if (config('mail.default') && config('mail.default') !== 'log' && config('mail.default') !== 'array') {
                Mail::to($pending['email'])->send(new RegistrationOtpMail($otp, $pending['name'], 10));
                $mailSent = true;
            } else {
                Mail::to($pending['email'])->send(new RegistrationOtpMail($otp, $pending['name'], 10));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if (! $mailSent) {
            $pending['otp_plain_dev'] = $otp;
        }

        $request->session()->put('pending_registration', $pending);

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    /**
     * Cancel pending registration and return to registration form.
     */
    public function cancelRegistration(Request $request): RedirectResponse
    {
        $request->session()->forget('pending_registration');

        return redirect()->route('register');
    }
}
