<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase credentials path
    |--------------------------------------------------------------------------
    | Path to the Firebase Admin SDK service account JSON file (relative to
    | storage_path() or absolute). Used for FCM and other Firebase APIs.
    */
    'credentials' => env('FIREBASE_CREDENTIALS', 'storage/app/tijaar-d9365-firebase-adminsdk-fbsvc-e1877dff83.json'),

    /*
    |--------------------------------------------------------------------------
    | Firebase project ID (optional)
    |--------------------------------------------------------------------------
    | If not set, read from the credentials JSON. Set explicitly if you need
    | to override (e.g. different project for FCM).
    */
    'project_id' => env('FIREBASE_PROJECT_ID'),
];
