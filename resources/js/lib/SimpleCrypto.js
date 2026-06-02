import CryptoJS from 'crypto-js';

export default class SimpleCrypto {
    constructor(secret) {
        if (!secret) {
            throw new Error("SimpleCrypto object MUST BE initialised with a SECRET KEY.");
        }
        // Hash the secret using SHA3-512 to match the old project
        this._secret = CryptoJS.SHA3(secret.toString(), { outputLength: 512 }).toString();
        this._keySize = 256;
        this._iterations = 100;
    }

    encrypt(plainText) {
        if (plainText === undefined || plainText === null) {
            throw new Error("No data provided. Process halted.");
        }
        const plainStr = typeof plainText === 'object' ? JSON.stringify(plainText) : plainText.toString();
        
        // Generate random salt and IV
        const salt = CryptoJS.lib.WordArray.random(128 / 8);
        const iv = CryptoJS.lib.WordArray.random(128 / 8);
        
        // Derive key using PBKDF2 with SHA1
        const key = CryptoJS.PBKDF2(this._secret, salt, {
            keySize: this._keySize / 32,
            iterations: this._iterations,
            hasher: CryptoJS.algo.SHA1
        });
        
        const encrypted = CryptoJS.AES.encrypt(plainStr, key, {
            iv: iv,
            padding: CryptoJS.pad.Pkcs7,
            mode: CryptoJS.mode.CBC
        });
        
        const cipherText = salt.toString() + iv.toString() + encrypted.toString();
        const hmac = CryptoJS.HmacSHA256(cipherText, key).toString();
        
        return cipherText + hmac;
    }

    decrypt(cipherText) {
        if (!cipherText || cipherText.length <= 64) {
            throw new Error("Invalid cipher text. Decryption halted.");
        }
        
        const salt = CryptoJS.enc.Hex.parse(cipherText.substring(0, 32));
        const iv = CryptoJS.enc.Hex.parse(cipherText.substring(32, 64));
        const encrypted = cipherText.substring(64, cipherText.length - 64);
        const targetHmac = cipherText.substring(cipherText.length - 64);
        const message = cipherText.substring(0, cipherText.length - 64);
        
        // Derive key using PBKDF2 with SHA1
        const key = CryptoJS.PBKDF2(this._secret, salt, {
            keySize: this._keySize / 32,
            iterations: this._iterations,
            hasher: CryptoJS.algo.SHA1
        });
        
        const computedHmac = CryptoJS.HmacSHA256(message, key).toString();
        if (computedHmac !== targetHmac) {
            throw new Error("Invalid encrypted text received. Decryption halted.");
        }
        
        const decrypted = CryptoJS.AES.decrypt(encrypted, key, {
            iv: iv,
            padding: CryptoJS.pad.Pkcs7,
            mode: CryptoJS.mode.CBC
        });
        
        const decryptedStr = decrypted.toString(CryptoJS.enc.Utf8);
        if (decryptedStr.toLowerCase() === "true" || decryptedStr.toLowerCase() === "false") {
            return decryptedStr.toLowerCase() === "true";
        }
        try {
            return JSON.parse(decryptedStr);
        } catch (e) {
            return /^-?[\d.]+(?:e-?\d+)?$/.test(decryptedStr) && !isNaN(parseFloat(decryptedStr)) 
                ? parseFloat(decryptedStr) 
                : decryptedStr;
        }
    }
}
