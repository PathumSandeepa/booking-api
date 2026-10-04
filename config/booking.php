<?php

return [
    'max_advance_days' => (int) env('BOOKING_MAX_ADVANCE_DAYS', 90),

    'rate_limit' => env('BOOKING_RATE_LIMIT', '60,1'),
];
