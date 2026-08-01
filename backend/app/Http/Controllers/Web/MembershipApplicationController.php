<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\MembershipApplicationRequest;
use App\Models\MembershipApplication;
use Illuminate\Http\Request;

class MembershipApplicationController extends Controller
{
    public function create()
    {
        return view('web.membership.apply');
    }

    public function store(MembershipApplicationRequest $request)
    {
        if (! verifyRecaptcha($request->input('g-recaptcha-response'))) {
            return back()->withInput()
                ->withErrors(['captcha' => 'Please complete the reCAPTCHA verification and try again.']);
        }

        $isBusiness = (int) $request->input('applicant_type') === MembershipApplication::TYPE_BUSINESS;
        $path       = config('jobstation.upload_paths.membership_docs', 'uploads/membership/documents');

        // Applicant documents are private. They contain CVs and registration papers,
        // so they go to the local disk and are served through SecureFileController,
        // never from a public URL.
        $data = [
            'full_name'         => $request->input('full_name'),
            'email'             => strtolower(trim($request->input('email'))),
            'phone'             => $request->input('phone'),
            'country'           => $request->input('country'),
            'applicant_type'    => $isBusiness ? MembershipApplication::TYPE_BUSINESS : MembershipApplication::TYPE_INDIVIDUAL,
            'resume_path'       => uploadPrivateFile($request->file('resume'), $path),
            'cover_letter_path' => $request->hasFile('cover_letter')
                ? uploadPrivateFile($request->file('cover_letter'), $path)
                : null,
            'status'            => MembershipApplication::STATUS_PENDING,
            'reference_code'    => MembershipApplication::generateReferenceCode(),
            'ip_address'        => $request->ip(),
            'submitted_at'      => now(),
        ];

        if ($isBusiness) {
            $data += [
                'business_name'             => $request->input('business_name'),
                'business_email'            => $request->input('business_email'),
                'business_country'          => $request->input('business_country'),
                'business_registration_doc' => uploadPrivateFile($request->file('business_registration_doc'), $path),
            ];
        }

        $application = MembershipApplication::create($data);

        return redirect()->route('membership.status')
            ->with('success', 'Application received. Your reference is ' . $application->reference_code
                . '. Keep it safe, you will need it to check your status.');
    }

    /**
     * Reference code AND matching email are both required. The code alone would
     * let anyone enumerate applications.
     */
    public function status(Request $request)
    {
        $application = null;
        $searched    = false;

        if ($request->filled('reference_code') && $request->filled('email')) {
            $request->validate([
                'reference_code' => ['required', 'string', 'max:40'],
                'email'          => ['required', 'email', 'max:120'],
            ]);

            $searched = true;

            $application = MembershipApplication::where('reference_code', trim($request->input('reference_code')))
                ->where('email', strtolower(trim($request->input('email'))))
                ->first();
        }

        return view('web.membership.status', compact('application', 'searched'));
    }
}
