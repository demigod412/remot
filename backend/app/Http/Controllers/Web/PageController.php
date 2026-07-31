<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\SitePage;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    /** Dynamic page renderer (about, terms, privacy, etc.) */
    public function show(string $slug)
    {
        $page = SitePage::where('slug', $slug)
            ->where('tempname', '!=', 'blog')
            ->firstOrFail();

        return view('web.pages.show', compact('page'));
    }

    /** Contact page */
    public function contact()
    {
        return view('web.pages.contact');
    }

    /** Contact form submission */
    public function submitContact(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        if (! verifyRecaptcha($request->input('g-recaptcha-response'))) {
            return back()->withInput()
                ->withErrors(['captcha' => 'Please complete the reCAPTCHA verification and try again.']);
        }

        $gs = gs();

        if ($gs->email_notify && $gs->email_from) {
            try {
                Mail::raw(
                    "From: {$data['name']} <{$data['email']}>\n\n{$data['message']}",
                    function ($m) use ($data, $gs) {
                        $m->to($gs->email_from)
                          ->subject('[Contact] ' . $data['subject']);
                    }
                );
            } catch (\Throwable) {
                // Mail not configured — silently continue
            }
        }

        return back()->with('success', __('Your message has been sent. We\'ll get back to you shortly.'));
    }

    /** Newsletter subscription */
    public function subscribe(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Subscriber::firstOrCreate(['email' => $request->email]);

        return back()->with('success', __('You have been subscribed to our newsletter.'));
    }

    /** Language switch */
    public function changeLanguage(string $code)
    {
        $lang = Language::where('code', $code)->firstOrFail();
        session(['locale' => $lang->code]);
        App::setLocale($lang->code);

        if (auth()->check()) {
            auth()->user()->update(['locale' => $lang->code]);
        }

        return redirect()->back();
    }

    /** Cookie consent */
    public function acceptCookie()
    {
        session(['cookie_accepted' => true]);
        return response()->json(['status' => 'ok']);
    }

    public function rejectCookie()
    {
        session(['cookie_rejected' => true]);
        return response()->json(['status' => 'ok']);
    }
}
