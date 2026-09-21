<?php

namespace App\Jobs;

use App\Helper;
use App\Models\WhatsappLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $logId;

    /**
     * Create a new job instance.
     */
    public function __construct($logId)
    {
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = WhatsappLog::find($this->logId);
        if (!$log) {
            return;
        }

        $log->update(['status' => 'sending']);

        $message = $log->message;
        $options = [];

        if (Helper::isBionService()) {
            $otp = (string) rand(100000, 999999);
            $options = [
                'otp_code' => $otp,
            ];

            if (empty($message) || str_starts_with($message, 'Template:')) {
                $message = Helper::getRandomOtpMessage($otp, 'login');
            }
        }

        $response = Helper::sendWhatsapp($log->recipient_phone, $message, $options);

        $success = false;
        if (is_array($response)) {
            if (isset($response['success'])) {
                $success = (bool) $response['success'];
            } elseif (isset($response['status']) && in_array($response['status'], [200, '200', 'success'])) {
                $success = true;
            } elseif (
                isset($response['message_id']) ||
                isset($response['data']['messageId']) ||
                isset($response['message']['queue_id'])
            ) {
                $success = true;
            }
        }

        if ($success) {
            $log->update([
                'status' => 'success',
                'response' => json_encode($response),
            ]);
        } else {
            $log->update([
                'status' => 'failed',
                'response' => json_encode($response),
            ]);
        }
    }
}
