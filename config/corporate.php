<?php

return [
    /*
    | Where corporate leads (proposal requests + paid strategy sessions from the
    | /corporate page) are emailed. Override with CORPORATE_LEAD_EMAIL in .env.
    */
    'lead_email' => env('CORPORATE_LEAD_EMAIL', 'shulesoftcompany@gmail.com'),
];
