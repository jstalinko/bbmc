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
    
    public static function sendWhatsapp($recipient, $message, $options = [])
    {
        $recipient = self::cleanPhone($recipient);
        if (empty($recipient) || empty($message)) {
            return [
                "success" => false,
                "message" => "Recipient or message is empty, skipping send."
            ];
        }

        $service = config('services.whatsapp.service', env('WHATSAPP_SERVICE', 'piwapi'));

        $path = storage_path('app/private/pemilihan-setting.json');
        if (file_exists($path)) {
            $settings = json_decode(file_get_contents($path), true);
            if (!empty($settings['whatsapp_service'])) {
                $service = $settings['whatsapp_service'];
            } elseif (!empty($settings['piwapi_api_secret_key']) || !empty($settings['piwapi'])) {
                $service = 'piwapi';
            }
        }

        $service = strtolower(trim($service));

        if ($service === 'bion' || $service === 'bion.id' || $service === 'bion_id') {
            return self::sendWhatsappViaBion($recipient, $message, $options);
        }

        return self::sendWhatsappViaPiwapi($recipient, $message);
    }

    public static function sendWhatsappViaPiwapi($recipient, $message)
    {
        $secret = config('services.whatsapp.piwapi.api_secret_key', env('PIWAPI_API_SECRET_KEY'));
        $account = config('services.whatsapp.piwapi.account_id', env('PIWAPI_ACCOUNT_ID'));
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

    public static function sendWhatsappViaBion($recipient, $message, $options = [])
    {
        $apiUrl = config('services.whatsapp.bion.api_url', env('BION_API_URL', 'https://crmapis2.bion.id/api/meta'));
        $version = config('services.whatsapp.bion.api_version', env('BION_API_VERSION', 'v19.0'));
        $phoneNumberId = config('services.whatsapp.bion.phone_number_id', env('BION_PHONE_NUMBER_ID', '115952861601111'));
        $accessToken = config('services.whatsapp.bion.access_token', env('BION_ACCESS_TOKEN', ''));
        $templateName = config('services.whatsapp.bion.auth_template_name', env('BION_AUTH_TEMPLATE_NAME', 'otp_bikers_mc'));
        $language = config('services.whatsapp.bion.template_language', env('BION_TEMPLATE_LANGUAGE', 'en_US'));

        $path = storage_path('app/private/pemilihan-setting.json');
        if (file_exists($path)) {
            $settings = json_decode(file_get_contents($path), true);
            if (!empty($settings['bion_api_url'])) $apiUrl = $settings['bion_api_url'];
            if (!empty($settings['bion_api_version'])) $version = $settings['bion_api_version'];
            if (!empty($settings['bion_phone_number_id'])) $phoneNumberId = $settings['bion_phone_number_id'];
            if (!empty($settings['bion_access_token'])) $accessToken = $settings['bion_access_token'];
            if (!empty($settings['bion_auth_template_name'])) $templateName = $settings['bion_auth_template_name'];
            if (!empty($settings['bion_template_language'])) $language = $settings['bion_template_language'];
        }

        // Allow options to be a direct scalar OTP (e.g. 123456 or "123456")
        if (is_numeric($options) || is_string($options)) {
            $options = ['otp' => (string) $options];
        }

        if (!empty($options['template_name'])) {
            $templateName = $options['template_name'];
        }
        if (!empty($options['language'])) {
            $language = $options['language'];
        }

        $otp = $options['otp'] ?? $options['otp_code'] ?? null;
        if (!$otp && is_string($message) && preg_match('/\b([0-9]{6})\b/', $message, $matches)) {
            $otp = $matches[1];
        } elseif (!$otp && is_string($message) && preg_match('/\b([0-9]{4,8})\b/', $message, $matches)) {
            $otp = $matches[1];
        }
        if (!$otp && is_numeric($message)) {
            $otp = (string) $message;
        }

        $url = rtrim($apiUrl, '/') . '/' . trim($version, '/') . '/' . trim($phoneNumberId, '/') . '/messages';

        if (($options['type'] ?? null) === 'text') {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'text',
                'text' => [
                    'body' => $message,
                ],
            ];
        } else {
            // Template: otp_bikers_mc#en_US with {{BODY_VARIABLE_1}} (message) and {{BUTTON_VARIABLE}} (otp)
            $bodyText = $options['body'] ?? $message;
            $buttonOtp = (string) ($otp ?? $message);

            $components = $options['components'] ?? [
                [
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => (string) $bodyText,
                        ],
                    ],
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => 0,
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $buttonOtp,
                        ],
                    ],
                ],
            ];

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'template',
                'template' => [
                    'language' => [
                        'policy' => 'deterministic',
                        'code' => $language,
                    ],
                    'name' => $templateName,
                    'components' => $components,
                ],
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $data = $response->json();
            if ($response->successful() && !isset($data['error'])) {
                return array_merge([
                    'success' => true,
                    'status' => $response->status(),
                ], is_array($data) ? $data : ['data' => $data]);
            }

            $errorMessage = 'Failed to send WhatsApp message via Bion.';
            if (isset($data['error'])) {
                $errorMessage = is_array($data['error']) ? ($data['error']['message'] ?? json_encode($data['error'])) : (string)$data['error'];
            } elseif (isset($data['message']) && is_string($data['message'])) {
                $errorMessage = $data['message'];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 500,
                'error' => $e->getMessage(),
            ];
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