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

        $response = Helper::sendWhatsapp($log->recipient_phone, $log->message);

        $success = false;
        if (is_array($response)) {
            if (
                (isset($response['success']) && $response['success'] === true) ||
                (isset($response['status']) && in_array($response['status'], [200, '200', 'success'])) ||
                isset($response['message_id']) ||
                isset($response['data']['messageId'])
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
