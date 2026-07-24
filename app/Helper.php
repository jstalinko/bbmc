<?php
namespace App;
use Illuminate\Support\Facades\Http;
Class Helper{

    public static function cleanPhone($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);
        $phone = preg_replace('/^08/', '628', $phone);

        // Pastikan jika depannya masih 0, diubah ke 62 (opsional tergantung input)
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

}