<?php

namespace App\Services;

use App\Services\Tools\TextToolsService;
use App\Services\Tools\CalculationToolsService;
use App\Services\Tools\DateToolsService;
use App\Services\Tools\ConversionToolsService;
use App\Services\Tools\GenerationToolsService;
use App\Services\Tools\UtilityToolsService;

class ToolsService
{
    protected $textTools;
    protected $calculationTools;
    protected $dateTools;
    protected $conversionTools;
    protected $generationTools;
    protected $utilityTools;

    public function __construct(
        TextToolsService $textTools,
        CalculationToolsService $calculationTools,
        DateToolsService $dateTools,
        ConversionToolsService $conversionTools,
        GenerationToolsService $generationTools,
        UtilityToolsService $utilityTools
    ) {
        $this->textTools = $textTools;
        $this->calculationTools = $calculationTools;
        $this->dateTools = $dateTools;
        $this->conversionTools = $conversionTools;
        $this->generationTools = $generationTools;
        $this->utilityTools = $utilityTools;
    }

    // Delegate methods to specialized services

    /**
     * Get gold saver tool data
     */
    public function getGoldSaverData(): array
    {
        return $this->utilityTools->getGoldSaverData();
    }

    /**
     * Get gold indicator tool data
     */
    public function getGoldIndicatorData(): array
    {
        return $this->utilityTools->getGoldIndicatorData();
    }

    /**
     * Get smart pricing calculator data
     */
    public function getSmartPricingCalculatorData(): array
    {
        return $this->utilityTools->getSmartPricingCalculatorData();
    }

    /**
     * Calculate InstaPay withdrawal amount
     */
    public function calculateInstapayWithdrawal(float $amount): float
    {
        return $this->calculationTools->calculateInstapayWithdrawal($amount);
    }

    /**
     * Calculate the amount that must be paid to receive a specific balance EGP
     */
    public function calculateAmountToPayForBalance(float $desiredBalance): float
    {
        return $this->calculationTools->calculateAmountToPayForBalance($desiredBalance);
    }

    /**
     * Calculate USD payout amount
     */
    public function calculateUsdPayout(float $usdAmount): float
    {
        return $this->calculationTools->calculateUsdPayout($usdAmount);
    }

    /**
     * Get API key data for the authenticated user
     */
    public function getApiKeyData(): array
    {
        return $this->utilityTools->getApiKeyData();
    }

    /**
     * Generate a new API key for the authenticated user
     */
    public function generateApiKey(): string
    {
        return $this->generationTools->generateApiKey();
    }

    /**
     * Get ads/forex guide data
     */
    public function getAdsData(): array
    {
        return $this->utilityTools->getAdsData();
    }

    // ==============================================
    // TEXT PROCESSING TOOLS
    // ==============================================

    public function countWords(string $text): array
    {
        return $this->textTools->countWords($text);
    }

    public function reverseString(string $text): string
    {
        return $this->textTools->reverseString($text);
    }

    public function checkPalindrome(string $text, bool $preserveCase = false, bool $includeSpaces = false): array
    {
        return $this->textTools->checkPalindrome($text, $preserveCase, $includeSpaces);
    }

    public function countVowels(string $text): array
    {
        return $this->textTools->countVowels($text);
    }

    public function caesarCipher(string $text, int $shift): string
    {
        return $this->textTools->caesarCipher($text, $shift);
    }

    public function characterCounter(string $text): array
    {
        return $this->textTools->characterCounter($text);
    }

    public function caseConverter(string $text, string $mode): array
    {
        return $this->textTools->caseConverter($text, $mode);
    }

    public function removeDuplicateLines(string $text, bool $keepOrder = true): array
    {
        return $this->textTools->removeDuplicateLines($text, $keepOrder);
    }

    public function sortLines(string $text, string $direction = 'asc', bool $caseSensitive = false): array
    {
        return $this->textTools->sortLines($text, $direction, $caseSensitive);
    }

    public function textCleaner(string $text, array $options): array
    {
        return $this->textTools->textCleaner($text, $options);
    }

    public function textDiff(string $textA, string $textB): array
    {
        return $this->textTools->textDiff($textA, $textB);
    }

    public function findAndReplace(string $text, string $find, string $replace, bool $isRegex = false, bool $caseSensitive = false): array
    {
        return $this->textTools->findAndReplace($text, $find, $replace, $isRegex, $caseSensitive);
    }

    public function base64Encode(string $text): string
    {
        return $this->textTools->base64Encode($text);
    }

