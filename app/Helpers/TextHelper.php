<?php

namespace App\Helpers;

use Modules\Core\Models\AdminSettings;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Meneses\LaravelMpdf\LaravelMpdfWrapper;
use PDF;

class TextHelper
{

    /**
     * @var TextHelper
     */
    protected static $instance = null;

    public static function instance(): ?TextHelper
    {
        if (self::$instance === null) {
            self::$instance = new TextHelper();
        }
        return self::$instance;
    }

    public static function getCurrentDomainName(HttpRequest $request)
    {
        $host = $request->getHost();
        $domain = parse_url($host, PHP_URL_HOST); // الحصول على اسم المضيف فقط
        $domainWithoutTld = preg_replace('/\.[^.]+$/', '', $domain); // التخلص من الامتداد
        return $domainWithoutTld;
    }

    /**
     * Format a message for safe display: trim, escape, and convert newlines to <br>.
     */
    public static function formatMessageForDisplay(?string $message): string
    {
        if ($message === null || $message === '') {
            return '';
        }
        $trimmed = trim($message);
        if ($trimmed === '') {
            return '';
        }
        return nl2br(e($trimmed));
    }

    public static function searchWords($string, $words)
    {
        $find_all = true;
        foreach ($words as $word) {
            if (stripos(strtolower($string), strtolower($word)) !== false) {
                $find_all &= true;
            } else {
                return false;
            }
        }
        return $find_all;
    }


    public static function hide_email($email)
    {
        $em = explode("@", $email);
        $name = implode('@', array_slice($em, 0, count($em) - 1));
        $len = floor(strlen($name) / 2);

        return substr($name, 0, $len) . str_repeat('*', $len) . "@" . end($em);
    }


    public static function hide_name($name)
    {
        if (auth()->user() && Auth::user()->isAdmin()){
            return $name;
        }
        if ($name == null) return null;
        $name = preg_replace('/\s+/', ' ', $name);
        $name_fragments = explode(" ", $name);

        // Loop over "John" and "Doe"
        $result = "";
        foreach ($name_fragments as $fragment) {
            if (mb_strlen($result) !== 0) {
                $result .= " ";
            }

            // Add clear first letter
            $result .= mb_substr($fragment, 0, 1);

            // Add asterisks
            $result .= str_repeat("*", mb_strlen($fragment) - 1);
        }
        return $result;
    }

    public static function hide_name_v2($name)
    {
        if ($name == null) return null;

        // Normalize spaces (remove extra spaces)
        $name = preg_replace('/\s+/', ' ', trim($name));

        // Split the name into fragments (e.g., "John Doe" -> ["John", "Doe"])
        $name_fragments = explode(" ", $name);

        // Build the result with only the first character of each fragment
        $result = "";
        foreach ($name_fragments as $index => $fragment) {
            if (mb_strlen($result) !== 0) {
                $result .= " "; // Add a space between fragments
            }

            if ($index == 0) {
                $result .= "*****";
                continue;
            }
            // Keep only the first character of the fragment
            $result .= mb_substr($fragment, 0, 1);
            $result .= "*****";
        }

        return trim($result);
    }

