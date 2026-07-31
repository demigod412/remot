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
    */
    'submission_status' => [
        0 => 'Applied',
        1 => 'Under Review',
        2 => 'Approved',
        3 => 'Rejected',
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