    public function base64Decode(string $text): array
    {
        return $this->textTools->base64Decode($text);
    }

    public function summarizeText(string $text, int $sentences): array
    {
        return $this->textTools->summarizeText($text, $sentences);
    }

    public function checkKeywordDensity(string $text): array
    {
        return $this->textTools->checkKeywordDensity($text);
    }

    public function generateLoremIpsum(int $count, string $type): array
    {
        return $this->textTools->generateLoremIpsum($count, $type);
    }

    public function formatText(string $text, string $format): array
    {
        return $this->textTools->formatText($text, $format);
    }

    public function testRegex(string $pattern, string $text, string $flags): array
    {
        return $this->textTools->testRegex($pattern, $text, $flags);
    }

    public function checkSpellingGrammar(string $text): array
    {
        return $this->textTools->checkSpellingGrammar($text);
    }

    // ==============================================
    // CALCULATION TOOLS
    // ==============================================

    public function convertUnits(float $value, string $fromUnit, string $toUnit): array
    {
        return $this->calculationTools->convertUnits($value, $fromUnit, $toUnit);
    }

    public function calculateFactorial(int $number): array
    {
        return $this->calculationTools->calculateFactorial($number);
    }

    public function checkPrimeNumber(int $number): array
    {
        return $this->calculationTools->checkPrimeNumber($number);
    }

    public function calculateLoan(float $loanAmount, float $interestRate, int $loanTerm): array
    {
        return $this->calculationTools->calculateLoan($loanAmount, $interestRate, $loanTerm);
    }

    public function calculateMortgage(float $homePrice, float $downPayment, float $interestRate, int $loanTerm): array
    {
        return $this->calculationTools->calculateMortgage($homePrice, $downPayment, $interestRate, $loanTerm);
    }

    public function calculateSimpleInterest(float $principal, float $rate, float $time): array
    {
        return $this->calculationTools->calculateSimpleInterest($principal, $rate, $time);
    }

    public function calculateCompoundInterest(float $principal, float $rate, int $compoundFreq, float $time): array
    {
        return $this->calculationTools->calculateCompoundInterest($principal, $rate, $compoundFreq, $time);
    }

    public function calculateBMI(float $height, float $weight, string $unit = 'metric'): array
    {
        return $this->calculationTools->calculateBMI($height, $weight, $unit);
    }

    public function calculateBMR(float $height, float $weight, int $age, string $gender): array
    {
        return $this->calculationTools->calculateBMR($height, $weight, $age, $gender);
    }

    public function calculateCalories(float $height, float $weight, int $age, string $gender, string $activity): array
    {
        return $this->calculationTools->calculateCalories($height, $weight, $age, $gender, $activity);
    }

    public function calculateBodyFat(float $height, float $weight, int $age, string $gender): array
    {
        return $this->calculationTools->calculateBodyFat($height, $weight, $age, $gender);
    }

    public function calculateHeartRate(int $age, int $restingHR): array
    {
        return $this->calculationTools->calculateHeartRate($age, $restingHR);
    }

    public function calculateWaterIntake(float $weight, string $activity, string $climate): array
    {
        return $this->calculationTools->calculateWaterIntake($weight, $activity, $climate);
    }

