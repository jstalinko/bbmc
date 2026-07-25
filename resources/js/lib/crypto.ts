import CryptoJS from 'crypto-js';

export function decryptPayload(encryptedBase64: string): any {
    if (!encryptedBase64) return null;
    try {
        const keyStr = import.meta.env.VITE_APP_ENCRYPTION_KEY || 'bbmc_secret_key_2026_xyz!';
        // Hash the key string to ensure it's 32 bytes (256 bits) for AES-256
        const key = CryptoJS.SHA256(keyStr);

        const encryptedBytes = CryptoJS.enc.Base64.parse(encryptedBase64);
        
        // Extract IV (first 16 bytes)
        const iv = CryptoJS.lib.WordArray.create(encryptedBytes.words.slice(0, 4), 16);
        
        // Extract ciphertext (remaining bytes)
        const ciphertext = CryptoJS.lib.WordArray.create(
            encryptedBytes.words.slice(4),
            encryptedBytes.sigBytes - 16
        );

        const decrypted = CryptoJS.AES.decrypt(
            { ciphertext: ciphertext } as any,
            key,
            {
                iv: iv,
                mode: CryptoJS.mode.CBC,
                padding: CryptoJS.pad.Pkcs7
            }
        );
        
        const jsonStr = decrypted.toString(CryptoJS.enc.Utf8);
        return JSON.parse(jsonStr);
    } catch (e) {
        console.error("Decryption failed", e);
        return null;
    }
}

export function encryptPayload(data: any): string {
    try {
        const keyStr = import.meta.env.VITE_APP_ENCRYPTION_KEY || 'bbmc_secret_key_2026_xyz!';
        const key = CryptoJS.SHA256(keyStr);
        
        // Generate random 16 bytes IV
        const iv = CryptoJS.lib.WordArray.random(16);
        
        const jsonStr = typeof data === 'string' ? data : JSON.stringify(data);
        
        const encrypted = CryptoJS.AES.encrypt(jsonStr, key, {
            iv: iv,
            mode: CryptoJS.mode.CBC,
            padding: CryptoJS.pad.Pkcs7
        });
        
        // Combine IV and Ciphertext
        const combined = iv.clone().concat(encrypted.ciphertext);
        
        return CryptoJS.enc.Base64.stringify(combined);
    } catch (e) {
        console.error("Encryption failed", e);
        return "";
    }
}
