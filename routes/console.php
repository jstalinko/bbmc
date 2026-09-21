<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$sendWaHandler = function ($recipient = null, $message = null) {
    if (empty($recipient)) {
        $recipient = $this->ask('Masukkan nomor HP penerima WhatsApp');
    }

    if (empty($recipient)) {
        $this->error('Nomor HP tidak boleh kosong.');
        return 1;
    }

    $otp = $this->option('otp');
    if (empty($otp)) {
        $otp = (string) rand(100000, 999999);
    }

    if (empty($message)) {
        $message = \App\Helper::getRandomOtpMessage($otp, 'login');
    }

    $options = [
        'otp_code' => $otp,
    ];

    if ($type = $this->option('type')) {
        $options['type'] = $type;
    }

    $service = \App\Helper::getWhatsappService();
    $this->info("Sending WhatsApp message via {$service} to: {$recipient} (OTP: {$otp})...");

    $result = \App\Helper::sendWhatsapp($recipient, $message, $options);
    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return (isset($result['success']) && $result['success']) ? 0 : 1;
};

Artisan::command('test:wa-send {recipient?} {message?} {--otp=} {--type=}', $sendWaHandler)
    ->purpose('Send a WhatsApp test message via configured WhatsApp service');

Artisan::command('test:send-wa {recipient?} {message?} {--otp=} {--type=}', $sendWaHandler)
    ->purpose('Send a WhatsApp test message via configured WhatsApp service');