    public function calculatePercentage(float $value, float $percentage, string $operation): array
    {
        return $this->calculationTools->calculatePercentage($value, $percentage, $operation);
    }

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2, string $unit): array
    {
        return $this->calculationTools->calculateDistance($lat1, $lon1, $lat2, $lon2, $unit);
    }

    public function scientificCalculation(string $expression): array
    {
        return $this->calculationTools->scientificCalculation($expression);
    }

    // ==============================================
    // DATE & TIME TOOLS
    // ==============================================

    public function calculateAge(string $birthdate): array
    {
        return $this->dateTools->calculateAge($birthdate);
    }

    public function calculateDateDifference(string $startDate, string $endDate): array
    {
        return $this->dateTools->calculateDateDifference($startDate, $endDate);
    }

    public function computeTimeDifference(string $startTime, string $endTime, ?string $timezone = 'UTC'): array
    {
        return $this->dateTools->computeTimeDifference($startTime, $endTime, $timezone);
    }

    public function addOrSubtractDate(
        string $date,
        int $years,
        int $months,
        int $days,
        int $hours,
        int $minutes,
        string $operation = 'add',
        ?string $timezone = 'UTC'
    ): array {
        return $this->dateTools->addOrSubtractDate($date, $years, $months, $days, $hours, $minutes, $operation, $timezone);
    }

    public function calculateWorkingDays(
        string $startDate,
        string $endDate,
        array $holidays = [],
        array $workingWeekdays = [1,2,3,4,5],
        bool $includeEnd = true
    ): array {
        return $this->dateTools->calculateWorkingDays($startDate, $endDate, $holidays, $workingWeekdays, $includeEnd);
    }

    public function findWeekday(string $date): array
    {
        return $this->dateTools->findWeekday($date);
    }

    public function findWeekNumber(string $date): array
    {
        return $this->dateTools->findWeekNumber($date);
    }

    public function isLeapYear(int $year): array
    {
        return $this->dateTools->isLeapYear($year);
    }

    public function countdownTo(string $targetDateTime, string $timezone = 'UTC'): array
    {
        return $this->dateTools->countdownTo($targetDateTime, $timezone);
    }

    public function worldClock(array $timezones): array
    {
        return $this->dateTools->worldClock($timezones);
    }

    public function convertDateFormat(string $input, string $fromFormat, string $toFormat, string $timezone = 'UTC'): array
    {
        return $this->dateTools->convertDateFormat($input, $fromFormat, $toFormat, $timezone);
    }

    public function lookupTimezone(string $query): array
    {
        return $this->dateTools->lookupTimezone($query);
    }

    public function sunriseSunset(float $lat, float $lng, string $date, string $timezone = 'UTC'): array
    {
        return $this->dateTools->sunriseSunset($lat, $lng, $date, $timezone);
    }

    public function nextBirthday(string $birthdate, string $timezone = 'UTC'): array
    {
        return $this->dateTools->nextBirthday($birthdate, $timezone);
    }

    public function generateDateRange(string $startDate, string $endDate, string $step = 'P1D'): array
    {
        return $this->dateTools->generateDateRange($startDate, $endDate, $step);
    }

    public function generateCalendar(int $year, int $month): array
    {
        return $this->dateTools->generateCalendar($year, $month);
    }

    public function dstTransitions(string $timezone, int $year): array
    {
        return $this->dateTools->dstTransitions($timezone, $year);
    }

    public function gregorianToJd(int $year, int $month, int $day): int
    {
        return $this->dateTools->gregorianToJd($year, $month, $day);
    }

    public function jdToGregorian(int $jd): array
    {
        return $this->dateTools->jdToGregorian($jd);
    }

    public function convertJulian(string $mode, array $input): array
    {
        return $this->dateTools->convertJulian($mode, $input);
    }

    public function convertOrdinalDate(string $input, string $mode = 'to_calendar'): array
    {
        return $this->dateTools->convertOrdinalDate($input, $mode);
    }

    public function calculateDuration(string $base, string $duration, string $operation = 'add', string $timezone = 'UTC'): array
    {
        return $this->dateTools->calculateDuration($base, $duration, $operation, $timezone);
    }

    // ==============================================
    // CONVERSION TOOLS
    // ==============================================

    public function convertJsonToCsv(string $json): array
    {
        return $this->conversionTools->convertJsonToCsv($json);
    }

    public function convertCsvToJson(string $csv): array
    {
        return $this->conversionTools->convertCsvToJson($csv);
    }

    public function formatValidateJson(string $json): array
    {
        return $this->conversionTools->formatValidateJson($json);
    }

    public function simplifyJson(string $json, string $outputStyle = 'original'): array
    {
        return $this->conversionTools->simplifyJson($json, $outputStyle);
    }

    public function minifyHtml(string $html): array
    {
        return $this->conversionTools->minifyHtml($html);
    }

    public function minifyCssJs(string $code, string $type): array
    {
        return $this->conversionTools->minifyCssJs($code, $type);
    }

    public function convertMarkdownToHtml(string $markdown): array
    {
        return $this->conversionTools->convertMarkdownToHtml($markdown);
    }

    public function encodeDecodeHtmlEntities(string $text, string $operation): array
    {
        return $this->conversionTools->encodeDecodeHtmlEntities($text, $operation);
    }

    public function decodeJsonString(string $text): array
    {
        return $this->conversionTools->decodeJsonString($text);
    }

    public function convertXmlToJson(string $xml): array
    {
        return $this->conversionTools->convertXmlToJson($xml);
    }

    public function encryptDecryptText(string $text, string $operation, string $method, string $key): array
    {
        return $this->conversionTools->encryptDecryptText($text, $operation, $method, $key);
    }

    public function generateFileHash(string $text, string $algorithm): array
    {
        return $this->conversionTools->generateFileHash($text, $algorithm);
    }

    public function generateJWT(string $payload, string $secret): array
    {
        return $this->conversionTools->generateJWT($payload, $secret);
    }

    public function decodeJWT(string $token, string $secret): array
    {
        return $this->conversionTools->decodeJWT($token, $secret);
    }

    public function convertGpsCoordinates(string $coordinates, string $fromFormat, string $toFormat): array
    {
        return $this->conversionTools->convertGpsCoordinates($coordinates, $fromFormat, $toFormat);
    }

    public function convertTimezone(string $datetime, string $fromTimezone, string $toTimezone): array
    {
        return $this->conversionTools->convertTimezone($datetime, $fromTimezone, $toTimezone);
    }

    // ==============================================
    // GENERATION TOOLS
    // ==============================================

    public function generatePassword(int $length, bool $uppercase = true, bool $lowercase = true, bool $numbers = true, bool $special = false): array
    {
        return $this->generationTools->generatePassword($length, $uppercase, $lowercase, $numbers, $special);
    }

    public function checkPasswordStrength(string $password): array
    {
        return $this->generationTools->checkPasswordStrength($password);
    }

    public function checkPasswordBreach(string $password): array
    {
        return $this->generationTools->checkPasswordBreach($password);
    }

    public function generateRandomNumbers(int $min, int $max, int $count): array
    {
        return $this->generationTools->generateRandomNumbers($min, $max, $count);
    }

    public function generateAiText(string $prompt, string $type = 'creative', int $length = 100): array
    {
        return $this->generationTools->generateAiText($prompt, $type, $length);
    }

    public function generateChart(array $data, string $type = 'line', array $options = []): array
    {
        return $this->generationTools->generateChart($data, $type, $options);
    }

    public function generateWifiQr(string $ssid, string $password, string $encryption = 'WPA', bool $hidden = false): array
    {
        return $this->generationTools->generateWifiQr($ssid, $password, $encryption, $hidden);
    }

    public function generateSitemap(string $url, int $maxPages = 100): array
    {
        return $this->generationTools->generateSitemap($url, $maxPages);
    }

    // ==============================================
    // UTILITY TOOLS
    // ==============================================

    public function prioritizeTasks(array $tasks, string $criteria): array
    {
        return $this->utilityTools->prioritizeTasks($tasks, $criteria);
    }

    public function generateIcons($uploadedFile, array $sizes, string $format, int $quality, bool $generateFavicon, bool $generateAppIcons): array
    {
        return $this->utilityTools->generateIcons($uploadedFile, $sizes, $format, $quality, $generateFavicon, $generateAppIcons);
    }

    public function getUrlShortenerServices(): array
    {
        return $this->utilityTools->getUrlShortenerServices();
    }

    public function shortenUrl(string $url, array $selectedServices, ?string $customAlias = null, array $apiKeys = []): array
    {
        return $this->utilityTools->shortenUrl($url, $selectedServices, $customAlias, $apiKeys);
    }

    public function colorPicker(string $color, string $fromFormat, string $toFormat): array
    {
        return $this->utilityTools->colorPicker($color, $fromFormat, $toFormat);
    }

    public function resizeImage($imageFile, int $width, int $height, string $format = 'jpeg', int $quality = 90): array
    {
        return $this->utilityTools->resizeImage($imageFile, $width, $height, $format, $quality);
    }

    public function mergePdfs(array $pdfFiles, string $outputName = null): array
    {
        return $this->utilityTools->mergePdfs($pdfFiles, $outputName);
    }

    public function generateBarcode(string $text, string $type = 'C128', int $width = 200, int $height = 100, string $color = 'black'): array
    {
        return $this->utilityTools->generateBarcode($text, $type, $width, $height, $color);
    }

    public function translateText(string $text, string $fromLang, string $toLang): array
    {
        return $this->utilityTools->translateText($text, $fromLang, $toLang);
    }

    public function convertExcel($file, string $toFormat): array
    {
        return $this->utilityTools->convertExcel($file, $toFormat);
    }

    public function convertVideo($file, string $toFormat, array $options = []): array
    {
        return $this->utilityTools->convertVideo($file, $toFormat, $options);
    }

    public function convertAudio($file, string $toFormat, array $options = []): array
    {
        return $this->utilityTools->convertAudio($file, $toFormat, $options);
    }

    public function generateFamilyTree(string $familyData, string $treeType = 'vertical', bool $showPhotos = false, bool $showDates = false): array
    {
        return $this->utilityTools->generateFamilyTree($familyData, $treeType, $showPhotos, $showDates);
    }
}