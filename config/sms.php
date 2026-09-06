<?php

return [

    'driver' => env('SMS_DRIVER', 'log'),

    'kavenegar' => [
        'api_key' => env('KAVENEGAR_API_KEY'),
        'sender' => env('KAVENEGAR_SENDER'),
        'otp_template' => env('KAVENEGAR_OTP_TEMPLATE', 'verify'),
    ],

    'smsir' => [
        'api_key' => env('SMSIR_API_KEY'),
        'template_id' => env('SMSIR_TEMPLATE_ID'),
        'otp_parameter' => env('SMSIR_OTP_PARAMETER', 'Code'),
        'line_number' => env('SMSIR_LINE_NUMBER'),
        'resend_minutes' => (int) env('SMSIR_RESEND_MINUTES', 2),
    ],

];