    public static function secondsToTime($init)
    {
        $day = floor($init / 86400);
        $hours = floor(($init - ($day * 86400)) / 3600);
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;

        if (!empty($day)) {
            $day = Carbon::now()->subDays($day)->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE);
        } else {
            $day = '';
        }
        return (!empty($day) ? $day . ' ' : '') . "{$hours}h:{$minutes}m:{$seconds}s";
    }

    /**
     * Total seconds as hours with a fixed decimal precision (e.g. timers summary).
     */
    public static function secondsToDecimalHours($seconds, int $decimals = 2): string
    {
        $seconds = (float) $seconds;
        if ($seconds <= 0) {
            return number_format(0, $decimals, '.', '');
        }

        return number_format($seconds / 3600, $decimals, '.', '');
    }

    public function arNum2enArray($value)
    {
        $array = array();
        foreach ($value as $k => $v) {
            $array[$k] = $this->arNum2en($v);
        }
        return $array;
    }


    public static function prep_url($str = '')
    {
        if ($str === 'http://' or $str === '') {
            return '';
        }
        $url = parse_url($str);
        if (!$url or !isset($url['scheme'])) {
            return 'http://' . $str;
        }
        return $str;
    }

    public static function array2csv($data, $delimiter = ',', $enclosure = '"', $escape_char = "\\")
    {
        $f = fopen('php://memory', 'r+');
        foreach ($data as $item) {
            fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
        }
        rewind($f);
        return stream_get_contents($f);
    }

    public function fix_numbers($numbers)
    {
        $num = $this->arNum2en($numbers);
        $res = preg_replace("/[^0-9]/", "", $num);
        return $res;
    }

    public function arNum2en($value)
    {
        $value = str_replace('٠', '0', $value);
        $value = str_replace('١', '1', $value);
        $value = str_replace('٢', '2', $value);
        $value = str_replace('٣', '3', $value);
        $value = str_replace('٤', '4', $value);
        $value = str_replace('٥', '5', $value);
        $value = str_replace('٦', '6', $value);
        $value = str_replace('٧', '7', $value);
        $value = str_replace('٨', '8', $value);
        $value = str_replace('٩', '9', $value);
        return $value;
    }

    public static function TwoChar($name)
    {
        $ex = explode(' ', $name);
        if (count($ex) == 2) {
            return strtoupper(mb_substr($ex[0], 0, 1)) . strtoupper(mb_substr($ex[1], 0, 1));
        }
        if (count($ex) == 1) {
            return strtoupper(mb_substr($ex[0], 0, 1));
        }
        return 'NO';
    }

    public static function numberToColumnName($number)
    {
        $abc = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";
        $len = strlen($abc);

        $result = "";
        while ($number > 0) {
            $index = $number % $len;
            $result = $abc[$index] . $result;
            $number = floor($number / $len);
        }
        if ($result == '') return "A";
        return $result;
    }

    public function crockford32_encode($data)
    {
        $chars = '0123456789abcdefghjkmnpqrstvwxyz';
        $mask = 0b11111;

        $dataSize = strlen($data);
        $res = '';
        $remainder = 0;
        $remainderSize = 0;

        for ($i = 0; $i < $dataSize; $i++) {
            $b = ord($data[$i]);
            $remainder = ($remainder << 8) | $b;
            $remainderSize += 8;
            while ($remainderSize > 4) {
                $remainderSize -= 5;
                $c = $remainder & ($mask << $remainderSize);
                $c >>= $remainderSize;
                $res .= $chars[$c];
            }
        }
        if ($remainderSize > 0) {
            $remainder <<= (5 - $remainderSize);
            $c = $remainder & $mask;
            $res .= $chars[$c];
        }

        return $res;
    }

    public function crockford_encodeC($base10)
    {
        $key = "encryption key";
        return bin2hex(openssl_encrypt($base10, 'AES-128-CBC', $key));
    }

    public function crockford_encode($data)
    {
        $chars = strtoupper('abcdefghjkmnpqrstvwxyz');
        $mask = 0b1111;

        $dataSize = strlen($data);
        $res = '';
        $remainder = 0;
        $remainderSize = 0;

        for ($i = 0; $i < $dataSize; $i++) {
            $b = ord($data[$i]);
            $remainder = ($remainder << 8) | $b;
            $remainderSize += 8;
            while ($remainderSize > 4) {
                $remainderSize -= 5;
                $c = $remainder & ($mask << $remainderSize);
                $c >>= $remainderSize;
                $res .= $chars[$c];
            }
        }
        if ($remainderSize > 0) {
            $remainder <<= (5 - $remainderSize);
            $c = $remainder & $mask;
            $res .= $chars[$c];
        }

        return $res;
    }

    public function crockford_encode2($number)
    {
        $chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ'; // Crockford Base32 alphabet
        $base = strlen($chars);
        $encoded = '';

        while ($number > 0) {
            $remainder = $number % $base;
            $number = intdiv($number, $base);
            $encoded = $chars[$remainder] . $encoded;
        }

        return $encoded ?: '0'; // Handle the case where $number is 0
    }

    public function generateInvoiceId($created_at, $id)
    {
        $date = date('ymd', strtotime($created_at));
        $randomPart = $this->crockford_encode2($id);
        return $date . '-' . $randomPart;
    }

    public function crockford_decode2($encoded)
    {
        $chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ'; // Crockford Base32 alphabet
        $base = strlen($chars);
        $decoded = 0;

        // Split the encoded string into two parts
        $parts = explode('-', trim($encoded));

        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Invalid encoded string format.");
        }

        $part = $parts[1];
        // Decode each part separately
        $decoded = $decoded * pow($base, strlen($part)); // Shift the previous decoded value
        // Reverse the part to decode from right to left
        $part = strrev($part);

        // Decode the characters in the part
        for ($i = 0; $i < strlen($part); $i++) {
            $char = $part[$i];
            $value = strpos($chars, $char);

            if ($value === false) {
                throw new InvalidArgumentException("Invalid character '{$char}' in encoded string.");
            }

            $decoded += $value * pow($base, $i);
        }

        return $decoded;
    }


    public function detect_utf_encoding($filename)
    {
        define('UTF32_BIG_ENDIAN_BOM', chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
        define('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
        define('UTF16_BIG_ENDIAN_BOM', chr(0xFE) . chr(0xFF));
        define('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
        define('UTF8_BOM', chr(0xEF) . chr(0xBB) . chr(0xBF));

        $text = file_get_contents($filename);
        $first2 = substr($text, 0, 2);
        $first3 = substr($text, 0, 3);
        $first4 = substr($text, 0, 3);

        if ($first3 == UTF8_BOM) return 'UTF-8';
        elseif ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
        elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
        elseif ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
        elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';
    }

    public function get_ip_address()
    {

        // Check for shared Internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        // Check for IP addresses passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

            // Check if multiple IP addresses exist in var
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
                $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($iplist as $ip) {
                    if ($this->validate_ip($ip))
                        return $ip;
                }
            } else {
                if ($this->validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_X_FORWARDED']))
            return $_SERVER['HTTP_X_FORWARDED'];
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
            return $_SERVER['HTTP_FORWARDED_FOR'];
        if (!empty($_SERVER['HTTP_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_FORWARDED']))
            return $_SERVER['HTTP_FORWARDED'];

        // Return unreliable IP address since all else failed
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Ensures an IP address is both a valid IP address and does not fall within
     * a private network range.
     */
    public function validate_ip($ip)
    {

        if (strtolower($ip) === 'unknown')
            return false;

        // Generate IPv4 network address
        $ip = ip2long($ip);

        // If the IP address is set and not equivalent to 255.255.255.255
        if ($ip !== false && $ip !== -1) {
            // Make sure to get unsigned long representation of IP address
            // due to discrepancies between 32 and 64 bit OSes and
            // signed numbers (ints default to signed in PHP)
            $ip = sprintf('%u', $ip);

            // Do private network range checking
            if ($ip >= 0 && $ip <= 50331647)
                return false;
            if ($ip >= 167772160 && $ip <= 184549375)
                return false;
            if ($ip >= 2130706432 && $ip <= 2147483647)
                return false;
            if ($ip >= 2851995648 && $ip <= 2852061183)
                return false;
            if ($ip >= 2886729728 && $ip <= 2887778303)
                return false;
            if ($ip >= 3221225984 && $ip <= 3221226239)
                return false;
            if ($ip >= 3232235520 && $ip <= 3232301055)
                return false;
            if ($ip >= 4294967040)
                return false;
        }
        return true;
    }

    public static function pdfInvoice($invoice)
    {

        $invoice_items = $invoice->items()->get();
        $user = $invoice->user()->first();
        $project = $invoice->project()->first();

        $qr_img = null;

        $pdf = \Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::loadView('admin.invoices.download', compact('invoice', 'invoice_items', 'user', 'project', 'qr_img'));
        return $pdf;
    }

    public static function currency_html_invoice($invoice): string
    {
        if ($invoice->currency != AdminSettings::GetValue('business_currency', '2')) {
            if ($invoice->status_str() == 'Partially_paid') {
                $text = 'Paid ' . $invoice->business_paid_str() . ' of ' . $invoice->business_total_str();
            } else {
                $text = $invoice->business_total_str();
            }
            return 'data-toggle="tooltip" data-placement="top" title="' . $text . '"';
        } else {
            return '';
        }
    }

    public static function getHost($Address)
    {
        $parseUrl = parse_url(trim($Address));
        if (isset($parseUrl['host'])) {
            $domain = trim($parseUrl['host']);
        } else {
            $a = explode('/', $parseUrl['path'], 2);
            $domain = trim(array_shift($a));
        }
        return str_ireplace('www.', '', $domain);
    }

    public static function parse_link($message)
    {
        $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
        if (strlen($message) > 512) return htmlentities($message);
        $message = preg_replace("/(\r?\n){2,}/", "\n", htmlentities($message));
        if (preg_match_all($reg_exUrl, $message, $urls)) {
            foreach ($urls[0] as $url) {
                //  $message = preg_replace($reg_exUrl, "<a href=\"{$url}\" target=\"_blank\">{$url}</a>", $message);
                $message = str_replace($url, "<a href=\"{$url}\" target=\"_blank\">{$url}</a>", $message);
            }
        }
        return $message;
    }
}
