<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OtpLogController extends Controller
{
    private function buildExportQuery(Request $request)
    {
        $query = Otp::with('member')->orderBy('created_at', 'desc');
        
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('otp', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('nama_lengkap', 'like', "%{$search}%")
                         ->orWhere('no_kartu', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'verified') {
                $query->where('is_verified', true);
            } elseif ($status === 'unverified') {
                $query->where('is_verified', false);
            }
        }
        
        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->buildExportQuery($request);
        $otps = $query->paginate(15)->withQueryString();
        
        return Inertia::render('OtpLog/Index', [
            'otps' => $otps,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all')
            ]
        ]);
    }

    public function exportCsv(Request $request)
    {
        $otps = $this->buildExportQuery($request)->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="data_otp_logs_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($otps) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($file, [
                'No.',
                'Tanggal',
                'Waktu',
                'No. Kartu',
                'Nama Lengkap',
                'No. HP',
                'OTP',
                'Status',
                'Kedaluwarsa',
            ]);

            foreach ($otps as $index => $otp) {
                fputcsv($file, [
                    $index + 1,
                    $otp->created_at ? $otp->created_at->format('Y-m-d') : '—',
                    $otp->created_at ? $otp->created_at->format('H:i:s') : '—',
                    $otp->member ? $otp->member->no_kartu : '—',
                    $otp->member ? $otp->member->nama_lengkap : 'Member Deleted',
                    $otp->phone,
                    $otp->otp,
                    $otp->is_verified ? 'Terverifikasi' : 'Menunggu',
                    $otp->expires_at ? \Carbon\Carbon::parse($otp->expires_at)->format('Y-m-d H:i:s') : '—',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $otps = $this->buildExportQuery($request)->get();

        $status = $request->input('status', 'all');
        $filterDesc = 'Semua Status';
        if ($status === 'verified') {
            $filterDesc = 'Status: Terverifikasi';
        } elseif ($status === 'unverified') {
            $filterDesc = 'Status: Menunggu / Belum Terverifikasi';
        }

        if ($search = $request->input('search')) {
            $filterDesc .= ($filterDesc ? ' & ' : '') . ' | Pencarian: "' . $search . '"';
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('otplog.export-pdf', [
            'otps' => $otps,
            'filter_desc' => $filterDesc,
        ]);

        return $pdf->download('data_otp_logs_' . date('Ymd_His') . '.pdf');
    }

    public function resend(Request $request, Otp $otp)
    {
        $message = \App\Helper::getRandomOtpMessage($otp->otp, 'umum');
        
        $result = \App\Helper::sendWhatsapp($otp->phone, $message);
        

        if (isset($result['success']) && $result['success'] === false || $result['status'] !== 200) {
            return back()->withErrors(['otp' => 'Gagal mengirim ulang OTP: ' . ($result['message'] ?? $result['error'] ?? 'Unknown error')]);
        }
        
        return back()->with('success', 'Kode OTP berhasil dikirim ulang ke WhatsApp.');
    }

    public function resetAll(Request $request)
    {
        $deleted = Otp::whereDate('created_at', now()->toDateString())
            ->where('is_verified', false)
            ->delete();

        $message = $deleted > 0
            ? "Berhasil mereset limit OTP. Sebanyak {$deleted} kode OTP hari ini yang belum terverifikasi telah dihapus."
            : "Tidak ada kode OTP hari ini yang belum terverifikasi untuk dihapus.";

        return back()->with('success', $message);
    }

    public function resetByUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ], [
            'user_id.required' => 'User ID / Member ID wajib diisi.',
        ]);

        $userId = trim((string) $request->input('user_id'));

        $member = Member::where('id', $userId)
            ->orWhere('no_kartu', $userId)
            ->first();

        $memberId = $member ? $member->id : $userId;

        $deleted = Otp::whereDate('created_at', now()->toDateString())
            ->where(function ($q) use ($userId, $memberId) {
                $q->where('user_id', $userId)
                  ->orWhere('member_id', $userId)
                  ->orWhere('member_id', $memberId);
            })
            ->delete();

        $targetLabel = $member ? "{$member->nama_lengkap} (ID: {$member->id})" : "User ID {$userId}";
        $message = $deleted > 0
            ? "Berhasil mereset limit OTP untuk {$targetLabel}. Sebanyak {$deleted} kode OTP hari ini telah dihapus."
            : "Tidak ditemukan data OTP hari ini untuk {$targetLabel}. Kuota OTP aman.";

        return back()->with('success', $message);
    }
}
