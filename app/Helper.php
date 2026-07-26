<?php
namespace App;
use Illuminate\Support\Facades\Http;
Class Helper{

    public static function cleanPhone($phone)
    {
        $phone = preg_replace('/^\+/', '', $phone);
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '1')) {
            return $phone;
        }

        $phone = preg_replace('/^08/', '628', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        return $phone;
    }
    
    public static function sendWhatsapp($recipient , $message)
    {
        $secret = env('PIWAPI_API_SECRET_KEY');
        $account = env('PIWAPI_ACCOUNT_ID');
        $path = storage_path('app/private/pemilihan-setting.json');
        if (file_exists($path)) {
            $settings = json_decode(file_get_contents($path), true);
            
            $piwapiList = $settings['piwapi'] ?? [];
            if (!empty($piwapiList) && is_array($piwapiList)) {
                $randomAccount = $piwapiList[array_rand($piwapiList)];
                if (!empty($randomAccount['secret_key'])) {
                    $secret = $randomAccount['secret_key'];
                }
                if (!empty($randomAccount['account_id'])) {
                    $account = $randomAccount['account_id'];
                }
            } else {
                if (!empty($settings['piwapi_api_secret_key'])) {
                    $secret = $settings['piwapi_api_secret_key'];
                }
                if (!empty($settings['piwapi_account_id'])) {
                    $account = $settings['piwapi_account_id'];
                }
            }
        }
        $recipient = self::cleanPhone($recipient);
        if (empty($recipient) || empty($message)) {
            return [
                "success" => false,
                "message" => "Recipient or message is empty, skipping send."
            ];
        }

        $url = "https://piwapi.com/api/send/whatsapp";
        $postFields = [
            "secret"    => $secret,
            "account"   => $account,
            "recipient" => self::cleanPhone($recipient),
            "type"      => "text",
            "message"   => $message,
        ];

        try {
            $response = Http::asForm()->post($url, $postFields);
            return $response->json();
        } catch (\Exception $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    public static function encryptForFrontend($data)
    {
        $key = env('VITE_APP_ENCRYPTION_KEY', 'bbmc_secret_key_2026_xyz!');
        // ensure key is 32 bytes (256 bits)
        $key = substr(hash('sha256', $key, true), 0, 32);
        $iv = random_bytes(16);
        
        $encrypted = openssl_encrypt(json_encode($data), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decryptFromFrontend($encryptedBase64)
    {
        $key = env('VITE_APP_ENCRYPTION_KEY', 'bbmc_secret_key_2026_xyz!');
        $key = substr(hash('sha256', $key, true), 0, 32);
        
        $data = base64_decode($encryptedBase64);
        if ($data === false || strlen($data) < 16) {
            return null;
        }
        
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        $jsonDecoded = json_decode($decrypted, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $jsonDecoded : $decrypted;
    }

    public static function getRandomOtpMessage($otpCode, $type = 'umum')
    {
        $konteks = "proses ini";
        if ($type === 'login') {
            $konteks = "proses login portal";
        } elseif ($type === 'nomination') {
            $konteks = "proses pengajuan pencalonan";
        }

        $templates = [
            "*BBMC ELECTION 2026*\n\nKode OTP Anda untuk $konteks adalah: *$otpCode*\n\nBerlaku selama 5 menit. JANGAN BERIKAN KODE INI KEPADA SIAPAPUN.",
            "*BBMC ELECTION 2026*\n\nIni adalah kode rahasia OTP Anda untuk $konteks: *$otpCode*\n\nKode ini hangus dalam 5 menit. Harap simpan dan jangan bagikan ke orang lain.",
            "*BBMC ELECTION 2026*\n\nPerhatian! Kode OTP $konteks Anda: *$otpCode*\n\nWaktu berlaku 5 menit. Mohon tidak memberitahukan kode ini pada siapapun demi keamanan.",
            "*BBMC ELECTION 2026*\n\nSilakan gunakan kode OTP berikut untuk $konteks: *$otpCode*\n\nMasa aktif kode ini hanya 5 menit. Tolong rahasiakan dari siapapun.",
            "*BBMC ELECTION 2026*\n\nBerikut kode OTP Anda ($konteks): *$otpCode*\n\nValid untuk 5 menit ke depan. Jaga kerahasiaan kode ini dengan tidak membagikannya."
        ];
        return $templates[array_rand($templates)];
    }
}