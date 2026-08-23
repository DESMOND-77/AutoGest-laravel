<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Students\Events\StudentEmailVerified;
use App\Domain\Students\Exceptions\InvalidOtp;
use App\Domain\Students\Http\Requests\VerifyEmailOtpRequest;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\EmailOtpService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EmailOtpController extends Controller
{
    public function __construct(
        private readonly EmailOtpService $otps,
    ) {}

    public function show(): View|RedirectResponse
    {
        if (Auth::user()->email_verified_at !== null) {
            return redirect()->route('eleve.dashboard');
        }

        return view('eleve.verification-otp');
    }

    public function verify(VerifyEmailOtpRequest $request): RedirectResponse
    {
        $user = Auth::user();

        try {
            $this->otps->verify($user, $request->validated('code'));
        } catch (InvalidOtp $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        $student = Student::query()->where('user_id', $user->id)->first();

        if ($student) {
            StudentEmailVerified::dispatch($student);
        } else {
            Log::warning('eleve.otp.verified_without_student', ['user_id' => $user->id]);
        }

        return redirect()->route('eleve.dashboard')->with('status', 'Adresse e-mail vérifiée.');
    }

    public function resend(): RedirectResponse
    {
        $this->otps->generate(Auth::user());

        return back()->with('status', 'Un nouveau code a été envoyé.');
    }
}
