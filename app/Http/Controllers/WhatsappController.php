<?php

namespace App\Http\Controllers;

use App\Helper;
use App\Models\Member;
use App\Models\WhatsappLog;
use App\Jobs\SendWhatsappJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WhatsappController extends Controller
{
    /**
     * Render the WhatsApp Blast page with members list.
     */
    public function index()
    {
        $members = Member::select('id', 'nama_lengkap', 'no_wa', 'email', 'no_kartu')->get();
        return Inertia::render('Whatsapp/index', [
            'members' => $members,
        ]);
    }

    /**
     * Handle sending bulk messages (sync for <= 2, async queue for > 2).
     */
    public function send(Request $request)
    {
        $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:members,id',
            'message' => 'required|string',
        ]);

        $memberIds = $request->input('member_ids');
        $messageTemplate = $request->input('message');
        $members = Member::whereIn('id', $memberIds)->get();

        $batchId = 'blast_' . Str::random(10);
        $logs = [];

        foreach ($members as $member) {
            $personalizedMessage = $this->replacePlaceholders($messageTemplate, $member);
            
            $log = WhatsappLog::create([
                'batch_id' => $batchId,
                'recipient_name' => $member->nama_lengkap,
                'recipient_phone' => $member->no_wa ?? '',
                'message' => $personalizedMessage,
                'status' => 'pending',
            ]);

            $logs[] = $log;
        }

        $count = count($logs);

        if ($count <= 2) {
            // Process synchronously
            foreach ($logs as $log) {
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

            return response()->json([
                'queued' => false,
                'batch_id' => $batchId,
                'logs' => WhatsappLog::where('batch_id', $batchId)->get(),
                'stats' => [
                    'total' => $count,
                    'completed' => $count,
                    'success' => WhatsappLog::where('batch_id', $batchId)->where('status', 'success')->count(),
                    'failed' => WhatsappLog::where('batch_id', $batchId)->where('status', 'failed')->count(),
                    'progress' => 100,
                ]
            ]);
        } else {
            // Process asynchronously via Queue
            foreach ($logs as $log) {
                SendWhatsappJob::dispatch($log->id);
            }

            return response()->json([
                'queued' => true,
                'batch_id' => $batchId,
                'stats' => [
                    'total' => $count,
                    'completed' => 0,
                    'success' => 0,
                    'failed' => 0,
                    'progress' => 0,
                ]
            ]);
        }
    }

    /**
     * Get real-time status of a batch.
     */
    public function status($batchId)
    {
        $logs = WhatsappLog::where('batch_id', $batchId)->get();
        $total = $logs->count();
        $completed = $logs->whereIn('status', ['success', 'failed'])->count();
        $success = $logs->where('status', 'success')->count();
        $failed = $logs->where('status', 'failed')->count();

        return response()->json([
            'logs' => $logs,
            'stats' => [
                'total' => $total,
                'completed' => $completed,
                'success' => $success,
                'failed' => $failed,
                'progress' => $total > 0 ? round(($completed / $total) * 100) : 0,
            ]
        ]);
    }

    /**
     * Helper to replace dynamic placeholders with Member data.
     */
    private function replacePlaceholders($message, $member)
    {
        return preg_replace_callback('/\[\[([a-zA-Z0-9_]+)\]\]/', function ($matches) use ($member) {
            $key = strtolower($matches[1]);

            // Aliases
            if ($key === 'name') {
                $key = 'nama_lengkap';
            }

            if (isset($member->{$key})) {
                return $member->{$key};
            }

            return $matches[0]; // Keep raw placeholder if not matched
        }, $message);
    }
}
