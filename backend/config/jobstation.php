<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Job Station Application Config
    |--------------------------------------------------------------------------
    */

    'name'        => env('APP_NAME', 'Job Station'),
    'version'     => '1.0.0',
    'coin_name'   => env('JOBSTATION_COIN_NAME', 'Job Coins'),
    'coin_symbol' => env('JOBSTATION_COIN_SYMBOL', 'JC'),
    'per_page'    => 20,

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    | This install is an invite-only, admin-curated microtask marketplace.
    | Third-party gig posting and the job board are switched OFF here rather
    | than deleted, so the original behaviour can be restored by flipping an
    | env value. Every flag MUST be enforced server-side in the controller, not
    | just used to hide a nav link.
    |
    | enable_user_gigs  Users posting their own gigs/works
    | enable_job_board  The whole Job Listing / job application system
    | enable_api        The /api/v1 mobile API (registration + gig posting live
    |                   there too, so leaving it on would bypass every rule)
    | invite_only       Self-registration closed, membership applications only
    */
    'features' => [
        'enable_user_gigs' => env('JOBSTATION_ENABLE_USER_GIGS', false),
        'enable_job_board' => env('JOBSTATION_ENABLE_JOB_BOARD', false),
        'enable_api'       => env('JOBSTATION_ENABLE_API', false),
        'invite_only'      => env('JOBSTATION_INVITE_ONLY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Review
    |--------------------------------------------------------------------------
    | Hours admin has to review submitted work before it auto-approves and the
    | worker is paid. Used as the fallback when works.auto_approve_hours is null.
    */
    'task_review_hours'   => env('JOBSTATION_TASK_REVIEW_HOURS', 48),
    'max_revisions'       => env('JOBSTATION_MAX_REVISIONS', 3),

    /*
    |--------------------------------------------------------------------------
    | Worker Accountability
    |--------------------------------------------------------------------------
    | Strikes are counted from work_submissions over a rolling window. Abandoning
    | a task (deadline passed with nothing submitted) is weighted heavier than
    | having work rejected, because an abandoned task holds a slot for the whole
    | window and gives admin nothing to review.
    |
    | Set max_strikes to 0 to disable the block entirely. It ships permissive:
    | 6 strikes = two abandonments, or six rejections, in 60 days.
    */
    'accountability' => [
        'window_days'   => env('JOBSTATION_STRIKE_WINDOW_DAYS', 30),
        'max_strikes'   => env('JOBSTATION_MAX_STRIKES', 3),
        'abandon_weight' => env('JOBSTATION_ABANDON_WEIGHT', 3),
        'reject_weight'  => env('JOBSTATION_REJECT_WEIGHT', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Membership Applications
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Task Result Schema
    |--------------------------------------------------------------------------
    | work_categories.result_schema is intentionally left NULL. With no schema,
    | TaskResultRequest still requires a genuinely parseable JSON file (a renamed
    | .txt is rejected) but does not constrain its shape. ResultSchemaValidator is
    | fully built and tested; add a schema to a category whenever a format is
    | agreed and validation starts applying to that category only.
    */

    'membership' => [
        'allowed_doc_types' => ['pdf', 'doc', 'docx'],
        'max_doc_size_kb'   => 5120,
    ],

    /*
    |--------------------------------------------------------------------------
    | CodeCanyon / Envato Licensing
    |--------------------------------------------------------------------------
    | Set during installation. For live purchase-code verification, add an
    | Envato personal token (with the "verify purchases" permission) and this
    | product's CodeCanyon item id.
    */
    'purchase_code' => env('PURCHASE_CODE'),
    'envato' => [
        'token'       => env('ENVATO_API_TOKEN'),
        'item_id'     => env('ENVATO_ITEM_ID'),
        // Fail closed when Envato cannot confirm a code (bad token / outage).
        // Default: degrade gracefully to offline acceptance so a valid buyer is
        // never blocked by a seller-side or network problem.
        'strict'      => env('ENVATO_STRICT', false),
        // Hours to cache a successful live verification (Envato rate limits).
        'cache_hours' => env('ENVATO_CACHE_HOURS', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Paths
    |--------------------------------------------------------------------------
    */
    'upload_paths' => [
        'user_avatar'     => 'uploads/users/avatars',
        'avatars'         => 'uploads/users/avatars',
        'work_cover'      => 'uploads/works/covers',
        'proof'           => 'uploads/submissions/proofs',
        'proof_files'     => 'uploads/submissions/proofs',
        'kyc_documents'   => 'uploads/users/kyc',
        'kyc'             => 'uploads/users/kyc',
        'help_files'      => 'uploads/helpdesk',
        'admin_avatar'    => 'uploads/admins/avatars',
        'logos'           => 'uploads/logos',
        'content'         => 'uploads/content',
        'resumes'         => 'uploads/resumes',
        'contract_proof'  => 'uploads/contracts/proofs',
        'topup_proof'     => 'uploads/topups/proofs',
        'payment_channel' => 'uploads/payment/channels',
        'payout_method'   => 'uploads/payment/methods',
        // Admin-delivered task packages (zip) and worker JSON results
        'task_files'      => 'uploads/tasks/packages',
        'task_results'    => 'uploads/tasks/results',
        // Membership application documents
        'membership_docs' => 'uploads/membership/documents',
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase
    |--------------------------------------------------------------------------
    | FIREBASE_PROJECT_ID       — your Firebase project ID
    | FIREBASE_WEB_API_KEY      — Web API key (for social login token verify)
    | FIREBASE_CREDENTIALS_PATH — absolute path to service account JSON (FCM)
    | FIREBASE_CREDENTIALS_B64  — base64-encoded service account JSON (FCM, alt)
    */
    'firebase' => [
        'project_id'       => env('FIREBASE_PROJECT_ID'),
        'web_api_key'      => env('FIREBASE_WEB_API_KEY'),
        'credentials_path' => env('FIREBASE_CREDENTIALS_PATH'),
        'credentials_b64'  => env('FIREBASE_CREDENTIALS_B64'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Work Status Labels
    |--------------------------------------------------------------------------
    */
    'work_status' => [
        0 => 'Holding',
        1 => 'Active',
        2 => 'Finished',
    ],

    'approval_status' => [
        0 => 'Pending',
        1 => 'Approved',
        2 => 'Rejected',
    ],

    /*
    |--------------------------------------------------------------------------
    | Submission Status Labels
    |--------------------------------------------------------------------------
    | LEGACY. Mirrors the derived work_submissions.status column. Kept so old
    | views do not break. New code should use application_status / delivery_status
    | below, or the lifecycle_label accessor on WorkSubmission.
    */
    'submission_status' => [
        0 => 'Applied',
        1 => 'Under Review',
        2 => 'Approved',
        3 => 'Rejected',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application + Delivery Status Labels (migration 0067)
    |--------------------------------------------------------------------------
    | Two independent axes. application_status answers "is this worker allowed on
    | the task", delivery_status answers "how is the work going".
    */
    'application_status' => [
        0 => 'Awaiting Review',
        1 => 'Approved To Work',
        2 => 'Application Rejected',
    ],

    'delivery_status' => [
        0 => 'Not Started',
        1 => 'Submitted',
        2 => 'Revision Requested',
        3 => 'Approved',
        4 => 'Rejected',
    ],

    /*
    |--------------------------------------------------------------------------
    | KYC Status
    |--------------------------------------------------------------------------
    */
    'kyc_status' => [
        0 => 'Unverified',
        1 => 'Verified',
        2 => 'Pending',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ledger Entry Categories
    |--------------------------------------------------------------------------
    */
    'ledger_categories' => [
        'topup'       => 'Coin Top-Up',
        'cashout'     => 'Cash Out',
        'work_earn'   => 'Work Earnings',
        'work_spend'  => 'Work Budget',
        'work_refund' => 'Work Refund',
        'referral'    => 'Referral Bonus',
        'admin'       => 'Admin Adjustment',
        'transfer_sent'     => 'Coins Sent',
        'transfer_received' => 'Coins Received',
        // Microtask marketplace flows
        'task_apply'        => 'Task Application Fee',
        'task_apply_refund' => 'Application Fee Refund',
        'task_commission'   => 'Platform Commission',
    ],

    /*
    |--------------------------------------------------------------------------
    | Poster Types
    |--------------------------------------------------------------------------
    */
    'poster_types' => [
        1 => 'Admin',
        2 => 'User',
    ],

];
