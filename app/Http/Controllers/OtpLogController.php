<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OtpLogController extends Controller
{
    public function index(Request $request)
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
        
        $otps = $query->paginate(15)->withQueryString();
        
        return Inertia::render('OtpLog/Index', [
            'otps' => $otps,
            'filters' => ['search' => $request->input('search', '')]
        ]);
    }

    public function resend(Request $request, Otp $otp)
    {
        $message = "*BBMC ELECTION 2026*\n\nKode OTP Anda adalah: *{$otp->otp}*\n\nJANGAN BERIKAN KODE INI KEPADA SIAPAPUN.";
        
        $result = \App\Helper::sendWhatsapp($otp->phone, $message);
        

        if (isset($result['success']) && $result['success'] === false || $result['status'] !== 200) {
            return back()->withErrors(['otp' => 'Gagal mengirim ulang OTP: ' . ($result['message'] ?? $result['error'] ?? 'Unknown error')]);
        }
        
        return back()->with('success', 'Kode OTP berhasil dikirim ulang ke WhatsApp.');
    }
}
