<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobApplicationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobApplicationMessageController extends Controller
{
    private function user()
    {
        return Auth::guard('web')->user();
    }

    public function thread(int $appId)
    {
        $user = $this->user();
        $app  = JobApplication::with(['listing.employer', 'applicant'])->findOrFail($appId);

        $isEmployer  = $app->listing->employer_id === $user->id;
        $isApplicant = $app->applicant_id === $user->id;

        abort_unless($isEmployer || $isApplicant, 403);

        // Mark unread messages sent by the other party as read
        $app->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = $app->messages()->with('sender')->orderBy('created_at')->get();

        $other = $isEmployer ? $app->applicant : $app->listing->employer;

        return view('user.jobs.applications.thread', compact('app', 'messages', 'isEmployer', 'other'));
    }

    public function send(Request $request, int $appId)
    {
        $user = $this->user();
        $app  = JobApplication::with(['listing', 'applicant'])->findOrFail($appId);

        $isEmployer  = $app->listing->employer_id === $user->id;
        $isApplicant = $app->applicant_id === $user->id;

        abort_unless($isEmployer || $isApplicant, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        JobApplicationMessage::create([
            'job_application_id' => $app->id,
            'sender_id'          => $user->id,
            'body'               => $data['body'],
        ]);

        $recipientId = $isEmployer ? $app->applicant_id : $app->listing->employer_id;

        \App\Models\UserNotification::notify(
            $recipientId,
            'job_message',
            'New message from ' . $user->fullname,
            \Illuminate\Support\Str::limit($data['body'], 100),
            route('user.jobs.applications.thread', $app->id),
            'message-circle'
        );

        return redirect()->route('user.jobs.applications.thread', $appId)
            ->with('success', 'Message sent.');
    }
}
