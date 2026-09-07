<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:send-wa {recipient} {message=Hello brother 123456 is your otp} {--otp=} {--type=}', function ($recipient, $message) {
    echo "Sending type: " . config('services.whatsapp.service') . PHP_EOL;
    $options = [];
    if ($otp = $this->option('otp')) {
        $options['otp'] = $otp;
    }
    if ($type = $this->option('type')) {
        $options['type'] = $type;
    }

    $this->info("Sending WhatsApp message to: {$recipient}");
    $result = \App\Helper::sendWhatsapp($recipient, $message, $options);
    $this->line(json_encode($result, JSON_PRETTY_PRINT));
})->purpose('Send a WhatsApp test message via configured WhatsApp service');
