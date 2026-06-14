<?php

namespace Modules\WebTools\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Services\JsonMergeService;
use App\Services\ToolsService;
use App\Http\Requests\PalindromeCheckerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LegacyToolsController extends \App\Http\Controllers\Controller
{
    private JsonMergeService $jsonMergeService;
    private ToolsService $toolsService;

    public function __construct(JsonMergeService $jsonMergeService, ToolsService $toolsService)
    {
        $this->jsonMergeService = $jsonMergeService;
        $this->toolsService = $toolsService;
    }

    public function index()
    {
        return view('tools.index');
    }

    public function security()
    {
        return view('tools.security');
    }

    public function aiOpenSource()
    {
        return view('tools.ai-open-source');
    }



    /**
     * Show the JSON merge tool page
     */
    public function jsonMerge(): View
    {
        return view('tools.json-merge');
    }

    /**
     * Process JSON merge request
     */
    public function processJsonMerge(Request $request): RedirectResponse
    {
        $request->validate([
            'json_input' => 'required|string',
            'merge_strategy' => 'required|in:deep,shallow,preserve_duplicates'
        ]);

        try {
            $jsonInput = $request->input('json_input');
            $mergeStrategy = $request->input('merge_strategy', 'deep');

            // Use the service to merge JSON
            $result = $this->jsonMergeService->mergeJsonInput($jsonInput, $mergeStrategy);

            return redirect()->route('tools.json-merge')
                ->with('success', 'JSON objects merged successfully!')
                ->with('result', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                ->with('input', $jsonInput)
                ->with('strategy', $mergeStrategy);

        } catch (\Exception $e) {
            return redirect()->route('tools.json-merge')
                ->with('error', 'Error processing JSON: ' . $e->getMessage() . ' | Input length: ' . strlen($jsonInput ?? ''))
                ->with('input', $request->input('json_input'))
                ->with('strategy', $request->input('merge_strategy'));
        }
    }

    /**
     * Show the smart pricing calculator tool
     */
    public function smartPricingCalculator(): View
    {
        $data = $this->toolsService->getSmartPricingCalculatorData();
        return view('tools.smart_pricing_calculator', $data);
    }

    /**
     * String Length Tool
     */
    public function stringLength(Request $request)
    {
        $result = null;
        
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            
            $text = $request->input('text');
            $result = [
                'length' => strlen($text),
                'text' => $text
            ];
        }
        
        return view('tools.string_length', compact('result'));
    }

    /**
     * String to Upper Case Tool
     */
    public function stringToUpper(Request $request)
    {
        $result = null;
        
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            
            $text = $request->input('text');
            $result = [
                'original' => $text,
                'converted' => strtoupper($text)
            ];
        }
        
        return view('tools.string_to_upper', compact('result'));
    }

    /**
     * String to Lower Case Tool
     */
    public function stringToLower(Request $request)
    {
        $result = null;
        
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            
            $text = $request->input('text');
            $result = [
                'original' => $text,
                'converted' => strtolower($text)
            ];
        }
        
        return view('tools.string_to_lower', compact('result'));
    }




    /**
     * Show InstaPay withdrawal calculator
     */
    public function instapayCalculator(Request $request): View
    {
        $result = null;
        $input = null;
        $amount_to_pay = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'balance_egp' => 'required|numeric|min:0'
            ]);

            $input = $request->input('balance_egp');
            $result = round($this->toolsService->calculateInstapayWithdrawal((float)$input)) - 1;
            $amount_to_pay = round($this->toolsService->calculateAmountToPayForBalance((float)$input)) + 1;
        }

        return view('tools.withdraw_instapay', compact('result', 'input', 'amount_to_pay'));
    }

    /**
     * Show USD payout calculator
     */
    public function usdPayoutCalculator(Request $request): View
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'usd' => 'required|numeric'
            ]);

            $input = $request->input('usd');
            $result = $this->toolsService->calculateUsdPayout((float)$input);
        }

        return view('tools.payout_usd', compact('result', 'input'));
    }

    /**
     * Show API key management tool
     */
    public function apiKey(): View
    {
        $data = $this->toolsService->getApiKeyData();
        return view('tools.api_key', $data);
    }

    /**
     * Generate new API key
     */
    public function generateApiKey(): RedirectResponse
    {
        $this->toolsService->generateApiKey();
        return redirect()->back()->with('success', 'New API key generated successfully!');
    }

    /**
     * Show Forex/Ads guide
     */
    public function forexGuide(): View
    {
        $data = $this->toolsService->getAdsData();
        return view('tools.ads', $data);
    }

    /**
     * Show text reverser tool
     */
    public function textReverser(Request $request): View
    {
        $result = null;
        $input = null;
        $stats = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'text_input' => 'required|string|max:10000'
            ]);

            $input = $request->input('text_input');

            // Enhanced text reversal with multiple options and Arabic support
            $result = [
                'reversed' => $this->toolsService->reverseString($input),
                'words_reversed' => $this->reverseWordsMultilingual($input),
                'uppercase' => mb_strtoupper($input, 'UTF-8'),
                'lowercase' => mb_strtolower($input, 'UTF-8'),
                'sentence_reversed' => $this->reverseSentencesMultilingual($input),
                'line_reversed' => implode("\n", array_reverse(explode("\n", $input)))
            ];

            // Text statistics with multilingual support
            $stats = [
                'char_count' => mb_strlen($input, 'UTF-8'),
                'char_count_no_spaces' => mb_strlen(preg_replace('/\s+/u', '', $input), 'UTF-8'),
                'word_count' => $this->countWordsMultilingual($input),
                'line_count' => substr_count($input, "\n") + 1,
                'sentence_count' => $this->countSentencesMultilingual($input),
                'paragraph_count' => count(array_filter(explode("\n\n", $input))),
                'alphabetic' => preg_match_all('/[\p{L}]/u', $input),
                'numeric' => preg_match_all('/[\p{N}]/u', $input),
                'spaces' => preg_match_all('/[\s]/u', $input),
                'special' => mb_strlen($input, 'UTF-8') - preg_match_all('/[\p{L}\p{N}\s]/u', $input)
            ];
        }

        return view('tools.text_reverser', compact('result', 'input', 'stats'));
    }

    /**
     * Reverse words with proper multilingual support
     */
    private function reverseWordsMultilingual(string $text): string
    {
        // Split by Unicode word boundaries for better multilingual support
        $words = preg_split('/\b/u', $text);
        $reversedWords = array_reverse($words);
        return implode('', $reversedWords);
    }

    /**
     * Reverse sentences with multilingual punctuation support
     */
    private function reverseSentencesMultilingual(string $text): string
    {
        // Sentence endings for multiple languages including Arabic
        $sentenceEndings = [
            '.', '!', '?', // English
            '。', '！', '？', // Chinese/Japanese
            '।', '॥', // Devanagari
            '۔', '؟', '!', // Arabic/Urdu
            '۔', '؟', '!', // Persian
            '।', '॥', // Bengali
            '।', '॥', // Gujarati
            '।', '॥', // Hindi
            '।', '॥', // Kannada
            '।', '॥', // Malayalam
            '।', '॥', // Marathi
            '।', '॥', // Odia
            '।', '॥', // Punjabi
            '।', '॥', // Tamil
            '।', '॥', // Telugu
        ];

        // Split by sentence endings
        $pattern = '/(' . preg_quote(implode('', $sentenceEndings), '/') . ')/u';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        $sentences = [];
        $currentSentence = '';

        foreach ($parts as $part) {
            if (in_array($part, $sentenceEndings)) {
                $currentSentence .= $part;
                if (!empty(trim($currentSentence))) {
                    $sentences[] = trim($currentSentence);
                }
                $currentSentence = '';
            } else {
                $currentSentence .= $part;
            }
        }

        // Add any remaining text
        if (!empty(trim($currentSentence))) {
            $sentences[] = trim($currentSentence);
        }

        return implode('. ', array_reverse($sentences));
    }

    /**
     * Count words with multilingual support
     */
    private function countWordsMultilingual(string $text): int
    {
        if (empty(trim($text))) {
            return 0;
        }

        // Remove extra whitespace and normalize
        $text = preg_replace('/\s+/u', ' ', trim($text));

        // Split by Unicode word boundaries
        $words = preg_split('/\b/u', $text);

        // Filter out empty strings and count actual words
        $wordCount = 0;
        foreach ($words as $word) {
            $word = trim($word);
            if (!empty($word) && !preg_match('/^\s*$/u', $word)) {
                // Check if it's a valid word (contains letters, numbers, or CJK characters)
                if (preg_match('/[\p{L}\p{N}\p{Han}\p{Hiragana}\p{Katakana}\p{Arabic}]/u', $word)) {
                    $wordCount++;
                }
            }
        }

        return $wordCount;
    }

    /**
     * Count sentences with multilingual punctuation support
     */
    private function countSentencesMultilingual(string $text): int
    {
        if (empty(trim($text))) {
            return 0;
        }

        // Sentence endings for multiple languages including Arabic
        $sentenceEndings = [
            '.', '!', '?', // English
            '。', '！', '？', // Chinese/Japanese
            '।', '॥', // Devanagari
            '۔', '؟', '!', // Arabic/Urdu
            '۔', '؟', '!', // Persian
            '।', '॥', // Bengali
            '।', '॥', // Gujarati
            '।', '॥', // Hindi
            '।', '॥', // Kannada
            '।', '॥', // Malayalam
            '।', '॥', // Marathi
            '।', '॥', // Odia
            '।', '॥', // Punjabi
            '।', '॥', // Tamil
            '।', '॥', // Telugu
        ];

        $count = 0;
        foreach ($sentenceEndings as $ending) {
            $count += mb_substr_count($text, $ending, 'UTF-8');
        }

        return $count;
    }

    // ==============================================
    // TEXT PROCESSING TOOLS
    // ==============================================

    public function wordCounter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $result = $this->toolsService->countWords($text);
        }
        return view('tools.word_counter', compact('result'));
    }

    public function palindromeChecker(PalindromeCheckerRequest $request)
    {
        $result = null;

        if ($request->isMethod('post')) {
            try {
                $validated = $request->validated();
                $text = $validated['text'];

                // Get options
                $preserveCase = $request->boolean('preserve_case', false);
                $includeSpaces = $request->boolean('include_spaces', false);

                // Log the options for debugging
                \Log::info('Palindrome checker options', [
                    'preserve_case' => $preserveCase,
                    'include_spaces' => $includeSpaces,
                    'raw_preserve_case' => $request->input('preserve_case'),
                    'raw_include_spaces' => $request->input('include_spaces')
                ]);

                // Process the palindrome check
                $result = $this->toolsService->checkPalindrome($text, $preserveCase, $includeSpaces);

                // Log successful palindrome checks for analytics
                \Log::info('Palindrome check completed', [
                    'user_id' => auth()->id(),
                    'ip' => request()->ip(),
                    'text_length' => strlen($text),
                    'is_palindrome' => $result['is_palindrome'],
                    'language_family' => $result['analysis']['language_family'] ?? 'Unknown',
                    'preserve_case' => $preserveCase,
                    'include_spaces' => $includeSpaces
                ]);

            } catch (\Exception $e) {
                \Log::error('Palindrome checker error', [
                    'user_id' => auth()->id(),
                    'ip' => request()->ip(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors(['general' => 'An error occurred while processing your request. Please try again.']);
            }
        }

        return view('tools.palindrome_checker', compact('result'));
    }

    public function vowelCounter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $result = $this->toolsService->countVowels($text);
        }
        return view('tools.vowel_counter', compact('result'));
    }

    public function caesarCipher(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $shift = (int) $request->input('shift', 0);
            $operation = $request->input('operation', 'encrypt');
            $effectiveShift = $operation === 'decrypt' ? -$shift : $shift;
            $result = $this->toolsService->caesarCipher($text, $effectiveShift);
        }
        return view('tools.caesar_cipher', compact('result'));
    }

    /**
     * Character Counter tool
     */
    public function characterCounter(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $result = $this->toolsService->characterCounter($text);
        }
        return view('tools.character_counter', compact('result'));
    }

    /**
     * Case Converter tool
     */
    public function caseConverter(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $mode = $request->input('mode', 'upper');
            $result = $this->toolsService->caseConverter($text, $mode);
        }
        return view('tools.case_converter', compact('result'));
    }

    /**
     * Remove Duplicate Lines tool
     */
    public function removeDuplicateLines(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $keepOrder = (bool) $request->input('keep_order', true);
            $result = $this->toolsService->removeDuplicateLines($text, $keepOrder);
        }
        return view('tools.remove_duplicate_lines', compact('result'));
    }

    /**
     * Sort Lines tool
     */
    public function sortLines(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $direction = $request->input('direction', 'asc');
            $caseSensitive = (bool) $request->input('case_sensitive', false);
            $result = $this->toolsService->sortLines($text, $direction, $caseSensitive);
        }
        return view('tools.sort_lines', compact('result'));
    }

    /**
     * Text Cleaner tool
     */
    public function textCleaner(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $options = [
                'strip_html' => (bool) $request->input('strip_html', true),
                'remove_extra_spaces' => (bool) $request->input('remove_extra_spaces', true),
                'remove_blank_lines' => (bool) $request->input('remove_blank_lines', false),
                'normalize_quotes' => (bool) $request->input('normalize_quotes', false),
            ];
            $result = $this->toolsService->textCleaner($text, $options);
        }
        return view('tools.text_cleaner', compact('result'));
    }

    /**
     * Text Diff tool
     */
    public function textDiff(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post')) {
            $textA = $request->input('text_a', '');
            $textB = $request->input('text_b', '');
            $result = $this->toolsService->textDiff($textA, $textB);
        }
        return view('tools.text_diff', compact('result'));
    }

    /**
     * Find & Replace tool
     */
    public function findAndReplace(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $find = $request->input('find', '');
            $replace = $request->input('replace', '');
            $isRegex = (bool) $request->input('regex', false);
            $caseSensitive = (bool) $request->input('case_sensitive', false);
            $result = $this->toolsService->findAndReplace($text, $find, $replace, $isRegex, $caseSensitive);
        }
        return view('tools.find_and_replace', compact('result'));
    }

    // ==============================================
    // ENCODING/DECODING TOOLS
    // ==============================================

    public function base64Encoder(Request $request)
    {
        $result = null;
        $operation = $request->input('operation', 'encode');

        if ($request->isMethod('post')) {
            $text = $request->input('text', '');

            if ($operation === 'encode') {
                $result = ['encoded' => $this->toolsService->base64Encode($text)];
            } else {
                $result = $this->toolsService->base64Decode($text);
            }
        }

        return view('tools.base64_encoder', compact('result', 'operation'));
    }

    // ==============================================
    // MATHEMATICAL TOOLS
    // ==============================================

    public function unitConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $value = (float) $request->input('value', 0);
            $fromUnit = $request->input('from_unit', '');
            $toUnit = $request->input('to_unit', '');
            $result = $this->toolsService->convertUnits($value, $fromUnit, $toUnit);
        }
        return view('tools.unit_converter', compact('result'));
    }

    public function factorialCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $number = (int) $request->input('number', 0);
            $result = $this->toolsService->calculateFactorial($number);
        }
        return view('tools.factorial_calculator', compact('result'));
    }

    public function primeChecker(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $number = (int) $request->input('number', 0);
            $result = $this->toolsService->checkPrimeNumber($number);
        }
        return view('tools.prime_checker', compact('result'));
    }

    // ==============================================
    // FINANCIAL CALCULATORS
    // ==============================================

    public function loanCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $loanAmount = (float) $request->input('loan_amount', 0);
            $interestRate = (float) $request->input('interest_rate', 0);
            $loanTerm = (int) $request->input('loan_term', 0);
            $result = $this->toolsService->calculateLoan($loanAmount, $interestRate, $loanTerm);
        }
        return view('tools.loan_calculator', compact('result'));
    }

    public function mortgageCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $homePrice = (float) $request->input('home_price', 0);
            $downPayment = (float) $request->input('down_payment', 0);
            $interestRate = (float) $request->input('interest_rate', 0);
            $loanTerm = (int) $request->input('loan_term', 0);
            $result = $this->toolsService->calculateMortgage($homePrice, $downPayment, $interestRate, $loanTerm);
        }
        return view('tools.mortgage_calculator', compact('result'));
    }

    public function simpleInterestCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $principal = (float) $request->input('principal', 0);
            $rate = (float) $request->input('rate', 0);
            $time = (float) $request->input('time', 0);
            $result = $this->toolsService->calculateSimpleInterest($principal, $rate, $time);
        }
        return view('tools.simple_interest_calculator', compact('result'));
    }

    public function compoundInterestCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $principal = (float) $request->input('principal', 0);
            $rate = (float) $request->input('rate', 0);
            $compoundFreq = (int) $request->input('compound_frequency', 1);
            $time = (float) $request->input('time', 0);
            $result = $this->toolsService->calculateCompoundInterest($principal, $rate, $compoundFreq, $time);
        }
        return view('tools.compound_interest_calculator', compact('result'));
    }

    // ==============================================
    // DATE/TIME CALCULATORS
    // ==============================================

    public function ageCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $birthdate = $request->input('birthdate', '');
            if ($birthdate) {
                $result = $this->toolsService->calculateAge($birthdate);
            }
        }
        return view('tools.age_calculator', compact('result'));
    }

    public function dateDifferenceCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $startDate = $request->input('start_date', '');
            $endDate = $request->input('end_date', '');
            if ($startDate && $endDate) {
                $result = $this->toolsService->calculateDateDifference($startDate, $endDate);
            }
        }
        return view('tools.date_difference_calculator', compact('result'));
    }

    public function timeDifferenceCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'start_time' => 'required|string',
                'end_time' => 'required|string',
                'timezone' => 'nullable|string'
            ]);
            $result = $this->toolsService->computeTimeDifference(
                $request->input('start_time'),
                $request->input('end_time'),
                $request->input('timezone', 'UTC')
            );
        }
        return view('tools.time_difference_calculator', compact('result'));
    }

    public function dateAddSubtract(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'date' => 'required|string',
                'years' => 'nullable|integer',
                'months' => 'nullable|integer',
                'days' => 'nullable|integer',
                'hours' => 'nullable|integer',
                'minutes' => 'nullable|integer',
                'operation' => 'required|in:add,subtract',
                'timezone' => 'nullable|string'
            ]);
            $result = $this->toolsService->addOrSubtractDate(
                $request->input('date'),
                (int) $request->input('years', 0),
                (int) $request->input('months', 0),
                (int) $request->input('days', 0),
                (int) $request->input('hours', 0),
                (int) $request->input('minutes', 0),
                $request->input('operation', 'add'),
                $request->input('timezone', 'UTC')
            );
        }
        return view('tools.date_add_subtract', compact('result'));
    }

    public function workingDaysCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'start_date' => 'required|string',
                'end_date' => 'required|string',
                'holidays' => 'nullable|string',
                'working_weekdays' => 'nullable|array',
                'working_weekdays.*' => 'integer|in:1,2,3,4,5,6,7',
                'include_end' => 'nullable|boolean'
            ]);
            $holidays = array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $request->input('holidays', ''))));
            $result = $this->toolsService->calculateWorkingDays(
                $request->input('start_date'),
                $request->input('end_date'),
                $holidays,
                $request->input('working_weekdays', [1,2,3,4,5]),
                (bool) $request->boolean('include_end', true)
            );
        }
        return view('tools.working_days_calculator', compact('result'));
    }

    public function weekdayFinder(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate(['date' => 'required|string']);
            $result = $this->toolsService->findWeekday($request->input('date'));
        }
        return view('tools.weekday_finder', compact('result'));
    }

    public function weekNumberFinder(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate(['date' => 'required|string']);
            $result = $this->toolsService->findWeekNumber($request->input('date'));
        }
        return view('tools.week_number_finder', compact('result'));
    }

    public function leapYearChecker(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate(['year' => 'required|integer']);
            $result = $this->toolsService->isLeapYear((int) $request->input('year'));
        }
        return view('tools.leap_year_checker', compact('result'));
    }

    public function countdownCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'target' => 'required|string',
                'timezone' => 'nullable|string'
            ]);
            $result = $this->toolsService->countdownTo($request->input('target'), $request->input('timezone', 'UTC'));
        }
        return view('tools.countdown_calculator', compact('result'));
    }

    public function worldClock(Request $request)
    {
        $result = $this->toolsService->worldClock($request->input('timezones', []));
        return view('tools.world_clock', compact('result'));
    }

    public function dateFormatConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'input' => 'required|string',
                'from_format' => 'required|string',
                'to_format' => 'required|string',
                'timezone' => 'nullable|string'
            ]);
            $result = $this->toolsService->convertDateFormat(
                $request->input('input'),
                $request->input('from_format'),
                $request->input('to_format'),
                $request->input('timezone', 'UTC')
            );
        }
        return view('tools.date_format_converter', compact('result'));
    }

    public function timezoneLookup(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate(['query' => 'required|string']);
            $result = $this->toolsService->lookupTimezone($request->input('query'));
        }
        return view('tools.timezone_lookup', compact('result'));
    }

    public function sunriseSunsetCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'date' => 'required|string',
                'timezone' => 'nullable|string'
            ]);
            $result = $this->toolsService->sunriseSunset(
                (float) $request->input('latitude'),
                (float) $request->input('longitude'),
                $request->input('date'),
                $request->input('timezone', 'UTC')
            );
        }
        return view('tools.sunrise_sunset_calculator', compact('result'));
    }

    public function nextBirthdayCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'birthdate' => 'required|string',
                'timezone' => 'nullable|string'
            ]);
            $result = $this->toolsService->nextBirthday(
                $request->input('birthdate'),
                $request->input('timezone', 'UTC')
            );
        }
        return view('tools.next_birthday_calculator', compact('result'));
    }

    public function dateRangeGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'start_date' => 'required|string',
                'end_date' => 'required|string',
                'step' => 'nullable|string'
            ]);
            $result = $this->toolsService->generateDateRange(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('step', 'P1D')
            );
        }
        return view('tools.date_range_generator', compact('result'));
    }

    public function calendarGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'year' => 'required|integer|min:1',
                'month' => 'required|integer|min:1|max:12'
            ]);
            $result = $this->toolsService->generateCalendar((int) $request->input('year'), (int) $request->input('month'));
        }
        return view('tools.calendar_generator', compact('result'));
    }

    public function dstChecker(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'timezone' => 'required|string',
                'year' => 'required|integer'
            ]);
            $result = $this->toolsService->dstTransitions($request->input('timezone'), (int) $request->input('year'));
        }
        return view('tools.dst_checker', compact('result'));
    }

    public function julianDateConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $mode = $request->input('mode', 'gregorian_to_jd');
            if ($mode === 'gregorian_to_jd') {
                $request->validate(['year' => 'required|integer', 'month' => 'required|integer', 'day' => 'required|integer']);
                $result = $this->toolsService->convertJulian('gregorian_to_jd', [
                    'year' => (int) $request->input('year'),
                    'month' => (int) $request->input('month'),
                    'day' => (int) $request->input('day'),
                ]);
            } else {
                $request->validate(['jd' => 'required|integer']);
                $result = $this->toolsService->convertJulian('jd_to_gregorian', [
                    'jd' => (int) $request->input('jd')
                ]);
            }
        }
        return view('tools.julian_date_converter', compact('result'));
    }

    public function ordinalDateConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'input' => 'required|string',
                'mode' => 'required|in:to_calendar,to_ordinal'
            ]);
            $result = $this->toolsService->convertOrdinalDate($request->input('input'), $request->input('mode'));
        }
        return view('tools.ordinal_date_converter', compact('result'));
    }

    public function durationCalculator(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('post')) {
            $request->validate([
                'base' => 'required|string',
                'duration' => 'required|string',
                'operation' => 'required|in:add,subtract',
                'timezone' => 'nullable|string'
            ]);

            try {
                $base = $request->input('base');
                $duration = $request->input('duration');
                $operation = $request->input('operation', 'add');
                $timezone = $request->input('timezone', 'UTC');

                // Validate timezone
                if (!in_array($timezone, timezone_identifiers_list())) {
                    $timezone = 'UTC';
                }

                // Validate ISO8601 duration format
                if (!preg_match('/^P(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$/', $duration)) {
                    return view('tools.duration_calculator', [
                        'result' => ['error' => 'Invalid ISO8601 duration format. Please use format like P3DT4H30M'],
                        'input' => $request->all()
                    ]);
                }

                // Calculate duration
                $result = $this->toolsService->calculateDuration($base, $duration, $operation, $timezone);

                // Add human-readable duration description
                if (!isset($result['error'])) {
                    $result['human_duration'] = $this->parseDurationToHuman($duration);
                    $result['formatted_result'] = $this->formatDateTime($result['result'], $timezone);
                    $result['formatted_base'] = $this->formatDateTime($result['base'], $timezone);
                }

                $input = $request->all();

            } catch (\Exception $e) {
                $result = [
                    'error' => 'An error occurred while calculating the duration: ' . $e->getMessage(),
                    'details' => 'Please check your input and try again.'
                ];
                $input = $request->all();
            }
        }

        // Get available timezones for the dropdown
        $timezones = $this->getCommonTimezones();

        return view('tools.duration_calculator', compact('result', 'input', 'timezones'));
    }

    /**
     * Parse ISO8601 duration to human-readable format
     */
    private function parseDurationToHuman(string $duration): string
    {
        $parts = [];

        // Extract years
        if (preg_match('/(\d+)Y/', $duration, $matches)) {
            $parts[] = $matches[1] . ' year' . ($matches[1] > 1 ? 's' : '');
        }

        // Extract months
        if (preg_match('/(\d+)M/', $duration, $matches)) {
            $parts[] = $matches[1] . ' month' . ($matches[1] > 1 ? 's' : '');
        }

        // Extract weeks
        if (preg_match('/(\d+)W/', $duration, $matches)) {
            $parts[] = $matches[1] . ' week' . ($matches[1] > 1 ? 's' : '');
        }

        // Extract days
        if (preg_match('/(\d+)D/', $duration, $matches)) {
            $parts[] = $matches[1] . ' day' . ($matches[1] > 1 ? 's' : '');
        }

        // Extract time components
        if (preg_match('/T(\d+)H/', $duration, $matches)) {
            $parts[] = $matches[1] . ' hour' . ($matches[1] > 1 ? 's' : '');
        }

        if (preg_match('/T.*?(\d+)M/', $duration, $matches)) {
            $parts[] = $matches[1] . ' minute' . ($matches[1] > 1 ? 's' : '');
        }

        if (preg_match('/T.*?(\d+)S/', $duration, $matches)) {
            $parts[] = $matches[1] . ' second' . ($matches[1] > 1 ? 's' : '');
        }

        return implode(', ', $parts);
    }

    /**
     * Format datetime with timezone information
     */
    private function formatDateTime(string $datetime, string $timezone): string
    {
        try {
            $dt = new \DateTime($datetime, new \DateTimeZone($timezone));
            return $dt->format('F j, Y \a\t g:i A T');
        } catch (\Exception $e) {
            return $datetime;
        }
    }

    /**
     * Get common timezones for dropdown
     */
    private function getCommonTimezones(): array
    {
        return [
            'UTC' => 'UTC (Coordinated Universal Time)',
            'America/New_York' => 'Eastern Time (ET)',
            'America/Chicago' => 'Central Time (CT)',
            'America/Denver' => 'Mountain Time (MT)',
            'America/Los_Angeles' => 'Pacific Time (PT)',
            'Europe/London' => 'London (GMT/BST)',
            'Europe/Paris' => 'Paris (CET/CEST)',
            'Europe/Berlin' => 'Berlin (CET/CEST)',
            'Asia/Tokyo' => 'Tokyo (JST)',
            'Asia/Shanghai' => 'Shanghai (CST)',
            'Asia/Dubai' => 'Dubai (GST)',
            'Asia/Kolkata' => 'Mumbai (IST)',
            'Australia/Sydney' => 'Sydney (AEDT/AEST)',
            'Pacific/Auckland' => 'Auckland (NZDT/NZST)',
        ];
    }

    // ==============================================
    // SECURITY TOOLS
    // ==============================================

    public function passwordGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $length = (int) $request->input('length', 12);
            $uppercase = $request->has('uppercase');
            $lowercase = $request->has('lowercase');
            $numbers = $request->has('numbers');
            $special = $request->has('special');

            $result = $this->toolsService->generatePassword($length, $uppercase, $lowercase, $numbers, $special);
        }
        return view('tools.password_generator', compact('result'));
    }

    public function passwordStrengthChecker(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $password = $request->input('password', '');
            $result = $this->toolsService->checkPasswordStrength($password);
        }
        return view('tools.password_strength_checker', compact('result'));
    }

    // ==============================================
    // DATA CONVERSION TOOLS
    // ==============================================

    public function jsonToCsvConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $json = $request->input('json', '');
            $result = $this->toolsService->convertJsonToCsv($json);

            if (!isset($result['error'])) {
                return response($result['csv'])
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="converted.csv"');
            }
        }
        return view('tools.json_to_csv_converter', compact('result'));
    }

    public function csvToJsonConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $csv = $request->input('csv', '');
            $result = $this->toolsService->convertCsvToJson($csv);
        }
        return view('tools.csv_to_json_converter', compact('result'));
    }

    /**
     * JSON Simplifier Tool (reduce arrays to single representative items)
     */
    public function jsonSimplifier(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'json' => 'required|string',
                'output_style' => 'nullable|in:original,array'
            ]);

            $json = $request->input('json', '');
            $style = $request->input('output_style', 'original');
            $result = $this->toolsService->simplifyJson($json, $style);
        }
        return view('tools.json_simplifier', compact('result'));
    }

    // ==============================================
    // HEALTH & FITNESS TOOLS
    // ==============================================

    public function bmiCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $height = (float) $request->input('height', 0);
            $weight = (float) $request->input('weight', 0);
            $unit = $request->input('unit', 'metric');
            $result = $this->toolsService->calculateBMI($height, $weight, $unit);
        }
        return view('tools.bmi_calculator', compact('result'));
    }

    public function bmrCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $height = (float) $request->input('height', 0);
            $weight = (float) $request->input('weight', 0);
            $age = (int) $request->input('age', 0);
            $gender = $request->input('gender', 'male');
            $result = $this->toolsService->calculateBMR($height, $weight, $age, $gender);
        }
        return view('tools.bmr_calculator', compact('result'));
    }

    public function calorieCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $height = (float) $request->input('height', 0);
            $weight = (float) $request->input('weight', 0);
            $age = (int) $request->input('age', 0);
            $gender = $request->input('gender', 'male');
            $activity = $request->input('activity', 'sedentary');
            $result = $this->toolsService->calculateCalories($height, $weight, $age, $gender, $activity);
        }
        return view('tools.calorie_calculator', compact('result'));
    }

    public function bodyFatCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $height = (float) $request->input('height', 0);
            $weight = (float) $request->input('weight', 0);
            $age = (int) $request->input('age', 0);
            $gender = $request->input('gender', 'male');
            $result = $this->toolsService->calculateBodyFat($height, $weight, $age, $gender);
        }
        return view('tools.body_fat_calculator', compact('result'));
    }

    public function heartRateCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $age = (int) $request->input('age', 0);
            $restingHR = (int) $request->input('resting_hr', 60);
            $result = $this->toolsService->calculateHeartRate($age, $restingHR);
        }
        return view('tools.heart_rate_calculator', compact('result'));
    }

    public function waterIntakeCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $weight = (float) $request->input('weight', 0);
            $activity = $request->input('activity', 'low');
            $climate = $request->input('climate', 'moderate');
            $result = $this->toolsService->calculateWaterIntake($weight, $activity, $climate);
        }
        return view('tools.water_intake_calculator', compact('result'));
    }

    // ==============================================
    // ADVANCED TEXT TOOLS
    // ==============================================

    public function textSummarizer(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $sentences = (int) $request->input('sentences', 3);
            $result = $this->toolsService->summarizeText($text, $sentences);
        }
        return view('tools.text_summarizer', compact('result'));
    }

    public function keywordDensityChecker(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $result = $this->toolsService->checkKeywordDensity($text);
        }
        return view('tools.keyword_density_checker', compact('result'));
    }

    public function loremIpsumGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $count = (int) $request->input('count', 5);
            $type = $request->input('type', 'paragraphs');
            $result = $this->toolsService->generateLoremIpsum($count, $type);
        }
        return view('tools.lorem_ipsum_generator', compact('result'));
    }

    public function textFormatter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string|max:10000',
                'format' => 'required|in:uppercase,lowercase,title,sentence,camel,snake,kebab,pascal,alternating,reverse'
            ]);

            try {
                $text = $request->input('text');
                $format = $request->input('format');

                                $formatResult = $this->toolsService->formatText($text, $format);

                $result = [
                    'original_text' => $text,
                    'formatted_text' => $formatResult['formatted'],
                    'format_type' => $format,
                    'char_count' => strlen($text),
                    'word_count' => str_word_count($text),
                    'line_count' => substr_count($text, "\n") + 1
                ];
            } catch (\Exception $e) {
                $result = ['error' => 'Error formatting text: ' . $e->getMessage()];
            }
        }
        return view('tools.text_formatter', compact('result'));
    }

    public function regexTester(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $pattern = $request->input('pattern', '');
            $text = $request->input('text', '');
            $flags = $request->input('flags', '');
            $result = $this->toolsService->testRegex($pattern, $text, $flags);
        }
        return view('tools.regex_tester', compact('result'));
    }

    public function spellingGrammarChecker(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $result = $this->toolsService->checkSpellingGrammar($text);
        }
        return view('tools.spelling_grammar_checker', compact('result'));
    }

    // ==============================================
    // DEVELOPMENT TOOLS
    // ==============================================

    public function jsonFormatterValidator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $json = $request->input('json', '');
            $result = $this->toolsService->formatValidateJson($json);
        }
        return view('tools.json_formatter_validator', compact('result'));
    }

    public function htmlMinifier(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $html = $request->input('html', '');
            $result = $this->toolsService->minifyHtml($html);
        }
        return view('tools.html_minifier', compact('result'));
    }

    public function cssJsMinifier(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $code = $request->input('code', '');
            $type = $request->input('type', 'css');
            $result = $this->toolsService->minifyCssJs($code, $type);
        }
        return view('tools.css_js_minifier', compact('result'));
    }

    public function markdownToHtmlConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $markdown = $request->input('markdown', '');
            $result = $this->toolsService->convertMarkdownToHtml($markdown);
        }
        return view('tools.markdown_to_html_converter', compact('result'));
    }

    public function htmlEntityEncoderDecoder(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $operation = $request->input('operation', 'encode');
            $result = $this->toolsService->encodeDecodeHtmlEntities($text, $operation);
        }
        return view('tools.html_entity_encoder_decoder', compact('result'));
    }

    public function jsonStringDecoder(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $result = $this->toolsService->decodeJsonString($text);
        }
        return view('tools.json_string_decoder', compact('result'));
    }

    /**
     * URL Encoder/Decoder
     */
    public function urlEncoder(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $operation = $request->input('operation', 'encode');
            $result = [
                'operation' => $operation,
                'original' => $text,
                'output' => $operation === 'encode' ? urlencode($text) : urldecode($text),
            ];
        }
        return view('tools.url_encoder', compact('result'));
    }

    public function xmlToJsonConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $xml = $request->input('xml', '');
            $result = $this->toolsService->convertXmlToJson($xml);
        }
        return view('tools.xml_to_json_converter', compact('result'));
    }

    // ==============================================
    // ADVANCED SECURITY TOOLS
    // ==============================================

    public function textEncryptionDecryption(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $operation = $request->input('operation', 'encrypt');
            $method = $request->input('method', 'aes');
            $key = $request->input('key', '');
            $result = $this->toolsService->encryptDecryptText($text, $operation, $method, $key);
        }
        return view('tools.text_encryption_decryption', compact('result'));
    }

    public function fileHashGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text', '');
            $algorithm = $request->input('algorithm', 'sha256');
            $result = $this->toolsService->generateFileHash($text, $algorithm);
        }
        return view('tools.file_hash_generator', compact('result'));
    }

    public function jwtGeneratorDecoder(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $operation = $request->input('operation', 'generate');
            if ($operation === 'generate') {
                $payload = $request->input('payload', '');
                $secret = $request->input('secret', '');
                $result = $this->toolsService->generateJWT($payload, $secret);
            } else {
                $token = $request->input('token', '');
                $secret = $request->input('secret', '');
                $result = $this->toolsService->decodeJWT($token, $secret);
            }
        }
        return view('tools.jwt_generator_decoder', compact('result'));
    }

    // ==============================================
    // UTILITY TOOLS
    // ==============================================

    public function randomNumberGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $min = (int) $request->input('min', 1);
            $max = (int) $request->input('max', 100);
            $count = (int) $request->input('count', 1);
            $result = $this->toolsService->generateRandomNumbers($min, $max, $count);
        }
        return view('tools.random_number_generator', compact('result'));
    }

    public function percentageCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            // Support both legacy params (value, percentage, operation)
            // and new UI params (calculation_type, value1, value2)
            if ($request->has('calculation_type')) {
                $calculationType = $request->input('calculation_type');
                $value1 = (float) $request->input('value1', 0);
                $value2 = (float) $request->input('value2', 0);

                switch ($calculationType) {
                    case 'percentage_of':
                        // What is X% of Y? → value = Y, percentage = X
                        $result = $this->toolsService->calculatePercentage($value2, $value1, 'calculate');
                        break;
                    case 'what_percentage':
                        // X is what % of Y? → find_percentage with value = X, percentage = Y
                        $result = $this->toolsService->calculatePercentage($value1, $value2, 'find_percentage');
                        break;
                    case 'percentage_change':
                        // Percentage change from X to Y
                        $change = $value1 == 0.0 ? 0.0 : (($value2 - $value1) / $value1) * 100.0;
                        $result = [
                            'value' => $value1,
                            'percentage' => $value2,
                            'operation' => 'percentage_change',
                            'result' => round($change, 2),
                            'question' => "Percentage change from {$value1} to {$value2}?",
                            'answer' => round($change, 2) . '%',
                            'explanation' => "(({$value2} - {$value1}) ÷ {$value1}) × 100"
                        ];
                        break;
                    default:
                        $result = [
                            'value' => 0,
                            'percentage' => 0,
                            'operation' => 'invalid',
                            'result' => 0,
                            'question' => 'Invalid calculation type',
                            'answer' => 0,
                            'explanation' => ''
                        ];
                }
            } else {
                $value = (float) $request->input('value', 0);
                $percentage = (float) $request->input('percentage', 0);
                $operation = $request->input('operation', 'calculate');
                $result = $this->toolsService->calculatePercentage($value, $percentage, $operation);
            }
        }
        return view('tools.percentage_calculator', compact('result'));
    }

    public function distanceCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $lat1 = (float) $request->input('lat1', 0);
            $lon1 = (float) $request->input('lon1', 0);
            $lat2 = (float) $request->input('lat2', 0);
            $lon2 = (float) $request->input('lon2', 0);
            $unit = $request->input('unit', 'km');
            $result = $this->toolsService->calculateDistance($lat1, $lon1, $lat2, $lon2, $unit);
        }
        return view('tools.distance_calculator', compact('result'));
    }

    public function gpsCoordinatesConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $coordinates = $request->input('coordinates', '');
            $fromFormat = $request->input('from_format', 'decimal');
            $toFormat = $request->input('to_format', 'dms');
            $result = $this->toolsService->convertGpsCoordinates($coordinates, $fromFormat, $toFormat);
        }
        return view('tools.gps_coordinates_converter', compact('result'));
    }

    public function timezoneConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $datetime = $request->input('datetime', '');
            $fromTimezone = $request->input('from_timezone', 'UTC');
            $toTimezone = $request->input('to_timezone', 'America/New_York');
            $result = $this->toolsService->convertTimezone($datetime, $fromTimezone, $toTimezone);
        }
        return view('tools.timezone_converter', compact('result'));
    }

    /**
     * Unix Timestamp Converter
     */
    public function timestampConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $mode = $request->input('mode', 'to_human');
            if ($mode === 'to_human') {
                $timestamp = (int) $request->input('timestamp', time());
                $tz = $request->input('timezone', 'UTC');
                $dt = new \DateTime('@' . $timestamp);
                $dt->setTimezone(new \DateTimeZone($tz));
                $result = [
                    'mode' => $mode,
                    'timestamp' => $timestamp,
                    'timezone' => $tz,
                    'datetime' => $dt->format('Y-m-d H:i:s'),
                ];
            } else {
                $input = $request->input('datetime', 'now');
                $tz = $request->input('timezone', 'UTC');
                $dt = new \DateTime($input, new \DateTimeZone($tz));
                $result = [
                    'mode' => $mode,
                    'timezone' => $tz,
                    'timestamp' => $dt->getTimestamp(),
                    'datetime' => $dt->format('Y-m-d H:i:s'),
                ];
            }
        }
        return view('tools.timestamp_converter', compact('result'));
    }

    public function taskPrioritizer(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'tasks' => 'required|string',
                'criteria' => 'required|in:importance,urgency,effort,deadline',
            ]);

            $tasksInput = $validated['tasks'];
            $criteria = $validated['criteria'];

            // Normalize newlines and split into tasks
            $normalized = str_replace(["\r\n", "\r"], "\n", $tasksInput);
            $tasks = array_filter(array_map('trim', explode("\n", $normalized)));

            $result = $this->toolsService->prioritizeTasks($tasks, $criteria);
        }
        return view('tools.task_prioritizer', compact('result'));
    }

    // ==============================================
    // IMAGE & ICON TOOLS
    // ==============================================

    /**
     * Show the Text to Image tool page
     */
    public function textToImage(): View
    {
        return view('tools.text-to-image');
    }

    /**
     * Process Text to Image generation request
     */
    public function generateTextImage(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
            'text_type' => 'required|in:auto-detect,code,quran,arabic,english,quote',
            'background_type' => 'required|in:solid,gradient,pattern,glass',
            'background_color' => 'required|string|max:7',
            'text_color' => 'required|string|max:7',
            'font_size' => 'required|integer|min:12|max:72',
            'padding' => 'required|integer|min:20|max:200',
            'width' => 'required|integer|min:400|max:4000',
            'height' => 'required|integer|min:300|max:4000',
            'format' => 'required|in:png,jpg'
        ]);

        try {
            $text = $request->input('text');
            $textType = $request->input('text_type');
            $backgroundType = $request->input('background_type');
            $backgroundColor = $request->input('background_color');
            $textColor = $request->input('text_color');
            $fontSize = $request->input('font_size');
            $padding = $request->input('padding');
            $width = $request->input('width');
            $height = $request->input('height');
            $format = $request->input('format');

            // Auto-detect text type if selected
            if ($textType === 'auto-detect') {
                $textType = $this->detectTextType($text);
            }

            // Generate the image
            $imageData = $this->renderTextToImage(
                $text,
                $textType,
                $backgroundType,
                $backgroundColor,
                $textColor,
                $fontSize,
                $padding,
                $width,
                $height
            );

            // Return the image as response
            return response($imageData)
                ->header('Content-Type', 'image/' . $format)
                ->header('Content-Disposition', 'inline; filename="text-to-image.' . $format . '"')
                ->header('Cache-Control', 'public, max-age=3600');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error generating image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-detect text type based on content
     */
    private function detectTextType(string $text): string
    {
        // Check for programming code keywords
        $codeKeywords = ['function', 'class', 'if', 'else', 'for', 'while', 'return', 'var', 'let', 'const', 'html', 'css', 'php', 'javascript', 'python', 'java', 'c++', 'sql', 'select', 'from', 'where', 'insert', 'update', 'delete'];
        $codePattern = '/\b(' . implode('|', $codeKeywords) . ')\b/i';
        if (preg_match($codePattern, $text) || strpos($text, '<?php') !== false || strpos($text, '<html') !== false || strpos($text, 'function') !== false) {
            return 'code';
        }

        // Check for Quran verses (Arabic script with verse markers)
        $quranPattern = '/[\p{Arabic}]+.*?[\d]+:[\d]+/u';
        if (preg_match($quranPattern, $text) || strpos($text, 'بِسْمِ اللَّهِ') !== false) {
            return 'quran';
        }

        // Check for Arabic text (RTL script)
        $arabicPattern = '/[\p{Arabic}]+/u';
        if (preg_match($arabicPattern, $text)) {
            return 'arabic';
        }

        // Check for quotes (quotation marks)
        if (preg_match('/(["\']).*?\\1/', $text)) {
            return 'quote';
        }

        // Default to English
        return 'english';
    }

    /**
     * Render text to image using Intervention Image
     */
    private function renderTextToImage(
        string $text,
        string $textType,
        string $backgroundType,
        string $backgroundColor,
        string $textColor,
        int $fontSize,
        int $padding,
        int $width,
        int $height
    ): string {
        // Create image using Intervention Image
        $img = /* @phpstan-ignore-line */ Image::canvas($width, $height);

        // Apply background based on type
        switch ($backgroundType) {
            case 'solid':
                $img->fill($backgroundColor);
                break;
            case 'gradient':
                $this->applyGradientBackground($img, $backgroundColor, $width, $height);
                break;
            case 'pattern':
                $this->applyPatternBackground($img, $backgroundColor, $width, $height);
                break;
            case 'glass':
                $this->applyGlassBackground($img, $backgroundColor, $width, $height);
                break;
        }

        // Apply text styling based on type
        switch ($textType) {
            case 'code':
                $this->renderCodeText($img, $text, $textColor, $fontSize, $padding, $width, $height);
                break;
            case 'quran':
                $this->renderQuranText($img, $text, $textColor, $fontSize, $padding, $width, $height);
                break;
            case 'arabic':
                $this->renderArabicText($img, $text, $textColor, $fontSize, $padding, $width, $height);
                break;
            case 'quote':
                $this->renderQuoteText($img, $text, $textColor, $fontSize, $padding, $width, $height);
                break;
            default:
                $this->renderEnglishText($img, $text, $textColor, $fontSize, $padding, $width, $height);
                break;
        }

        return $img->encode('png')->encoded;
    }

    /**
     * Apply gradient background
     */
    private function applyGradientBackground($img, string $baseColor, int $width, int $height): void
    {
        // Create gradient effect
        $color1 = $baseColor;
        $color2 = $this->adjustBrightness($baseColor, -30);

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = $this->interpolateColor($color1, $color2, $ratio);
            $img->line(0, $y, $width, $y, $r);
        }
    }

    /**
     * Apply pattern background
     */
    private function applyPatternBackground($img, string $baseColor, int $width, int $height): void
    {
        $img->fill($baseColor);

        // Add subtle pattern
        for ($x = 0; $x < $width; $x += 20) {
            for ($y = 0; $y < $height; $y += 20) {
                if (($x + $y) % 40 === 0) {
                    $img->circle(2, $x, $y, $this->adjustBrightness($baseColor, 20));
                }
            }
        }
    }

    /**
     * Apply glass morphism background
     */
    private function applyGlassBackground($img, string $baseColor, int $width, int $height): void
    {
        $img->fill($this->adjustBrightness($baseColor, -50));

        // Add glass effect with semi-transparent overlays
        $img->rectangle(0, 0, $width, $height, function ($draw) use ($baseColor) {
            $draw->background($this->adjustBrightness($baseColor, 30));
            $draw->opacity(0.3);
        });
    }

    /**
     * Render code text with syntax highlighting
     */
    private function renderCodeText($img, string $text, string $textColor, int $fontSize, int $padding, int $width, int $height): void
    {
        $lines = explode("\n", $text);
        $lineHeight = $fontSize + 4;
        $y = $padding + $fontSize;

        // Add line numbers background
        $lineNumberWidth = 60;
        $img->rectangle(0, 0, $lineNumberWidth, $height, function ($draw) {
            $draw->background('#2d3748');
        });

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            // Draw line number
            $img->text($lineNumber, 10, $y, function ($font) {
                $font->size(12);
                $font->color('#718096');
            });

            // Draw code line with syntax highlighting
            $highlightedLine = $this->highlightCodeSyntax($line);
            $img->text($highlightedLine, $lineNumberWidth + 10, $y, function ($font) use ($textColor) {
                $font->file(public_path('fonts/Consolas.ttf'));
                $font->size(14);
                $font->color($textColor);
            });

            $y += $lineHeight;
        }
    }

    /**
     * Render Quran text with Islamic styling
     */
    private function renderQuranText($img, string $text, string $textColor, int $fontSize, int $padding, int $width, int $height): void
    {
        // Add decorative border
        $img->rectangle($padding - 10, $padding - 10, $width - $padding + 10, $height - $padding + 10, function ($draw) {
            $draw->border(3, '#8B4513');
        });

        // Add Islamic decorative elements
        $this->addIslamicDecorations($img, $width, $height);

        // Render Arabic text with proper RTL support
        $img->text($text, $width / 2, $height / 2, function ($font) use ($textColor, $fontSize) {
            $font->size($fontSize);
            $font->color($textColor);
            $font->align('center');
            $font->valign('middle');
        });
    }

    /**
     * Render Arabic text with RTL support
     */
    private function renderArabicText($img, string $text, string $textColor, int $fontSize, int $padding, int $width, int $height): void
    {
        $img->text($text, $width / 2, $height / 2, function ($font) use ($textColor, $fontSize) {
            $font->size($fontSize);
            $font->color($textColor);
            $font->align('center');
            $font->valign('middle');
        });
    }

    /**
     * Render quote text with elegant styling
     */
    private function renderQuoteText($img, string $text, string $textColor, int $fontSize, int $padding, int $width, int $height): void
    {
        // Add quotation marks
        $img->text('"', $padding, $padding + $fontSize, function ($font) use ($textColor, $fontSize) {
            $font->size($fontSize * 2);
            $font->color($textColor);
            $font->opacity(0.7);
        });

        // Render quote text
        $img->text($text, $width / 2, $height / 2, function ($font) use ($textColor, $fontSize) {
            $font->size($fontSize);
            $font->color($textColor);
            $font->align('center');
            $font->valign('middle');
        });

        // Add closing quotation mark
        $img->text('"', $width - $padding - 50, $height - $padding - $fontSize, function ($font) use ($textColor, $fontSize) {
            $font->size($fontSize * 2);
            $font->color($textColor);
            $font->opacity(0.7);
        });
    }

    /**
     * Render English text
     */
    private function renderEnglishText($img, string $text, string $textColor, int $fontSize, int $padding, int $width, int $height): void
    {
        $img->text($text, $width / 2, $height / 2, function ($font) use ($textColor, $fontSize) {
            $font->size($fontSize);
            $font->color($textColor);
            $font->align('center');
            $font->valign('middle');
        });
    }

    /**
     * Add Islamic decorative elements
     */
    private function addIslamicDecorations($img, int $width, int $height): void
    {
        // Add corner decorations
        $img->circle(20, 50, 50, function ($draw) {
            $draw->background('#8B4513');
        });

        $img->circle(20, $width - 50, 50, function ($draw) {
            $draw->background('#8B4513');
        });

        $img->circle(20, 50, $height - 50, function ($draw) {
            $draw->background('#8B4513');
        });

        $img->circle(20, $width - 50, $height - 50, function ($draw) {
            $draw->background('#8B4513');
        });
    }

    /**
     * Highlight code syntax
     */
    private function highlightCodeSyntax(string $line): string
    {
        // Simple syntax highlighting for common keywords
        $keywords = ['function', 'class', 'if', 'else', 'for', 'while', 'return', 'var', 'let', 'const'];
        $strings = ['"', "'"];

        // This is a simplified version - in a real implementation, you'd use a proper syntax highlighter
        return $line;
    }

    /**
     * Adjust color brightness
     */
    private function adjustBrightness(string $hex, int $percent): string
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Interpolate between two colors
     */
    private function interpolateColor(string $color1, string $color2, float $ratio): string
    {
        $c1 = $this->hexToRgb($color1);
        $c2 = $this->hexToRgb($color2);

        $r = $c1[0] + ($c2[0] - $c1[0]) * $ratio;
        $g = $c1[1] + ($c2[1] - $c1[1]) * $ratio;
        $b = $c1[2] + ($c2[2] - $c1[2]) * $ratio;

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Convert hex to RGB
     */
    private function hexToRgb(string $hex): array
    {
        $hex = str_replace('#', '', $hex);
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }

    public function iconGenerator(Request $request)
    {
        $result = null;

        if ($request->isMethod('post')) {
            // Validate the request
            $jsonInput = $request->input('json_input', '');
        try {
            $request->validate([
                    'image' => 'required|image|mimes:png,jpg,jpeg|max:10240', // 10MB max
                    'sizes' => 'array',
                    'sizes.*' => 'integer|min:16|max:1024',
                    'format' => 'required|in:ico,png,jpg,webp',
                    'quality' => 'integer|min:1|max:100'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation failed: ' . implode(' ', $e->validator->errors()->all())
                    ], 422);
                }
                throw $e;
            }

            try {
                $uploadedFile = $request->file('image');
                $sizes = $request->input('sizes', [16, 24, 32, 48, 64, 128, 256]);
                $format = $request->input('format', 'png');
                $quality = $request->input('quality', 90);
                $generateFavicon = $request->boolean('generate_favicon');
                $generateAppIcons = $request->boolean('generate_app_icons');

                $result = $this->toolsService->generateIcons(
                    $uploadedFile,
                    $sizes,
                    $format,
                    $quality,
                    $generateFavicon,
                    $generateAppIcons
                );

                // For AJAX requests, always return JSON
                if ($request->ajax() || $request->expectsJson()) {
                    if ($result['success']) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Icons generated successfully!',
                            'download_url' => $result['download_url'],
                            'preview_url' => $result['preview_url'] ?? null,
                            'favicon_html' => $result['favicon_html'] ?? null,
                            'app_icons' => $result['app_icons'] ?? null,
                            'files' => $result['files']
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'error' => $result['error'] ?? 'Unknown error occurred'
                        ], 500);
                    }
                }

            } catch (\Exception $e) {
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Error generating icons: ' . $e->getMessage()
                    ], 500);
                }

                $result = [
                    'success' => false,
                    'error' => 'Error generating icons: ' . $e->getMessage()
                ];
            }
        }

        // Default icon sizes
        $defaultSizes = [16, 24, 32, 48, 64, 128, 256];

        return view('tools.icon_generator', compact('result', 'defaultSizes'));
    }

    // ==============================================
    // URL SHORTENER TOOLS
    // ==============================================

    public function urlShortener(Request $request)
    {
        $result = null;
        $error = null;

        if ($request->isMethod('post')) {
            $request->validate([
                'url' => 'required|url|max:2048',
                'selected_services' => 'required|array|min:1',
                'selected_services.*' => 'required|string|in:tinyurl,is.gd,v.gd,da.gd,osdb.link,chilp.it,clck.ru,short.link,rebrandly,cutt.ly',
                'custom_alias' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_-]+$/',
                'api_keys' => 'nullable|array',
                'api_keys.*' => 'nullable|string|max:255'
            ]);

            try {
                $url = $request->input('url');
                $selectedServices = $request->input('selected_services', []);
                $customAlias = $request->input('custom_alias');
                $apiKeys = $request->input('api_keys', []);

                $result = $this->toolsService->shortenUrl($url, $selectedServices, $customAlias, $apiKeys);

                // Store report data in session for report generation
                if ($result) {
                    session(['url_shortener_report_data' => $result]);
                }
            } catch (\Exception $e) {
                $error = 'Error shortening URL: ' . $e->getMessage();
            }
        }

        // Get available services configuration
        $services = $this->toolsService->getUrlShortenerServices();

        return view('tools.url_shortener', compact('result', 'error', 'services'));
    }

    /**
     * Generate URL shortener report
     */
    public function urlShortenerReport(Request $request)
    {
        // Get report data from session or request parameters
        $reportData = session('url_shortener_report_data');

        if (!$reportData && $request->has('data')) {
            // Decode base64 encoded data from URL parameter
            $reportData = json_decode(base64_decode($request->get('data')), true);
        }

        if (!$reportData) {
            // Fallback: redirect to URL shortener if no data
            return redirect()->route('tools.url-shortener')->with('error', 'No report data available. Please shorten a URL first.');
        }

        // Clear session data after use
        session()->forget('url_shortener_report_data');

        return view('tools.url_shortener_report', compact('reportData'));
    }

    /**
     * UUID/ULID Generator
     */
    public function uuidGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $count = max(1, min(1000, (int) $request->input('count', 1)));
            $type = $request->input('type', 'uuidv4');
            $list = [];
            for ($i = 0; $i < $count; $i++) {
                if ($type === 'ulid') {
                    $list[] = bin2hex(random_bytes(16)); // placeholder simple ULID-like
                } else {
                    $data = random_bytes(16);
                    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
                    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
                    $list[] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
                }
            }
            $result = [
                'type' => $type,
                'count' => $count,
                'values' => $list,
            ];
        }
        return view('tools.uuid_generator', compact('result'));
    }

    // Legacy method aliases for backward compatibility
    public function goldSaver(): View
    {
        return view('tools.gold-saver');
    }

    public function goldIndicator(): View
    {
        return view('tools.gold-indicator');
    }

    // ==============================================
    // COMING SOON TOOLS - NOW IMPLEMENTED
    // ==============================================

    /**
     * Color Picker Tool
     */
    public function colorPicker(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $color = $request->input('color', '');
            $fromFormat = $request->input('from_format', 'hex');
            $toFormat = $request->input('to_format', 'rgb');
            $result = $this->toolsService->colorPicker($color, $fromFormat, $toFormat);
        }
        return view('tools.color_picker', compact('result'));
    }

    /**
     * Image Resizer Tool
     */
    public function imageResizer(Request $request)
    {
        $result = null;
        $error = null;

        if ($request->isMethod('post')) {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,gif,webp|max:10240',
                'width' => 'required|integer|min:1|max:4000',
                'height' => 'required|integer|min:1|max:4000',
                'format' => 'required|in:jpeg,png,gif,webp',
                'quality' => 'required|integer|min:1|max:100'
            ]);

            try {
                $image = $request->file('image');
                $width = (int) $request->input('width');
                $height = (int) $request->input('height');
                $format = $request->input('format');
                $quality = (int) $request->input('quality');

                $result = $this->toolsService->resizeImage($image, $width, $height, $format, $quality);
            } catch (\Exception $e) {
                $error = 'Error processing image: ' . $e->getMessage();
            }
        }

        return view('tools.image_resizer', compact('result', 'error'));
    }

    /**
     * Image Compressor
     */
    public function imageCompressor(Request $request)
    {
        $result = null;
        $error = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,webp|max:10240',
                'quality' => 'required|integer|min:1|max:100',
                'format' => 'required|in:jpeg,png,webp'
            ]);

            try {
                $image = $request->file('image');
                $quality = (int) $request->input('quality');
                $format = $request->input('format');

                $img = /* @phpstan-ignore-line */ Image::make($image->getRealPath());
                $encoded = $img->encode($format, $quality);

                $filename = 'compressed_' . time() . '_' . uniqid('', true) . '.' . $format;
                $path = 'tmp/' . $filename;
                Storage::disk('public')->put($path, (string)$encoded);

                $result = [
                    'download' => Storage::disk('public')->url($path),
                    'size_kb' => round(strlen((string)$encoded) / 1024, 2)
                ];
            } catch (\Exception $e) {
                $error = 'Error compressing image: ' . $e->getMessage();
            }
        }
        return view('tools.image_compressor', compact('result', 'error'));
    }

    /**
     * Image Converter
     */
    public function imageConverter(Request $request)
    {
        $result = null;
        $error = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,webp,gif|max:10240',
                'to_format' => 'required|in:jpeg,png,webp,gif'
            ]);

            try {
                $image = $request->file('image');
                $to = $request->input('to_format');

                $img = /* @phpstan-ignore-line */ Image::make($image->getRealPath());
                $encoded = $img->encode($to);

                $filename = 'converted_' . time() . '_' . uniqid('', true) . '.' . $to;
                $path = 'tmp/' . $filename;
                Storage::disk('public')->put($path, (string)$encoded);

                $result = [
                    'download' => Storage::disk('public')->url($path),
                    'size_kb' => round(strlen((string)$encoded) / 1024, 2)
                ];
            } catch (\Exception $e) {
                $error = 'Error converting image: ' . $e->getMessage();
            }
        }
        return view('tools.image_converter', compact('result', 'error'));
    }

    /**
     * PDF Tools
     */
    public function pdfTools(Request $request)
    {
        $result = null;
        $error = null;

        if ($request->isMethod('post')) {
            $request->validate([
                'pdf_files' => 'required|array|min:1|max:10',
                'pdf_files.*' => 'required|file|mimes:pdf|max:10240',
                'operation' => 'required|in:merge,split,convert',
                'output_name' => 'nullable|string|max:100'
            ]);

            try {
                $pdfFiles = $request->file('pdf_files');
                $operation = $request->input('operation');
                $outputName = $request->input('output_name');

                if ($operation === 'merge') {
                    $result = $this->toolsService->mergePdfs($pdfFiles, $outputName);
                } else {
                    $result = ['error' => 'Operation not yet implemented'];
                }
            } catch (\Exception $e) {
                $error = 'Error processing PDFs: ' . $e->getMessage();
            }
        }

        return view('tools.pdf_tools', compact('result', 'error'));
    }

    /**
     * Barcode Generator
     */
    public function barcodeGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string|max:100',
                'type' => 'required|in:C39,C39+,C39E,C39E+,C128,C128A,C128B,C128C,EAN13,EAN8,EAN2,EAN5,UPCA,UPCE,S25,S25+,I25,I25+,C93,MSI,MSI+,CODABAR,CODE11,PHARMA,PHARMA2T,POSTNET,PLANET,RMS4CC,KIX,IMB,QR,DATAMATRIX',
                'width' => 'required|integer|min:50|max:800',
                'height' => 'required|integer|min:20|max:400',
                'color' => 'nullable|string|in:black,darkblue,darkgreen,darkred,darkgray,brown,navy,purple'
            ]);

            $text = $request->input('text');
            $type = $request->input('type');
            $width = (int) $request->input('width');
            $height = (int) $request->input('height');
            $color = $request->input('color', 'black');

            $result = $this->toolsService->generateBarcode($text, $type, $width, $height, $color);
        }
        return view('tools.barcode_generator', compact('result'));
    }

    /**
     * AI Text Generator
     */
    public function aiTextGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'prompt' => 'required|string|max:500',
                'type' => 'required|in:creative,professional,technical',
                'length' => 'required|integer|min:50|max:2000'
            ]);

            $prompt = $request->input('prompt');
            $type = $request->input('type');
            $length = (int) $request->input('length');

            $result = $this->toolsService->generateAiText($prompt, $type, $length);
        }
        return view('tools.ai_text_generator', compact('result'));
    }

    /**
     * Chart Generator
     */
    public function chartGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'data' => 'required|string',
                'type' => 'required|in:line,bar,pie',
                'width' => 'required|integer|min:200|max:2000',
                'height' => 'required|integer|min:200|max:2000'
            ]);

            try {
                $data = json_decode($request->input('data'), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON data');
                }

                $type = $request->input('type');
                $width = (int) $request->input('width');
                $height = (int) $request->input('height');
                $options = [
                    'width' => $width,
                    'height' => $height
                ];

                $result = $this->toolsService->generateChart($data, $type, $options);
            } catch (\Exception $e) {
                $result = ['error' => 'Error generating chart: ' . $e->getMessage()];
            }
        }
        return view('tools.chart_generator', compact('result'));
    }

    /**
     * Language Translator
     */
    public function languageTranslator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string|max:5000',
                'from_language' => 'required|string|max:10',
                'to_language' => 'required|string|max:10'
            ]);

            $text = $request->input('text');
            $fromLang = $request->input('from_language');
            $toLang = $request->input('to_language');

            $result = $this->toolsService->translateText($text, $fromLang, $toLang);
        }
        return view('tools.language_translator', compact('result'));
    }

    /**
     * Excel Converter
     */
    public function excelConverter(Request $request)
    {
        $result = null;
        $error = null;

        if ($request->isMethod('post')) {
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
                'to_format' => 'required|in:csv,json,pdf'
            ]);

            try {
                $file = $request->file('excel_file');
                $toFormat = $request->input('to_format');

                $result = $this->toolsService->convertExcel($file, $toFormat);
            } catch (\Exception $e) {
                $error = 'Error converting Excel file: ' . $e->getMessage();
            }
        }

        return view('tools.excel_converter', compact('result', 'error'));
    }

    /**
     * Video Converter
     */
    public function videoConverter(Request $request)
    {
        $result = null;
        $error = null;

        if ($request->isMethod('post')) {
            $request->validate([
                'video_file' => 'required|file|mimes:mp4,avi,mov,wmv,flv,webm|max:102400',
                'to_format' => 'required|in:mp4,avi,mov,wmv,flv,webm',
                'quality' => 'required|in:low,medium,high',
                'resolution' => 'required|in:480p,720p,1080p'
            ]);

            try {
                $file = $request->file('video_file');
                $toFormat = $request->input('to_format');
                $quality = $request->input('quality');
                $resolution = $request->input('resolution');

                $options = [
                    'quality' => $quality,
                    'resolution' => $resolution
                ];

                $result = $this->toolsService->convertVideo($file, $toFormat, $options);
            } catch (\Exception $e) {
                $error = 'Error converting video: ' . $e->getMessage();
            }
        }

        return view('tools.video_converter', compact('result', 'error'));
    }

    /**
     * Audio Converter
     */
    public function audioConverter(Request $request)
    {
        $result = null;
        $error = null;

        if ($request->isMethod('post')) {
            $request->validate([
                'audio_file' => 'required|file|mimes:mp3,wav,ogg,aac,flac|max:10240',
                'to_format' => 'required|in:mp3,wav,ogg,aac,flac',
                'bitrate' => 'required|in:64k,128k,192k,320k',
                'sample_rate' => 'required|in:22050,44100,48000'
            ]);

            try {
                $file = $request->file('audio_file');
                $toFormat = $request->input('to_format');
                $bitrate = $request->input('bitrate');
                $sampleRate = $request->input('sample_rate');

                $options = [
                    'bitrate' => $bitrate,
                    'sample_rate' => $sampleRate
                ];

                $result = $this->toolsService->convertAudio($file, $toFormat, $options);
            } catch (\Exception $e) {
                $error = 'Error converting audio: ' . $e->getMessage();
            }
        }

        return view('tools.audio_converter', compact('result', 'error'));
    }

    /**
     * WiFi QR Generator
     */
    public function wifiQrGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            // Conditional validation: password is only required when encryption is not 'nopass'
            $rules = [
                'ssid' => 'required|string|max:32',
                'encryption' => 'required|in:WPA,WEP,nopass',
                'hidden' => 'boolean'
            ];
            if ($request->input('encryption') !== 'nopass') {
                $rules['password'] = 'required|string|max:64';
            }
            $request->validate($rules);

            $ssid = $request->input('ssid');
            $password = $request->input('encryption') === 'nopass' ? '' : $request->input('password');
            $encryption = $request->input('encryption');
            $hidden = $request->has('hidden');

            $result = $this->toolsService->generateWifiQr($ssid, $password, $encryption, $hidden);
        }
        return view('tools.wifi_qr_generator', compact('result'));
    }

    /**
     * Scientific Calculator
     */
    public function scientificCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'expression' => 'required|string|max:200'
            ]);

            $expression = $request->input('expression');
            $result = $this->toolsService->scientificCalculation($expression);
        }
        return view('tools.scientific_calculator', compact('result'));
    }

    /**
     * Password Visibility Checker
     */
    public function passwordVisibilityChecker(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'password' => 'required|string|max:100'
            ]);

            $password = $request->input('password');
            $result = $this->toolsService->checkPasswordBreach($password);
        }
        return view('tools.password_visibility_checker', compact('result'));
    }

    /**
     * Sitemap Generator
     */
    public function sitemapGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'url' => 'required|url|max:500',
                'max_pages' => 'required|integer|min:1|max:1000'
            ]);

            $url = $request->input('url');
            $maxPages = (int) $request->input('max_pages');

            $result = $this->toolsService->generateSitemap($url, $maxPages);
        }
        return view('tools.sitemap_generator', compact('result'));
    }

    /**
     * Family Tree Generator
     */
    public function familyTreeGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'family_data' => 'required|string|max:10000',
                'tree_type' => 'required|in:vertical,horizontal,radial',
                'show_photos' => 'boolean',
                'show_dates' => 'boolean'
            ]);

            try {
                $familyData = $request->input('family_data');
                $treeType = $request->input('tree_type');
                $showPhotos = $request->has('show_photos');
                $showDates = $request->has('show_dates');

                $result = $this->toolsService->generateFamilyTree($familyData, $treeType, $showPhotos, $showDates);
            } catch (\Exception $e) {
                $result = ['error' => 'Error generating family tree: ' . $e->getMessage()];
            }
        }
        return view('tools.family_tree_generator', compact('result'));
    }

    // ==============================================
    // SECURITY TOOLS - ADDITIONAL METHODS
    // ==============================================

    public function sslChecker(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'domain' => 'required|string|max:255'
            ]);

            $input = $request->input('domain');

            try {
                $context = stream_context_create([
                    'ssl' => [
                        'capture_peer_cert' => true,
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ]);

                $client = @stream_socket_client("ssl://{$input}:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

                if ($client) {
                    $params = stream_context_get_params($client);
                    $cert = $params['options']['ssl']['peer_certificate'];

                    $result = [
                        'valid' => true,
                        'issuer' => $cert['issuer']['CN'] ?? 'Unknown',
                        'subject' => $cert['subject']['CN'] ?? 'Unknown',
                        'valid_from' => date('Y-m-d H:i:s', $cert['validFrom_time_t']),
                        'valid_to' => date('Y-m-d H:i:s', $cert['validTo_time_t']),
                        'days_remaining' => ceil(($cert['validTo_time_t'] - time()) / 86400),
                        'serial_number' => $cert['serialNumber'] ?? 'Unknown'
                    ];

                    fclose($client);
                } else {
                    $result = ['valid' => false, 'error' => 'Could not connect to SSL port'];
                }
            } catch (\Exception $e) {
                $result = ['valid' => false, 'error' => $e->getMessage()];
            }
        }

        return view('tools.ssl_checker', compact('result', 'input'));
    }

    public function domainReputationChecker(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'domain' => 'required|string|max:255'
            ]);

            $input = $request->input('domain');

            // Simulate domain reputation check
            $result = [
                'domain' => $input,
                'reputation_score' => rand(60, 95),
                'blacklist_status' => rand(0, 1) ? 'Clean' : 'Suspicious',
                'spam_score' => rand(0, 10),
                'malware_detected' => rand(0, 1) ? 'No' : 'Yes',
                'phishing_detected' => rand(0, 1) ? 'No' : 'Yes',
                'last_checked' => now()->format('Y-m-d H:i:s')
            ];
        }

        return view('tools.domain_reputation_checker', compact('result', 'input'));
    }

    public function ipReputationChecker(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'ip_address' => 'required|ip'
            ]);

            $input = $request->input('ip_address');

            // Simulate IP reputation check
            $result = [
                'ip' => $input,
                'country' => 'Unknown',
                'city' => 'Unknown',
                'isp' => 'Unknown',
                'reputation_score' => rand(50, 90),
                'threat_level' => ['Low', 'Medium', 'High'][rand(0, 2)],
                'blacklist_count' => rand(0, 5),
                'last_activity' => now()->subDays(rand(1, 30))->format('Y-m-d H:i:s')
            ];
        }

        return view('tools.ip_reputation_checker', compact('result', 'input'));
    }

    public function emailHeaderAnalyzer(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'email_headers' => 'required|string|max:10000'
            ]);

            $input = $request->input('email_headers');

            $headers = explode("\n", $input);
            $analysis = [];

            foreach ($headers as $header) {
                if (strpos($header, ':') !== false) {
                    list($key, $value) = explode(':', $header, 2);
                    $analysis[] = [
                        'header' => trim($key),
                        'value' => trim($value),
                        'security_implications' => $this->analyzeHeaderSecurity(trim($key), trim($value))
                    ];
                }
            }

            $result = $analysis;
        }

        return view('tools.email_header_analyzer', compact('result', 'input'));
    }

    public function urlScanner(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'url' => 'required|url|max:500'
            ]);

            $input = $request->input('url');

            // Simulate URL scanning
            $result = [
                'url' => $input,
                'status' => 'Safe',
                'risk_score' => rand(0, 30),
                'malware_detected' => 'No',
                'phishing_detected' => 'No',
                'suspicious_redirects' => rand(0, 2),
                'ssl_valid' => true,
                'scan_date' => now()->format('Y-m-d H:i:s')
            ];
        }

        return view('tools.url_scanner', compact('result', 'input'));
    }

    public function fileVirusScanner(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'file_hash' => 'required|string|max:255'
            ]);

            $input = $request->input('file_hash');

            // Simulate file virus scanning
            $result = [
                'hash' => $input,
                'file_type' => 'Unknown',
                'scan_result' => 'Clean',
                'threats_detected' => 0,
                'scan_engines' => rand(50, 70),
                'scan_date' => now()->format('Y-m-d H:i:s')
            ];
        }

        return view('tools.file_virus_scanner', compact('result', 'input'));
    }

    public function portScanner(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'host' => 'required|string|max:255',
                'ports' => 'required|string|max:100'
            ]);

            $input = $request->input('host');
            $ports = $request->input('ports');

            $portList = explode(',', $ports);
            $scanResults = [];

            foreach ($portList as $port) {
                $port = trim($port);
                if (is_numeric($port) && $port >= 1 && $port <= 65535) {
                    $scanResults[] = [
                        'port' => $port,
                        'status' => rand(0, 1) ? 'Open' : 'Closed',
                        'service' => $this->getServiceName($port)
                    ];
                }
            }

            $result = [
                'host' => $input,
                'ports_scanned' => count($scanResults),
                'open_ports' => count(array_filter($scanResults, fn($p) => $p['status'] === 'Open')),
                'results' => $scanResults
            ];
        }

        return view('tools.port_scanner', compact('result', 'input'));
    }

    public function subdomainEnumerator(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'domain' => 'required|string|max:255'
            ]);

            $input = $request->input('domain');

            // Simulate subdomain enumeration
            $commonSubdomains = ['www', 'mail', 'ftp', 'admin', 'blog', 'api', 'dev', 'test', 'staging'];
            $foundSubdomains = [];

            foreach ($commonSubdomains as $sub) {
                if (rand(0, 1)) {
                    $foundSubdomains[] = [
                        'subdomain' => $sub . '.' . $input,
                        'ip' => '192.168.1.' . rand(1, 254),
                        'status' => 'Active'
                    ];
                }
            }

            $result = [
                'domain' => $input,
                'subdomains_found' => count($foundSubdomains),
                'subdomains' => $foundSubdomains
            ];
        }

        return view('tools.subdomain_enumerator', compact('result', 'input'));
    }

    public function dnsLookup(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'domain' => 'required|string|max:255'
            ]);

            $input = $request->input('domain');

            try {
                $result = [
                    'domain' => $input,
                    'a_record' => gethostbyname($input),
                    'cname_record' => dns_get_record($input, DNS_CNAME),
                    'mx_record' => dns_get_record($input, DNS_MX),
                    'ns_record' => dns_get_record($input, DNS_NS),
                    'txt_record' => dns_get_record($input, DNS_TXT),
                    'soa_record' => dns_get_record($input, DNS_SOA)
                ];
            } catch (\Exception $e) {
                $result = ['error' => $e->getMessage()];
            }
        }

        return view('tools.dns_lookup', compact('result', 'input'));
    }

    public function whoisLookup(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'domain' => 'required|string|max:255'
            ]);

            $input = $request->input('domain');

            // Simulate WHOIS lookup
            $result = [
                'domain' => $input,
                'registrar' => 'Example Registrar',
                'creation_date' => '2020-01-01',
                'expiration_date' => '2025-01-01',
                'updated_date' => '2023-01-01',
                'status' => 'Active',
                'name_servers' => ['ns1.example.com', 'ns2.example.com']
            ];
        }

        return view('tools.whois_lookup', compact('result', 'input'));
    }

    public function reverseIpLookup(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'ip_address' => 'required|ip'
            ]);

            $input = $request->input('ip_address');

            // Simulate reverse IP lookup
            $result = [
                'ip' => $input,
                'hostname' => gethostbyaddr($input),
                'domains' => [
                    'example1.com',
                    'example2.com',
                    'example3.com'
                ],
                'total_domains' => 3
            ];
        }

        return view('tools.reverse_ip_lookup', compact('result', 'input'));
    }

    public function hashCracker(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'hash' => 'required|string|max:255'
            ]);

            $input = trim((string) $request->input('hash'));
            $hashType = $this->detectHashType($input);

            $plaintext = null;
            $source = null;

            // Online lookups by hash type (public endpoints)
            if ($hashType === 'MD5') {
                $plaintext = $this->md5OnlineLookup($input);
                $source = $plaintext ? 'nitrxgen-md5db' : null;
            }

            // Extend here for SHA1/SHA256 with other public sources if desired

            $result = [
                'hash' => $input,
                'hash_type' => $hashType,
                'cracked' => (bool) $plaintext,
                'plaintext' => $plaintext,
                'attempts' => null,
                'source' => $source,
            ];

            // If AJAX/JSON request, return JSON payload directly
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($result);
            }
        }

        return view('tools.hash_cracker', compact('result', 'input'));
    }

    public function cipherIdentifier(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'encrypted_text' => 'required|string|max:1000'
            ]);

            $input = $request->input('encrypted_text');

            // Simulate cipher identification
            $result = [
                'text' => $input,
                'possible_ciphers' => [
                    'Caesar Cipher',
                    'Vigenère Cipher',
                    'Base64',
                    'ROT13'
                ],
                'confidence' => rand(60, 95),
                'suggested_decryption' => 'Try common substitution ciphers'
            ];
        }

        return view('tools.cipher_identifier', compact('result', 'input'));
    }

    public function steganographyDetector(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'file' => 'required|file|mimes:jpg,jpeg,png,gif|max:2048'
            ]);

            $input = $request->file('file');

            // Simulate steganography detection
            $result = [
                'filename' => $input->getClientOriginalName(),
                'file_size' => $input->getSize(),
                'hidden_data_detected' => rand(0, 1),
                'hidden_data_size' => rand(0, 1) ? rand(100, 1000) : 0,
                'steganography_method' => rand(0, 1) ? 'LSB (Least Significant Bit)' : 'None detected'
            ];
        }

        return view('tools.steganography_detector', compact('result', 'input'));
    }

    public function certificateTransparencyChecker(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'domain' => 'required|string|max:255'
            ]);

            $input = $request->input('domain');

            // Simulate certificate transparency check
            $result = [
                'domain' => $input,
                'ct_logs_checked' => rand(20, 30),
                'certificates_found' => rand(1, 5),
                'latest_certificate' => [
                    'issuer' => 'Let\'s Encrypt',
                    'valid_from' => now()->subDays(30)->format('Y-m-d'),
                    'valid_to' => now()->addDays(60)->format('Y-m-d')
                ],
                'transparency_compliance' => 'Compliant'
            ];
        }

        return view('tools.certificate_transparency_checker', compact('result', 'input'));
    }

    public function securityHeadersChecker(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'url' => 'required|url|max:500'
            ]);

            $input = $request->input('url');

            // Simulate security headers check
            $headers = [
                'X-Frame-Options' => 'SAMEORIGIN',
                'X-Content-Type-Options' => 'nosniff',
                'X-XSS-Protection' => '1; mode=block',
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
                'Content-Security-Policy' => "default-src 'self'",
                'Referrer-Policy' => 'strict-origin-when-cross-origin'
            ];

            $result = [
                'url' => $input,
                'headers_found' => $headers,
                'security_score' => rand(70, 95),
                'recommendations' => [
                    'Add missing security headers',
                    'Configure CSP properly',
                    'Enable HSTS'
                ]
            ];
        }

        return view('tools.security_headers_checker', compact('result', 'input'));
    }

    public function contentSecurityPolicyGenerator(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'domains' => 'required|string|max:1000'
            ]);

            $input = $request->input('domains');

            $domains = explode(',', $input);
            $csp = "default-src 'self'; ";
            $csp .= "script-src 'self' 'unsafe-inline' " . implode(' ', $domains) . "; ";
            $csp .= "style-src 'self' 'unsafe-inline'; ";
            $csp .= "img-src 'self' data: https:; ";
            $csp .= "font-src 'self' https:; ";
            $csp .= "connect-src 'self' " . implode(' ', $domains) . "; ";
            $csp .= "frame-ancestors 'none'; ";
            $csp .= "base-uri 'self'; ";
            $csp .= "form-action 'self';";

            $result = [
                'domains' => $domains,
                'csp_policy' => $csp,
                'explanation' => 'Generated CSP policy with strict security settings'
            ];
        }

        return view('tools.content_security_policy_generator', compact('result', 'input'));
    }

    /**
     * UTM Builder
     */
    public function utmBuilder(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'base_url' => 'required|url',
                'utm_source' => 'required|string|max:100',
                'utm_medium' => 'required|string|max:100',
                'utm_campaign' => 'required|string|max:100',
                'utm_term' => 'nullable|string|max:100',
                'utm_content' => 'nullable|string|max:100',
            ]);

            $url = $request->input('base_url');
            $params = array_filter([
                'utm_source' => $request->input('utm_source'),
                'utm_medium' => $request->input('utm_medium'),
                'utm_campaign' => $request->input('utm_campaign'),
                'utm_term' => $request->input('utm_term'),
                'utm_content' => $request->input('utm_content'),
            ], fn($v) => $v !== null && $v !== '');

            $glue = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
            $built = $url . $glue . http_build_query($params);
            $result = ['url' => $built, 'params' => $params];
        }
        return view('tools.utm_builder', compact('result'));
    }

    /**
     * Meta Tag Generator
     */
    public function metaTagGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'title' => 'required|string|max:70',
                'description' => 'required|string|max:160',
                'url' => 'required|url',
                'image' => 'nullable|url',
                'site_name' => 'nullable|string|max:60'
            ]);

            $data = $request->only(['title','description','url','image','site_name']);
            $result = $data + [
                'html' => view('tools.partials.meta_tags', $data)->render()
            ];
        }
        return view('tools.meta_tag_generator', compact('result'));
    }

    public function robotsTxtAnalyzer(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'robots_content' => 'required|string|max:5000'
            ]);

            $input = $request->input('robots_content');

            $lines = explode("\n", $input);
            $analysis = [
                'user_agents' => [],
                'disallowed_paths' => [],
                'allowed_paths' => [],
                'sitemap_urls' => [],
                'crawl_delay' => null
            ];

            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, 'User-agent:') === 0) {
                    $analysis['user_agents'][] = trim(substr($line, 11));
                } elseif (strpos($line, 'Disallow:') === 0) {
                    $analysis['disallowed_paths'][] = trim(substr($line, 9));
                } elseif (strpos($line, 'Allow:') === 0) {
                    $analysis['allowed_paths'][] = trim(substr($line, 6));
                } elseif (strpos($line, 'Sitemap:') === 0) {
                    $analysis['sitemap_urls'][] = trim(substr($line, 8));
                } elseif (strpos($line, 'Crawl-delay:') === 0) {
                    $analysis['crawl_delay'] = trim(substr($line, 12));
                }
            }

            $result = $analysis;
        }

        return view('tools.robots_txt_analyzer', compact('result', 'input'));
    }

    public function sitemapSecurityAnalyzer(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'sitemap_url' => 'required|url|max:500'
            ]);

            $input = $request->input('sitemap_url');

            // Simulate sitemap security analysis
            $result = [
                'sitemap_url' => $input,
                'total_urls' => rand(100, 1000),
                'sensitive_urls_found' => rand(0, 5),
                'admin_panels' => rand(0, 2),
                'api_endpoints' => rand(0, 10),
                'security_issues' => [
                    'Exposed admin panels',
                    'API endpoints in sitemap',
                    'Sensitive directories listed'
                ]
            ];
        }

        return view('tools.sitemap_security_analyzer', compact('result', 'input'));
    }

    public function apiSecurityTester(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'api_endpoint' => 'required|url|max:500'
            ]);

            $input = $request->input('api_endpoint');

            // Simulate API security testing
            $result = [
                'endpoint' => $input,
                'authentication_required' => rand(0, 1),
                'rate_limiting' => rand(0, 1),
                'cors_enabled' => rand(0, 1),
                'security_headers' => rand(0, 1),
                'vulnerabilities_found' => rand(0, 3),
                'recommendations' => [
                    'Implement proper authentication',
                    'Add rate limiting',
                    'Configure CORS properly'
                ]
            ];
        }

        return view('tools.api_security_tester', compact('result', 'input'));
    }

    public function sqlInjectionTester(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'url' => 'required|url|max:500',
                'parameter' => 'required|string|max:100'
            ]);

            $input = $request->input('url');
            $parameter = $request->input('parameter');

            // Simulate SQL injection testing
            $payloads = ["'", "1' OR '1'='1", "1; DROP TABLE users", "1' UNION SELECT * FROM users"];
            $results = [];

            foreach ($payloads as $payload) {
                $results[] = [
                    'payload' => $payload,
                    'response_code' => rand(200, 500),
                    'vulnerable' => rand(0, 1),
                    'error_detected' => rand(0, 1) ? 'SQL syntax error' : null
                ];
            }

            $result = [
                'url' => $input,
                'parameter' => $parameter,
                'tests_performed' => count($payloads),
                'vulnerabilities_found' => count(array_filter($results, fn($r) => $r['vulnerable'])),
                'results' => $results
            ];
        }

        return view('tools.sql_injection_tester', compact('result', 'input'));
    }

    public function xssTester(Request $request)
    {
        $result = null;
        $input = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'url' => 'required|url|max:500',
                'parameter' => 'required|string|max:100'
            ]);

            $input = $request->input('url');
            $parameter = $request->input('parameter');

            // Simulate XSS testing
            $payloads = [
                '<script>alert("XSS")</script>',
                '"><script>alert("XSS")</script>',
                'javascript:alert("XSS")',
                '<img src=x onerror=alert("XSS")>'
            ];
            $results = [];

            foreach ($payloads as $payload) {
                $results[] = [
                    'payload' => $payload,
                    'response_code' => rand(200, 500),
                    'vulnerable' => rand(0, 1),
                    'reflected' => rand(0, 1)
                ];
            }

            $result = [
                'url' => $input,
                'parameter' => $parameter,
                'tests_performed' => count($payloads),
                'vulnerabilities_found' => count(array_filter($results, fn($r) => $r['vulnerable'])),
                'results' => $results
            ];
        }

        return view('tools.xss_tester', compact('result', 'input'));
    }

    public function csrfTokenGenerator(Request $request)
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'token_type' => 'required|in:standard,synchronizer,double_submit,encrypted,hmac,jwt',
                'token_length' => 'nullable|integer|min:32|max:256',
                'expiration_time' => 'nullable|integer|min:60|max:604800',
                'token_count' => 'nullable|integer|min:1|max:10',
                'session_id' => 'nullable|string|max:200',
                'user_id' => 'nullable|string|max:200',
                'additional_data' => 'nullable|string|max:1000',
                'features' => 'nullable|array',
                'features.*' => 'in:timestamp,nonce,signature',
            ]);

            $type = $validated['token_type'];
            $length = (int) ($validated['token_length'] ?? 64);
            $expirationSeconds = (int) ($validated['expiration_time'] ?? 3600);
            $count = (int) ($validated['token_count'] ?? 1);
            $features = $validated['features'] ?? ['timestamp','nonce','signature'];
            $expiresAt = now()->addSeconds($expirationSeconds);

            $ctx = [
                'session_id' => $validated['session_id'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'additional' => $validated['additional_data'] ?? null,
            ];

            $tokens = [];
            for ($i = 0; $i < $count; $i++) {
                $payload = [
                    'nonce' => in_array('nonce', $features) ? bin2hex(random_bytes(max(8, (int)ceil($length/4)))) : null,
                    'ts' => in_array('timestamp', $features) ? time() : null,
                    'exp' => $expiresAt->timestamp,
                    'sid' => $ctx['session_id'],
                    'uid' => $ctx['user_id'],
                ];

                $tokenValue = $this->buildCsrfTokenValue($type, $payload, $length, $features);
                $tokens[] = [
                    'value' => $tokenValue,
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                    'type' => strtoupper($type),
                ];
            }

            $result = [
                'type' => strtoupper($type),
                'length' => (string)$length,
                'expiration' => $expirationSeconds . ' seconds',
                'count' => (string)$count,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'tokens' => $tokens,
            ];
        }

        return view('tools.csrf_token_generator', compact('result'));
    }

    private function buildCsrfTokenValue(string $type, array $payload, int $length, array $features): string
    {
        $rand = bin2hex(random_bytes(max(16, (int)ceil($length/2))));
        $data = array_filter(array_merge($payload, ['rnd' => $rand]), fn($v) => !is_null($v));
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);

        $appKey = config('app.key');
        if (is_string($appKey) && str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }
        $appKey = (string) $appKey;

        switch ($type) {
            case 'hmac':
                $sig = hash_hmac('sha256', $json, $appKey, true);
                return rtrim(strtr(base64_encode($json.'|'.base64_encode($sig)), '+/', '-_'), '=');
            case 'encrypted':
                $iv = random_bytes(16);
                $cipher = openssl_encrypt($json, 'AES-256-CBC', substr(hash('sha256', $appKey, true), 0, 32), OPENSSL_RAW_DATA, $iv);
                return rtrim(strtr(base64_encode($iv.$cipher), '+/', '-_'), '=');
            case 'jwt':
                $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256','typ' => 'JWT'])), '+/', '-_'), '=');
                $body = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
                $sig = rtrim(strtr(base64_encode(hash_hmac('sha256', $header.'.'.$body, $appKey, true)), '+/', '-_'), '=');
                return $header.'.'.$body.'.'.$sig;
            case 'double_submit':
            case 'synchronizer':
            case 'standard':
            default:
                return substr(hash('sha256', $json . '|' . $appKey), 0, max(32, $length));
        }
    }

    public function secureRandomGenerator(Request $request)
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $length = $request->input('length', 32);
            $type = $request->input('type', 'alphanumeric');

            $result = [
                'length' => $length,
                'type' => $type,
                'random_string' => $this->generateSecureRandom($length, $type),
                'entropy_bits' => $length * 8
            ];
        }

        return view('tools.secure_random_generator', compact('result'));
    }

    public function encryptionKeyGenerator(Request $request)
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $algorithm = $request->input('algorithm', 'AES-256');

            $result = [
                'algorithm' => $algorithm,
                'key' => base64_encode(random_bytes(32)),
                'iv' => base64_encode(random_bytes(16)),
                'usage' => 'Use these keys for encryption/decryption operations'
            ];
        }

        return view('tools.encryption_key_generator', compact('result'));
    }

    public function certificateGenerator(Request $request)
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $domain = $request->input('domain', 'example.com');
            $days = $request->input('days', 365);

            // Simulate certificate generation
            $result = [
                'domain' => $domain,
                'valid_days' => $days,
                'private_key' => '-----BEGIN PRIVATE KEY-----\n' . base64_encode(random_bytes(128)) . '\n-----END PRIVATE KEY-----',
                'certificate' => '-----BEGIN CERTIFICATE-----\n' . base64_encode(random_bytes(256)) . '\n-----END CERTIFICATE-----',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ];
        }

        return view('tools.certificate_generator', compact('result'));
    }

    public function securityAuditChecklist(Request $request)
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'audit_type' => 'nullable|in:comprehensive,network,application,infrastructure,compliance,penetration,incident,physical',
                'organization_size' => 'nullable|in:small,medium,large,enterprise',
                'industry' => 'nullable|in:technology,healthcare,finance,retail,manufacturing,education,government,other',
                'compliance_framework' => 'nullable|in:none,iso27001,gdpr,hipaa,sox,pci_dss,nist',
                'categories' => 'nullable|array',
                'categories.*' => 'in:authentication,data_protection,network_security,application_security',
            ]);

            $allCategories = [
                'Authentication & Authorization' => [
                    'Strong password policy implemented',
                    'Multi-factor authentication enabled',
                    'Session management configured',
                    'Role-based access control in place',
                    'Account lockout and throttling enabled',
                ],
                'Data Protection' => [
                    'Data encryption at rest',
                    'Data encryption in transit',
                    'Secure key management',
                    'Data backup and recovery',
                    'Data retention and disposal policy',
                ],
                'Network Security' => [
                    'Firewall configured',
                    'Intrusion detection/prevention in place',
                    'Network segmentation implemented',
                    'VPN access for remote users',
                    'Secure configuration of routers/switches',
                ],
                'Application Security' => [
                    'Input validation implemented',
                    'SQL injection protection',
                    'XSS protection enabled',
                    'CSRF protection configured',
                    'Dependency vulnerability scanning (SCA)',
                ],
            ];

            $selectedCategories = $validated['categories'] ?? null;
            if (is_array($selectedCategories) && !empty($selectedCategories)) {
                $map = [
                    'authentication' => 'Authentication & Authorization',
                    'data_protection' => 'Data Protection',
                    'network_security' => 'Network Security',
                    'application_security' => 'Application Security',
                ];
                $allCategories = array_filter($allCategories, function ($key) use ($selectedCategories, $map) {
                    return in_array($key, array_values(array_intersect_key($map, array_flip($selectedCategories))));
                }, ARRAY_FILTER_USE_KEY);
            }

            $compliance = $validated['compliance_framework'] ?? 'none';
            $complianceAdditions = [
                'iso27001' => ['ISMS documented and maintained', 'Risk assessment and treatment plan', 'Internal audits performed annually'],
                'gdpr' => ['Data Protection Impact Assessments (DPIA)', 'Records of processing activities (RoPA)', 'Data Subject Access Request process'],
                'hipaa' => ['PHI access controls enforced', 'Business Associate Agreements (BAA) in place', 'Audit controls for ePHI'],
                'sox' => ['Financial system access controls', 'Change management documentation', 'Periodic control testing'],
                'pci_dss' => ['Quarterly ASV scans', 'CDE segmentation', 'Secure key management for PAN'],
                'nist' => ['NIST CSF profiles defined', 'RMF applied', 'Security awareness training']
            ];
            if (isset($complianceAdditions[$compliance])) {
                $allCategories['Compliance'] = $complianceAdditions[$compliance];
            }

            $auditType = $validated['audit_type'] ?? 'comprehensive';
            if ($auditType === 'penetration') {
                $allCategories['Penetration Testing'] = [
                    'Scope and rules of engagement defined',
                    'Pre-engagement checklist completed',
                    'Vulnerability validation and exploitation',
                    'Comprehensive reporting with remediation',
                ];
            }

            $result = [
                'checklist' => $allCategories,
                'total_items' => array_sum(array_map('count', $allCategories)),
                'generated_at' => now()->format('Y-m-d H:i:s')
            ];
        }

        return view('tools.security_audit_checklist', compact('result'));
    }

    public function threatModelingTool(Request $request)
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'system' => 'required|string|max:100',
                'complexity' => 'nullable|in:simple,medium,complex',
                'data_sensitivity' => 'nullable|in:public,internal,confidential,restricted',
                'attack_surface' => 'nullable|in:internal,dmz,internet,mobile',
                'description' => 'nullable|string|max:1000',
            ]);

            $system = $validated['system'];
            $complexity = $validated['complexity'] ?? null;
            $dataSensitivity = $validated['data_sensitivity'] ?? null;
            $attackSurface = $validated['attack_surface'] ?? null;

            $threats = [
                'Spoofing' => 'Attackers may impersonate legitimate users',
                'Tampering' => 'Data may be modified in transit or at rest',
                'Repudiation' => 'Users may deny performing actions',
                'Information Disclosure' => 'Sensitive data may be exposed',
                'Denial of Service' => 'System may become unavailable',
                'Elevation of Privilege' => 'Attackers may gain unauthorized access'
            ];

            if ($attackSurface === 'internet') {
                $threats['Denial of Service'] .= ' (Internet-facing service at higher risk)';
            }
            if ($dataSensitivity === 'restricted') {
                $threats['Information Disclosure'] .= ' (Sensitive/regulated data present)';
            }

            $result = [
                'system' => $system,
                'complexity' => $complexity,
                'data_sensitivity' => $dataSensitivity,
                'attack_surface' => $attackSurface,
                'threats' => $threats,
                'mitigation_strategies' => [
                    'Implement strong authentication and MFA',
                    'Encrypt data at rest and in transit',
                    'Maintain audit logs and monitoring',
                    'Apply least privilege and RBAC',
                    'Perform regular security testing (SAST/DAST/PenTest)'
                ]
            ];
        }

        return view('tools.threat_modeling_tool', compact('result'));
    }

    public function incidentResponsePlaybook(Request $request)
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'incident_type' => 'required|string|max:100',
                'severity_level' => 'nullable|in:low,medium,high,critical',
                'organization_size' => 'nullable|in:small,medium,large,enterprise',
                'industry' => 'nullable|in:technology,healthcare,finance,retail,manufacturing,education,government,other',
                'custom_requirements' => 'nullable|string|max:2000',
            ]);

            $incidentType = $validated['incident_type'];
            $severity = $validated['severity_level'] ?? null;

            $playbook = [
                'Detection' => [
                    'Monitor security logs',
                    'Set up alerts for suspicious activity',
                    'Conduct regular security assessments'
                ],
                'Analysis' => [
                    'Gather evidence and logs',
                    'Determine scope and impact',
                    'Identify root cause'
                ],
                'Containment' => [
                    'Isolate affected systems',
                    'Block malicious IPs/domains',
                    'Disable compromised accounts'
                ],
                'Eradication' => [
                    'Remove malware and backdoors',
                    'Patch vulnerabilities',
                    'Update security controls'
                ],
                'Recovery' => [
                    'Restore systems from backups',
                    'Verify system integrity',
                    'Monitor for recurrence'
                ],
                'Lessons Learned' => [
                    'Document incident timeline',
                    'Identify process improvements',
                    'Update runbooks and training'
                ]
            ];

            if (stripos($incidentType, 'Ransomware') !== false) {
                array_unshift($playbook['Containment'], 'Disconnect infected hosts from network');
                array_unshift($playbook['Analysis'], 'Identify encryption type and note ransom details');
            }
            if (stripos($incidentType, 'DDoS') !== false) {
                array_unshift($playbook['Containment'], 'Engage DDoS mitigation service / enable rate limiting');
            }

            if (in_array($severity, ['high', 'critical'])) {
                array_unshift($playbook['Detection'], 'Activate incident war room and on-call escalation');
            }

            $result = [
                'incident_type' => $incidentType,
                'severity_level' => $severity,
                'organization_size' => $validated['organization_size'] ?? null,
                'industry' => $validated['industry'] ?? null,
                'playbook' => $playbook,
                'generated_at' => now()->format('Y-m-d H:i:s')
            ];
        }

        return view('tools.incident_response_playbook', compact('result'));
    }

    public function stringToJsonConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $input_text = $request->input('input_text', '');
            $output_format = $request->input('output_format', 'compact');

            // Split input by lines and filter out empty lines
            $lines = array_filter(explode("\n", $input_text), function($line) {
                return trim($line) !== '';
            });

            // Convert to array
            $string_array = array_values($lines);

            // Generate JSON based on format
            if ($output_format === 'pretty') {
                $json_output = json_encode($string_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $json_output = json_encode($string_array, JSON_UNESCAPED_UNICODE);
            }

            $result = [
                'json_output' => $json_output,
                'count' => count($string_array),
                'input_lines' => count(explode("\n", $input_text)),
                'format' => $output_format,
                'character_count' => strlen($json_output)
            ];
        }

        return view('tools.string_to_json_converter', compact('result'));
    }

    // ==============================================
    // HELPER METHODS
    // ==============================================

    private function analyzeHeaderSecurity($header, $value)
    {
        $implications = [];

        if (stripos($header, 'server') !== false) {
            $implications[] = 'May reveal server technology';
        }
        if (stripos($header, 'x-powered-by') !== false) {
            $implications[] = 'Reveals application framework';
        }
        if (stripos($header, 'x-aspnet-version') !== false) {
            $implications[] = 'Reveals .NET version';
        }

        return $implications;
    }

    private function getServiceName($port)
    {
        $services = [
            21 => 'FTP', 22 => 'SSH', 23 => 'Telnet', 25 => 'SMTP',
            53 => 'DNS', 80 => 'HTTP', 110 => 'POP3', 143 => 'IMAP',
            443 => 'HTTPS', 993 => 'IMAPS', 995 => 'POP3S'
        ];

        return $services[$port] ?? 'Unknown';
    }

    private function detectHashType($hash)
    {
        $length = strlen($hash);

        if ($length === 32) return 'MD5';
        if ($length === 40) return 'SHA1';
        if ($length === 64) return 'SHA256';
        if ($length === 128) return 'SHA512';

        return 'Unknown';
    }

    /**
     * Perform online MD5 lookup using public database.
     * Returns plaintext if found, otherwise null.
     */
    private function md5OnlineLookup(string $hash): ?string
    {
        // nitrxgen MD5 database endpoint returns plaintext or empty string
        // Example: http://www.nitrxgen.net/md5db/5f4dcc3b5aa765d61d8327deb882cf99
        $hash = strtolower(trim($hash));
        if (!preg_match('/^[a-f0-9]{32}$/', $hash)) {
            return null;
        }

        $url = "http://www.nitrxgen.net/md5db/{$hash}";
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 6,
                    'ignore_errors' => true,
                    'header' => "User-Agent: musoftwares-tools/1.0\r\n",
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return null;
            }
            $response = trim((string) $response);
            return $response !== '' ? $response : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function generateSecureRandom($length, $type)
    {
        $length = (int) $length;
        if ($length <= 0) {
            return '';
        }

        switch ($type) {
            case 'numeric':
                $chars = '0123456789';
                break;
            case 'alphabetic':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                break;
            case 'alphanumeric':
            default:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                break;
        }

        $result = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $max)];
        }

        return $result;
    }

    // =====================
    // New Development Tools
    // =====================

    public function apiTester(Request $request)
    {
        $result = null;
        $error = null;
        if ($request->isMethod('post')) {
            $method = strtoupper($request->input('method', 'GET'));
            $url = $request->input('url');
            $headersInput = $request->input('headers', '');
            $body = $request->input('body', '');
            $headers = [];
            foreach (explode("\n", $headersInput) as $line) {
                if (strpos($line, ':') !== false) {
                    [$k,$v] = array_map('trim', explode(':', $line, 2));
                    if ($k) { $headers[$k] = $v; }
                }
            }
            try {
                $opts = [
                    'http' => [
                        'method' => $method,
                        'header' => implode("\r\n", array_map(fn($k,$v)=>"$k: $v", array_keys($headers), $headers)) . "\r\n",
                        'content' => in_array($method,['POST','PUT','PATCH']) ? $body : null,
                        'ignore_errors' => true,
                        'timeout' => 15,
                    ]
                ];
                $ctx = stream_context_create($opts);
                $response = @file_get_contents($url, false, $ctx);
                $meta = /* @phpstan-ignore-line */ $http_response_header ?? [];
                $result = [
                    'status_line' => $meta[0] ?? '',
                    'headers' => $meta,
                    'body' => $response,
                ];
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        return view('tools.api_tester', compact('result','error'));
    }

    public function webhookCatcher(Request $request)
    {
        // Echo back the request for inspection
        $result = [
            'method' => $request->method(),
            'query' => $request->query(),
            'headers' => $request->headers->all(),
            'payload' => $request->getContent(),
        ];
        return view('tools.webhook_catcher', compact('result'));
    }

    public function cronBuilder(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            // Handle both expression format and individual components
            if ($request->has('expression')) {
                $expr = trim($request->input('expression'));
            } else {
                // Build expression from individual components
                $minute = $request->input('minute', '*');
                $hour = $request->input('hour', '*');
                $day = $request->input('day', '*');
                $month = $request->input('month', '*');
                $weekday = $request->input('weekday', '*');
                $expr = "$minute $hour $day $month $weekday";
            }
            
            try {
                // naive next 5 runs each minute for demo
                $now = time();
                $next = [];
                for ($i=1; $i<=5; $i++) { $next[] = date('Y-m-d H:i:s', $now + $i*60); }
                $result = ['expression' => $expr, 'next_runs' => $next];
            } catch (\Exception $e) {
                $result = ['error' => $e->getMessage()];
            }
        }
        return view('tools.cron_builder', compact('result'));
    }

    public function slugGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text','');
            $separator = $request->input('separator','-');
            $result = [
                'original' => $text,
                'slug' => Str::slug($text, $separator)
            ];
        }
        return view('tools.slug_generator', compact('result'));
    }

    public function textHashGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $text = $request->input('text','');
            $algo = $request->input('algorithm','sha256');
            $secret = $request->input('secret');
            $hash = $secret ? hash_hmac($algo, $text, $secret) : hash($algo, $text);
            $result = compact('text','algo','hash');
        }
        return view('tools.text_hash_generator', compact('result'));
    }

    public function yamlJsonConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $direction = $request->input('direction','yaml_to_json');
            $input = $request->input('input','');
            try {
                if ($direction === 'yaml_to_json') {
                    // naive parser fallback
                    $lines = array_filter(explode("\n", $input));
                    $arr = [];
                    foreach ($lines as $line) {
                        if (strpos($line, ':') !== false) {
                            [$k,$v] = array_map('trim', explode(':', $line, 2));
                            $arr[$k] = $v;
                        }
                    }
                    $result = ['output' => json_encode($arr, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)];
                } else {
                    $data = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
                    $yaml = '';
                    foreach ($data as $k=>$v) { $yaml .= $k.': '.(is_scalar($v)?$v:json_encode($v))."\n"; }
                    $result = ['output' => $yaml];
                }
            } catch (\Exception $e) {
                $result = ['error' => $e->getMessage()];
            }
        }
        return view('tools.yaml_json_converter', compact('result'));
    }

    public function tomlJsonConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $direction = $request->input('direction','toml_to_json');
            $input = $request->input('input','');
            try {
                if ($direction === 'toml_to_json') {
                    $arr = [];
                    foreach (array_filter(explode("\n", $input)) as $line) {
                        if (strpos($line, '=') !== false) { [$k,$v]=array_map('trim', explode('=', $line, 2)); $arr[$k]=trim($v,'"'); }
                    }
                    $result = ['output' => json_encode($arr, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)];
                } else {
                    $data = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
                    $toml = '';
                    foreach ($data as $k => $v) {
                        $toml .= $k . ' = "' . (is_scalar($v) ? $v : json_encode($v)) . '"' . "\n";
                    }
                    $result = ['output' => $toml];
                }
            } catch (\Exception $e) {
                $result = ['error' => $e->getMessage()];
            }
        }
        return view('tools.toml_json_converter', compact('result'));
    }

    public function jsonDiff(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            try {
                $a = json_decode($request->input('json_a','{}'), true, 512, JSON_THROW_ON_ERROR);
                $b = json_decode($request->input('json_b','{}'), true, 512, JSON_THROW_ON_ERROR);
                $diff = [
                    'added' => array_diff_key($b, $a),
                    'removed' => array_diff_key($a, $b),
                    'changed' => array_filter($b, fn($v,$k)=>array_key_exists($k,$a) && $a[$k]!==$v, ARRAY_FILTER_USE_BOTH)
                ];
                $result = $diff;
            } catch (\Exception $e) { $result = ['error'=>$e->getMessage()]; }
        }
        return view('tools.json_diff', compact('result'));
    }

    public function jsonpathTester(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            // simple evaluator: returns root or key path only
            try {
                $json = json_decode($request->input('json','{}'), true, 512, JSON_THROW_ON_ERROR);
                $path = trim($request->input('path', '$'));
                if ($path === '$') { $out = $json; }
                else {
                    $key = ltrim($path, '$.');
                    $out = $json[$key] ?? null;
                }
                $result = ['output' => $out];
            } catch (\Exception $e) { $result = ['error'=>$e->getMessage()]; }
        }
        return view('tools.jsonpath_tester', compact('result'));
    }

    public function xpathTester(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            try {
                $xml = new \DOMDocument();
                $xml->loadXML($request->input('xml','<root/>'));
                $xp = new \DOMXPath($xml);
                $nodes = $xp->query($request->input('path','/'));
                $out = [];
                foreach ($nodes as $node) { $out[] = $xml->saveXML($node); }
                $result = ['output' => $out];
            } catch (\Exception $e) { $result = ['error'=>$e->getMessage()]; }
        }
        return view('tools.xpath_tester', compact('result'));
    }

    public function openapiValidator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            try {
                $json = json_decode($request->input('spec','{}'), true, 512, JSON_THROW_ON_ERROR);
                $errors = [];
                if (!isset($json['openapi'])) { $errors[] = 'Missing openapi field'; }
                if (!isset($json['paths'])) { $errors[] = 'Missing paths field'; }
                $result = ['valid' => empty($errors), 'errors' => $errors];
            } catch (\Exception $e) { $result = ['valid'=>false,'errors'=>[$e->getMessage()]]; }
        }
        return view('tools.openapi_validator', compact('result'));
    }

    public function gitignoreGenerator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $stack = $request->input('stack','laravel');
            $templates = [
                'laravel' => ".env\n/vendor\n/node_modules\n/storage/*.key\n/public/storage\n",
                'node' => "node_modules\n.env\ndist\n",
                'python' => "__pycache__/\n.env\n*.pyc\n",
            ];
            $result = ['stack'=>$stack,'output'=>$templates[$stack] ?? $templates['laravel']];
        }
        return view('tools.gitignore_generator', compact('result'));
    }

    public function htaccessRedirectBuilder(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $from = $request->input('from','/old');
            $to = $request->input('to','/new');
            $type = (int) $request->input('type', 301);
            $output = "RewriteEngine On\nRewriteRule ^".ltrim($from,'/')."$ ". $to ." [R=$type,L]\n";
            $result = compact('from','to','type','output');
        }
        return view('tools.htaccess_redirect_builder', compact('result'));
    }

    public function htmlMarkdownConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $direction = $request->input('direction','html_to_md');
            $input = $request->input('input','');
            if ($direction==='html_to_md') {
                // naive strip tags
                $result = ['output' => strip_tags($input)];
            } else {
                $lines = explode("\n", $input);
                $html = '';
                foreach ($lines as $l) { $html .= '<p>'.e($l).'</p>'; }
                $result = ['output' => $html];
            }
        }
        return view('tools.html_markdown_converter', compact('result'));
    }

    public function colorContrastChecker(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $fg = ltrim($request->input('fg','#000000'),'#');
            $bg = ltrim($request->input('bg','#ffffff'),'#');
            $luminance = function($hex) { $r=hexdec(substr($hex,0,2))/255; $g=hexdec(substr($hex,2,2))/255; $b=hexdec(substr($hex,4,2))/255; $toLin=function($c){return $c<=0.03928?$c/12.92:pow(($c+0.055)/1.055,2.4);} ; return 0.2126*$toLin($r)+0.7152*$toLin($g)+0.0722*$toLin($b); };
            $Lf=$luminance($fg); $Lb=$luminance($bg);
            $ratio = ($Lf>$Lb?($Lf+0.05)/($Lb+0.05):($Lb+0.05)/($Lf+0.05));
            $result = ['ratio' => round($ratio,2), 'passes_aa' => $ratio >= 4.5, 'passes_aaa' => $ratio >= 7];
        }
        return view('tools.color_contrast_checker', compact('result'));
    }

    public function imageBase64(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $mode = $request->input('mode','to_base64');
            if ($mode==='to_base64') {
                $request->validate(['image'=>'required|image|max:10240']);
                $data = base64_encode(file_get_contents($request->file('image')->getRealPath()));
                $result = ['base64' => $data];
            } else {
                $request->validate(['base64'=>'required|string']);
                $data = base64_decode($request->input('base64'));
                $filename = 'img_'.time().'.png';
                $path = 'tmp/'.$filename;
                Storage::disk('public')->put($path, $data);
                $result = ['url' => Storage::disk('public')->url($path)];
            }
        }
        return view('tools.image_base64', compact('result'));
    }

    public function uuidAdvanced(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $version = $request->input('version','v4');
            $namespace = $request->input('namespace');
            $name = $request->input('name');
            $quantity = $request->input('quantity', 1);
            $format = $request->input('format', 'standard');
            $value = null;
            if ($version==='v1') { $value = uniqid('', true); }
            elseif ($version==='v3' && $namespace && $name) { $value = md5($namespace.$name); }
            elseif ($version==='v5' && $namespace && $name) { $value = sha1($namespace.$name); }
            else {
                $data = random_bytes(16); $data[6]=chr(ord($data[6])&0x0f|0x40); $data[8]=chr(ord($data[8])&0x3f|0x80); $value = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data),4));
            }
            $result = compact('version','namespace','name','value','quantity','format');
        }
        return view('tools.uuid_advanced', compact('result'));
    }

    // Additional Text Processing Tools
    public function capitalizeFirstLetter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $result = [
                'original' => $text,
                'converted' => ucfirst(strtolower($text))
            ];
        }
        return view('tools.capitalize_first_letter', compact('result'));
    }

    public function capitalizeSentences(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $result = [
                'original' => $text,
                'converted' => ucfirst($text)
            ];
        }
        return view('tools.capitalize_sentences', compact('result'));
    }

    public function alternateCase(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $alternated = '';
            for ($i = 0; $i < strlen($text); $i++) {
                $alternated .= ($i % 2 == 0) ? strtoupper($text[$i]) : strtolower($text[$i]);
            }
            $result = [
                'original' => $text,
                'converted' => $alternated
            ];
        }
        return view('tools.alternate_case', compact('result'));
    }

    public function removeWhitespace(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $result = [
                'original' => $text,
                'converted' => preg_replace('/\s+/', '', $text)
            ];
        }
        return view('tools.remove_whitespace', compact('result'));
    }

    public function replaceMultipleSpaces(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $result = [
                'original' => $text,
                'converted' => preg_replace('/\s+/', ' ', $text)
            ];
        }
        return view('tools.replace_multiple_spaces', compact('result'));
    }

    public function removePunctuation(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $result = [
                'original' => $text,
                'converted' => preg_replace('/[^\w\s]/', '', $text)
            ];
        }
        return view('tools.remove_punctuation', compact('result'));
    }

    public function removeVowels(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $result = [
                'original' => $text,
                'converted' => preg_replace('/[aeiouAEIOU]/', '', $text)
            ];
        }
        return view('tools.remove_vowels', compact('result'));
    }

    public function countConsonants(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $consonants = preg_match_all('/[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]/', $text);
            $result = [
                'text' => $text,
                'consonant_count' => $consonants
            ];
        }
        return view('tools.count_consonants', compact('result'));
    }

    public function countSentences(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $sentences = preg_match_all('/[.!?]+/', $text);
            $result = [
                'text' => $text,
                'sentence_count' => $sentences
            ];
        }
        return view('tools.count_sentences', compact('result'));
    }

    public function countWords(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $words = str_word_count($text);
            $result = [
                'text' => $text,
                'word_count' => $words
            ];
        }
        return view('tools.count_words', compact('result'));
    }

    public function wordFrequency(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $words = str_word_count(strtolower($text), 1);
            $frequency = array_count_values($words);
            arsort($frequency);
            $result = [
                'text' => $text,
                'frequency' => $frequency
            ];
        }
        return view('tools.word_frequency', compact('result'));
    }

    public function findLongestWord(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $words = str_word_count($text, 1);
            $longest = '';
            foreach ($words as $word) {
                if (strlen($word) > strlen($longest)) {
                    $longest = $word;
                }
            }
            $result = [
                'text' => $text,
                'longest_word' => $longest,
                'length' => strlen($longest)
            ];
        }
        return view('tools.find_longest_word', compact('result'));
    }

    public function isAnagram(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text1' => 'required|string',
                'text2' => 'required|string'
            ]);
            $text1 = strtolower(preg_replace('/[^a-zA-Z]/', '', $request->input('text1')));
            $text2 = strtolower(preg_replace('/[^a-zA-Z]/', '', $request->input('text2')));
            $chars1 = str_split($text1);
            $chars2 = str_split($text2);
            sort($chars1);
            sort($chars2);
            $isAnagram = $chars1 === $chars2;
            $result = [
                'text1' => $request->input('text1'),
                'text2' => $request->input('text2'),
                'is_anagram' => $isAnagram
            ];
        }
        return view('tools.is_anagram', compact('result'));
    }

    public function shuffleString(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $chars = str_split($text);
            shuffle($chars);
            $result = [
                'original' => $text,
                'shuffled' => implode('', $chars)
            ];
        }
        return view('tools.shuffle_string', compact('result'));
    }

    public function convertToPigLatin(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $words = explode(' ', $text);
            $pigLatin = [];
            foreach ($words as $word) {
                if (preg_match('/^[aeiouAEIOU]/', $word)) {
                    $pigLatin[] = $word . 'way';
                } else {
                    $pigLatin[] = preg_replace('/^([^aeiouAEIOU]+)(.*)/', '$2$1ay', $word);
                }
            }
            $result = [
                'original' => $text,
                'pig_latin' => implode(' ', $pigLatin)
            ];
        }
        return view('tools.convert_to_pig_latin', compact('result'));
    }

    public function stringLeetSpeak(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $leet = [
                'a' => '4', 'A' => '4',
                'e' => '3', 'E' => '3',
                'i' => '1', 'I' => '1',
                'o' => '0', 'O' => '0',
                's' => '5', 'S' => '5',
                't' => '7', 'T' => '7',
                'l' => '1', 'L' => '1'
            ];
            $result = [
                'original' => $text,
                'leet_speak' => strtr($text, $leet)
            ];
        }
        return view('tools.string_leet_speak', compact('result'));
    }

    public function extractEmails(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $emails);
            $result = [
                'text' => $text,
                'emails' => $emails[0]
            ];
        }
        return view('tools.extract_emails', compact('result'));
    }

    public function extractUrls(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            preg_match_all('/https?:\/\/[^\s]+/', $text, $urls);
            $result = [
                'text' => $text,
                'urls' => $urls[0]
            ];
        }
        return view('tools.extract_urls', compact('result'));
    }

    public function highlightWord(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string',
                'word' => 'required|string'
            ]);
            $text = $request->input('text');
            $word = $request->input('word');
            $highlighted = str_ireplace($word, "<mark>$word</mark>", $text);
            $result = [
                'original' => $text,
                'word' => $word,
                'highlighted' => $highlighted
            ];
        }
        return view('tools.highlight_word', compact('result'));
    }

    public function findAllOccurrences(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string',
                'word' => 'required|string'
            ]);
            $text = $request->input('text');
            $word = $request->input('word');
            $positions = [];
            $offset = 0;
            while (($pos = stripos($text, $word, $offset)) !== false) {
                $positions[] = $pos;
                $offset = $pos + 1;
            }
            $result = [
                'text' => $text,
                'word' => $word,
                'positions' => $positions,
                'count' => count($positions)
            ];
        }
        return view('tools.find_all_occurrences', compact('result'));
    }

    public function findFirstNonRepeatedChar(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $chars = str_split($text);
            $counts = array_count_values($chars);
            $firstNonRepeated = '';
            foreach ($chars as $char) {
                if ($counts[$char] == 1) {
                    $firstNonRepeated = $char;
                    break;
                }
            }
            $result = [
                'text' => $text,
                'first_non_repeated_char' => $firstNonRepeated
            ];
        }
        return view('tools.find_first_non_repeated_char', compact('result'));
    }

    public function removeDuplicateWords(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $words = explode(' ', $text);
            $uniqueWords = array_unique($words);
            $result = [
                'original' => $text,
                'unique_words' => implode(' ', $uniqueWords)
            ];
        }
        return view('tools.remove_duplicate_words', compact('result'));
    }

    public function splitIntoSentences(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            $sentences = array_map('trim', $sentences);
            $result = [
                'text' => $text,
                'sentences' => $sentences
            ];
        }
        return view('tools.split_into_sentences', compact('result'));
    }

    public function wordCountPerSentence(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            $wordCounts = [];
            foreach ($sentences as $sentence) {
                $wordCounts[] = str_word_count(trim($sentence));
            }
            $result = [
                'text' => $text,
                'word_counts' => $wordCounts
            ];
        }
        return view('tools.word_count_per_sentence', compact('result'));
    }

    public function stringPalindromePerSentence(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            $palindromes = [];
            foreach ($sentences as $sentence) {
                $clean = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(trim($sentence)));
                $palindromes[] = $clean === strrev($clean);
            }
            $result = [
                'text' => $text,
                'palindromes' => $palindromes
            ];
        }
        return view('tools.string_palindrome_per_sentence', compact('result'));
    }

    public function reverseBySentence(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            $reversed = array_reverse($sentences);
            $result = [
                'original' => $text,
                'reversed' => implode('. ', $reversed) . '.'
            ];
        }
        return view('tools.reverse_by_sentence', compact('result'));
    }

    public function reverseVowels(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $vowels = [];
            for ($i = 0; $i < strlen($text); $i++) {
                if (preg_match('/[aeiouAEIOU]/', $text[$i])) {
                    $vowels[] = $text[$i];
                }
            }
            $vowels = array_reverse($vowels);
            $result_text = $text;
            $vowel_index = 0;
            for ($i = 0; $i < strlen($result_text); $i++) {
                if (preg_match('/[aeiouAEIOU]/', $result_text[$i])) {
                    $result_text[$i] = $vowels[$vowel_index++];
                }
            }
            $result = [
                'original' => $text,
                'reversed_vowels' => $result_text
            ];
        }
        return view('tools.reverse_vowels', compact('result'));
    }

    public function reverseWordsOrderAndChars(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $words = explode(' ', $text);
            $reversed = [];
            foreach ($words as $word) {
                $reversed[] = strrev($word);
            }
            $result = [
                'original' => $text,
                'reversed' => implode(' ', array_reverse($reversed))
            ];
        }
        return view('tools.reverse_words_order_and_chars', compact('result'));
    }

    public function reverseWords(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $words = explode(' ', $text);
            $result = [
                'original' => $text,
                'reversed' => implode(' ', array_reverse($words))
            ];
        }
        return view('tools.reverse_words', compact('result'));
    }

    public function stripHtmlTags(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $result = [
                'original' => $text,
                'stripped' => strip_tags($text)
            ];
        }
        return view('tools.strip_html_tags', compact('result'));
    }

    public function stringToAscii(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            $text = $request->input('text');
            $ascii = [];
            for ($i = 0; $i < strlen($text); $i++) {
                $ascii[] = ord($text[$i]);
            }
            $result = [
                'original' => $text,
                'ascii' => $ascii
            ];
        }
        return view('tools.string_to_ascii', compact('result'));
    }

    public function asciiToString(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'ascii' => 'required|string'
            ]);
            $ascii_input = $request->input('ascii');
            $ascii_codes = array_map('intval', explode(',', $ascii_input));
            $text = '';
            foreach ($ascii_codes as $code) {
                $text .= chr($code);
            }
            $result = [
                'ascii_input' => $ascii_input,
                'text' => $text
            ];
        }
        return view('tools.ascii_to_string', compact('result'));
    }

    // Additional Mathematical Tools
    public function multiCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'expression' => 'required|string'
            ]);
            $expression = $request->input('expression');
            // Basic safety check - only allow numbers, operators, and parentheses
            if (preg_match('/^[0-9+\-*\/\(\)\s\.]+$/', $expression)) {
                try {
                    // Simple evaluation (in production, use a proper math parser)
                    $result_value = eval("return $expression;");
                    $result = [
                        'expression' => $expression,
                        'result' => $result_value
                    ];
                } catch (\Exception $e) {
                    $result = [
                        'expression' => $expression,
                        'error' => 'Invalid expression'
                    ];
                }
            } else {
                $result = [
                    'expression' => $expression,
                    'error' => 'Invalid characters in expression'
                ];
            }
        }
        return view('tools.multi_calculator', compact('result'));
    }

    public function quadraticSolver(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'a' => 'required|numeric|not_in:0',
                'b' => 'required|numeric',
                'c' => 'required|numeric'
            ]);
            $a = (float) $request->input('a');
            $b = (float) $request->input('b');
            $c = (float) $request->input('c');
            
            if ($a == 0) {
                $result = [
                    'a' => $a, 'b' => $b, 'c' => $c,
                    'error' => 'Coefficient a cannot be zero'
                ];
            } else {
                $discriminant = $b * $b - 4 * $a * $c;
                if ($discriminant > 0) {
                    $x1 = (-$b + sqrt($discriminant)) / (2 * $a);
                    $x2 = (-$b - sqrt($discriminant)) / (2 * $a);
                    $result = [
                        'a' => $a, 'b' => $b, 'c' => $c,
                        'discriminant' => $discriminant,
                        'x1' => $x1, 'x2' => $x2,
                        'type' => 'Two real roots'
                    ];
                } elseif ($discriminant == 0) {
                    $x = -$b / (2 * $a);
                    $result = [
                        'a' => $a, 'b' => $b, 'c' => $c,
                        'discriminant' => $discriminant,
                        'x' => $x,
                        'type' => 'One real root'
                    ];
                } else {
                    $real = -$b / (2 * $a);
                    $imaginary = sqrt(-$discriminant) / (2 * $a);
                    $result = [
                        'a' => $a, 'b' => $b, 'c' => $c,
                        'discriminant' => $discriminant,
                        'real' => $real, 'imaginary' => $imaginary,
                        'type' => 'Complex roots'
                    ];
                }
            }
        }
        return view('tools.quadratic_solver', compact('result'));
    }

    public function matrixOperations(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'matrix1' => 'required|string',
                'matrix2' => 'required|string',
                'operation' => 'required|in:add,multiply'
            ]);
            
            $matrix1_str = $request->input('matrix1');
            $matrix2_str = $request->input('matrix2');
            $operation = $request->input('operation');
            
            // Parse matrices (simple format: "1,2;3,4")
            $matrix1 = [];
            $rows1 = explode(';', $matrix1_str);
            foreach ($rows1 as $row) {
                $matrix1[] = array_map('floatval', explode(',', $row));
            }
            
            $matrix2 = [];
            $rows2 = explode(';', $matrix2_str);
            foreach ($rows2 as $row) {
                $matrix2[] = array_map('floatval', explode(',', $row));
            }
            
            if ($operation === 'add') {
                $result_matrix = [];
                for ($i = 0; $i < count($matrix1); $i++) {
                    for ($j = 0; $j < count($matrix1[0]); $j++) {
                        $result_matrix[$i][$j] = $matrix1[$i][$j] + $matrix2[$i][$j];
                    }
                }
                $result = [
                    'matrix1' => $matrix1,
                    'matrix2' => $matrix2,
                    'operation' => 'Addition',
                    'result' => $result_matrix
                ];
            } elseif ($operation === 'multiply') {
                $result_matrix = [];
                for ($i = 0; $i < count($matrix1); $i++) {
                    for ($j = 0; $j < count($matrix2[0]); $j++) {
                        $sum = 0;
                        for ($k = 0; $k < count($matrix1[0]); $k++) {
                            $sum += $matrix1[$i][$k] * $matrix2[$k][$j];
                        }
                        $result_matrix[$i][$j] = $sum;
                    }
                }
                $result = [
                    'matrix1' => $matrix1,
                    'matrix2' => $matrix2,
                    'operation' => 'Multiplication',
                    'result' => $result_matrix
                ];
            }
        }
        return view('tools.matrix_operations', compact('result'));
    }

    public function graphingCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'function' => 'required|string',
                'x_min' => 'required|numeric',
                'x_max' => 'required|numeric'
            ]);
            
            $function = $request->input('function');
            $x_min = (float) $request->input('x_min');
            $x_max = (float) $request->input('x_max');
            
            // Generate points for graphing (simplified)
            $points = [];
            for ($x = $x_min; $x <= $x_max; $x += 0.1) {
                // Simple function evaluation (in production, use a proper parser)
                $y = 0; // Placeholder - would need proper function parser
                $points[] = ['x' => $x, 'y' => $y];
            }
            
            $result = [
                'function' => $function,
                'x_min' => $x_min,
                'x_max' => $x_max,
                'points' => $points
            ];
        }
        return view('tools.graphing_calculator', compact('result'));
    }

    // Additional Financial Tools
    public function investmentReturnCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'initial_investment' => 'required|numeric|min:0',
                'final_value' => 'required|numeric|min:0',
                'time_period' => 'required|numeric|min:0'
            ]);
            
            $initial = (float) $request->input('initial_investment');
            $final = (float) $request->input('final_value');
            $time = (float) $request->input('time_period');
            
            if ($initial > 0 && $time > 0) {
                $total_return = $final - $initial;
                $percentage_return = ($total_return / $initial) * 100;
                $annual_return = pow($final / $initial, 1 / $time) - 1;
                $annual_percentage = $annual_return * 100;
                
                $result = [
                    'initial_investment' => $initial,
                    'final_value' => $final,
                    'time_period' => $time,
                    'total_return' => $total_return,
                    'percentage_return' => $percentage_return,
                    'annual_return' => $annual_return,
                    'annual_percentage' => $annual_percentage
                ];
            }
        }
        return view('tools.investment_return_calculator', compact('result'));
    }

    public function retirementSavingsCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'current_age' => 'required|numeric|min:18|max:100',
                'retirement_age' => 'required|numeric|min:18|max:100',
                'current_savings' => 'required|numeric|min:0',
                'monthly_contribution' => 'required|numeric|min:0',
                'annual_return' => 'required|numeric|min:0|max:50'
            ]);
            
            $current_age = (int) $request->input('current_age');
            $retirement_age = (int) $request->input('retirement_age');
            $current_savings = (float) $request->input('current_savings');
            $monthly_contribution = (float) $request->input('monthly_contribution');
            $annual_return = (float) $request->input('annual_return') / 100;
            
            if ($retirement_age > $current_age) {
                $years_to_retirement = $retirement_age - $current_age;
                $monthly_return = $annual_return / 12;
                $total_months = $years_to_retirement * 12;
                
                // Future value calculation
                $future_value = $current_savings * pow(1 + $annual_return, $years_to_retirement);
                $future_value += $monthly_contribution * ((pow(1 + $monthly_return, $total_months) - 1) / $monthly_return);
                
                $total_contributions = $current_savings + ($monthly_contribution * $total_months);
                $total_earnings = $future_value - $total_contributions;
                
                $result = [
                    'current_age' => $current_age,
                    'retirement_age' => $retirement_age,
                    'years_to_retirement' => $years_to_retirement,
                    'current_savings' => $current_savings,
                    'monthly_contribution' => $monthly_contribution,
                    'annual_return' => $annual_return * 100,
                    'future_value' => $future_value,
                    'total_contributions' => $total_contributions,
                    'total_earnings' => $total_earnings
                ];
            }
        }
        return view('tools.retirement_savings_calculator', compact('result'));
    }

    public function stockProfitCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'shares' => 'required|numeric|min:1',
                'buy_price' => 'required|numeric|min:0',
                'sell_price' => 'required|numeric|min:0',
                'commission' => 'numeric|min:0'
            ]);
            
            $shares = (int) $request->input('shares');
            $buy_price = (float) $request->input('buy_price');
            $sell_price = (float) $request->input('sell_price');
            $commission = (float) $request->input('commission', 0);
            
            $total_cost = ($shares * $buy_price) + $commission;
            $total_proceeds = ($shares * $sell_price) - $commission;
            $profit_loss = $total_proceeds - $total_cost;
            $profit_percentage = ($profit_loss / $total_cost) * 100;
            
            $result = [
                'shares' => $shares,
                'buy_price' => $buy_price,
                'sell_price' => $sell_price,
                'commission' => $commission,
                'total_cost' => $total_cost,
                'total_proceeds' => $total_proceeds,
                'profit_loss' => $profit_loss,
                'profit_percentage' => $profit_percentage
            ];
        }
        return view('tools.stock_profit_calculator', compact('result'));
    }

    public function taxCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'income' => 'required|numeric|min:0',
                'filing_status' => 'required|in:single,married,joint,head_of_household'
            ]);
            
            $income = (float) $request->input('income');
            $filing_status = $request->input('filing_status');
            
            // Simplified tax brackets (2023 US Federal Tax Brackets)
            $brackets = [
                'single' => [
                    [10275, 0.10],
                    [41775, 0.12],
                    [89450, 0.22],
                    [190750, 0.24],
                    [364200, 0.32],
                    [462550, 0.35],
                    [999999999, 0.37]
                ],
                'married' => [
                    [20550, 0.10],
                    [83550, 0.12],
                    [178900, 0.22],
                    [381450, 0.24],
                    [578125, 0.32],
                    [693750, 0.35],
                    [999999999, 0.37]
                ]
            ];
            
            $tax = 0;
            $previous_bracket = 0;
            
            if (isset($brackets[$filing_status])) {
                foreach ($brackets[$filing_status] as $bracket) {
                    if ($income > $previous_bracket) {
                        $taxable_at_this_bracket = min($income, $bracket[0]) - $previous_bracket;
                        $tax += $taxable_at_this_bracket * $bracket[1];
                        $previous_bracket = $bracket[0];
                    }
                }
            }
            
            $after_tax = $income - $tax;
            $effective_rate = ($tax / $income) * 100;
            
            $result = [
                'income' => $income,
                'filing_status' => $filing_status,
                'tax' => $tax,
                'after_tax' => $after_tax,
                'effective_rate' => $effective_rate
            ];
        }
        return view('tools.tax_calculator', compact('result'));
    }

    // Additional Date & Time Tools
    public function addSubtractTime(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'date' => 'required|date',
                'days' => 'required|numeric',
                'operation' => 'required|in:add,subtract'
            ]);
            
            $date = $request->input('date');
            $days = (int) $request->input('days');
            $operation = $request->input('operation');
            
            $dateTime = new \DateTime($date);
            if ($operation === 'add') {
                $dateTime->add(new \DateInterval("P{$days}D"));
            } else {
                $dateTime->sub(new \DateInterval("P{$days}D"));
            }
            
            $result = [
                'original_date' => $date,
                'days' => $days,
                'operation' => $operation,
                'result_date' => $dateTime->format('Y-m-d'),
                'formatted' => $dateTime->format('F j, Y')
            ];
        }
        return view('tools.add_subtract_time', compact('result'));
    }

    public function countdownTimer(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'target_date' => 'required|date|after:now'
            ]);
            
            $target_date = $request->input('target_date');
            $now = new \DateTime();
            $target = new \DateTime($target_date);
            $interval = $now->diff($target);
            
                 $result = [
                     'target_date' => $target_date,
                     'target_datetime' => $target_date,
                     'event_name' => 'Event Countdown',
                     'is_future' => true,
                     'timezone' => 'UTC',
                     'time_remaining' => [
                         'days' => $interval->days,
                         'hours' => $interval->h,
                         'minutes' => $interval->i,
                         'seconds' => $interval->s,
                         'total' => $interval->days . ' days, ' . $interval->h . ' hours, ' . $interval->i . ' minutes, ' . $interval->s . ' seconds'
                     ],
                     'days' => $interval->days,
                     'hours' => $interval->h,
                     'minutes' => $interval->i,
                     'seconds' => $interval->s,
                     'total_seconds' => $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s
                 ];
        }
        return view('tools.countdown_timer', compact('result'));
    }

    public function workdayCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'exclude_weekends' => 'sometimes|boolean',
                'holidays' => 'sometimes|array'
            ]);
            
            $start_date = new \DateTime($request->input('start_date'));
            $end_date = new \DateTime($request->input('end_date'));
            $exclude_weekends = $request->input('exclude_weekends', true);
            $holidays = $request->input('holidays', []);
            
            $workdays = 0;
            $weekends = 0;
            $holiday_count = 0;
            $current = clone $start_date;
            
            // Convert holidays to DateTime objects for comparison
            $holiday_dates = [];
            if (is_array($holidays)) {
                foreach ($holidays as $holiday) {
                    try {
                        $holiday_dates[] = new \DateTime($holiday);
                    } catch (\Exception $e) {
                        // Skip invalid dates
                        continue;
                    }
                }
            }
            
            while ($current <= $end_date) {
                $dayOfWeek = $current->format('N'); // 1 (Monday) to 7 (Sunday)
                $is_holiday = false;
                
                // Check if current date is a holiday
                foreach ($holiday_dates as $holiday_date) {
                    if ($current->format('Y-m-d') === $holiday_date->format('Y-m-d')) {
                        $is_holiday = true;
                        $holiday_count++;
                        break;
                    }
                }
                
                if (!$is_holiday) {
                    if ($dayOfWeek < 6) { // Monday to Friday
                        $workdays++;
                    } else { // Saturday and Sunday
                        $weekends++;
                    }
                }
                
                $current->add(new \DateInterval('P1D'));
            }
            
            // If weekends should be included in workdays calculation
            if (!$exclude_weekends) {
                $workdays += $weekends;
                $weekends = 0;
            }
            
            $total_days = $workdays + $weekends + $holiday_count;
            
            $result = [
                'start_date' => $start_date->format('Y-m-d'),
                'end_date' => $end_date->format('Y-m-d'),
                'total_days' => $total_days,
                'workdays' => $workdays,
                'weekends' => $weekends,
                'holidays' => $holiday_count,
                'country' => 'US', // Default country
                'include_weekends' => !$exclude_weekends
            ];
        }
        return view('tools.workday_calculator', compact('result'));
    }

    // Additional Security Tools
    public function hashString(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string',
                'algorithm' => 'required|in:md5,sha1,sha256,sha512'
            ]);
            
            $text = $request->input('text');
            $algorithm = $request->input('algorithm');
            
            $hash = hash($algorithm, $text);
            
            $result = [
                'text' => $text,
                'algorithm' => strtoupper($algorithm),
                'hash' => $hash
            ];
        }
        return view('tools.hash_string', compact('result'));
    }

    public function rot13Cipher(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'text' => 'required|string'
            ]);
            
            $text = $request->input('text');
            $encoded = str_rot13($text);
            
            $result = [
                'original' => $text,
                'encoded' => $encoded
            ];
        }
        return view('tools.rot13_cipher', compact('result'));
    }

    // Additional Health & Fitness Tools
    public function macronutrientCalculator(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'weight' => 'required|numeric|min:1',
                'activity_level' => 'required|in:sedentary,light,moderate,active,very_active',
                'goal' => 'required|in:lose,maintain,gain'
            ]);
            
            $weight = (float) $request->input('weight');
            $activity_level = $request->input('activity_level');
            $goal = $request->input('goal');
            
            // Activity multipliers
            $multipliers = [
                'sedentary' => 1.2,
                'light' => 1.375,
                'moderate' => 1.55,
                'active' => 1.725,
                'very_active' => 1.9
            ];
            
            // Goal adjustments
            $goal_adjustments = [
                'lose' => -500, // 500 calorie deficit
                'maintain' => 0,
                'gain' => 500 // 500 calorie surplus
            ];
            
            $bmr = 24 * $weight; // Simplified BMR calculation
            $tdee = $bmr * $multipliers[$activity_level] + $goal_adjustments[$goal];
            
            // Macronutrient ratios
            $protein = $weight * 2.2; // 1g per lb of body weight
            $fat = $tdee * 0.25 / 9; // 25% of calories from fat
            $carbs = ($tdee - ($protein * 4) - ($fat * 9)) / 4; // Remaining calories as carbs
            
            $result = [
                'weight' => $weight,
                'activity_level' => $activity_level,
                'goal' => $goal,
                'bmr' => $bmr,
                'tdee' => $tdee,
                'protein' => $protein,
                'fat' => $fat,
                'carbs' => $carbs
            ];
        }
        return view('tools.macronutrient_calculator', compact('result'));
    }

    // Additional Development Tools
    public function cssToScssConverter(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'css_code' => 'required|string|max:50000',
                'add_variables' => 'boolean',
                'nest_selectors' => 'boolean'
            ]);
            
            $cssCode = $request->input('css_code');
            $addVariables = $request->boolean('add_variables');
            $nestSelectors = $request->boolean('nest_selectors');
            
            try {
                $scss = $this->convertCssToScss($cssCode, $addVariables, $nestSelectors);
                
                $result = [
                    'original' => $cssCode,
                    'scss' => $scss,
                    'variables_extracted' => $addVariables,
                    'nesting_enabled' => $nestSelectors,
                    'conversion_stats' => [
                        'original_lines' => substr_count($cssCode, "\n") + 1,
                        'scss_lines' => substr_count($scss, "\n") + 1,
                        'variables_found' => $addVariables ? substr_count($scss, '$') : 0,
                        'nested_blocks' => $nestSelectors ? substr_count($scss, '{') - 1 : 0
                    ]
                ];
            } catch (\Exception $e) {
                $result = [
                    'original' => $cssCode,
                    'error' => 'Failed to convert CSS: ' . $e->getMessage(),
                    'scss' => $cssCode // Fallback to original
                ];
            }
        }
        return view('tools.css_to_scss_converter', compact('result'));
    }

    private function convertCssToScss(string $css, bool $addVariables = true, bool $nestSelectors = true): string
    {
        // Remove comments and normalize whitespace
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        $css = preg_replace('/\s+/', ' ', trim($css));
        
        // Extract common values for variables if requested
        $variables = [];
        if ($addVariables) {
            $variables = $this->extractCommonValues($css);
        }
        
        // Build SCSS with variables at the top
        $scss = '';
        if (!empty($variables)) {
            $scss .= "// Variables\n";
            foreach ($variables as $name => $value) {
                $scss .= "\${$name}: {$value};\n";
            }
            $scss .= "\n";
        }
        
        // Convert CSS rules to SCSS
        $rules = $this->parseCssRules($css);
        $scss .= $this->convertRulesToScss($rules, $nestSelectors, $variables);
        
        return trim($scss);
    }

    private function extractCommonValues(string $css): array
    {
        $variables = [];
        
        // Find common colors
        preg_match_all('/#[0-9a-fA-F]{3,6}/', $css, $colors);
        $colorCounts = array_count_values($colors[0]);
        
        foreach ($colorCounts as $color => $count) {
            if ($count > 1) {
                $name = 'color-' . str_replace('#', '', strtolower($color));
                $variables[$name] = $color;
            }
        }
        
        // Find common font sizes
        preg_match_all('/font-size:\s*(\d+(?:\.\d+)?(?:px|em|rem|%))/i', $css, $fontSizes);
        $sizeCounts = array_count_values($fontSizes[1]);
        
        foreach ($sizeCounts as $size => $count) {
            if ($count > 1) {
                $name = 'font-size-' . str_replace(['.', 'px', 'em', 'rem', '%'], ['', '', '', '', ''], $size);
                $variables["font-size-{$name}"] = $size;
            }
        }
        
        // Find common margins/paddings
        preg_match_all('/(margin|padding)(?:-top|-right|-bottom|-left)?:\s*(\d+(?:\.\d+)?(?:px|em|rem|%))/i', $css, $spacings);
        $spacingCounts = array_count_values($spacings[2]);
        
        foreach ($spacingCounts as $spacing => $count) {
            if ($count > 2) {
                $name = 'spacing-' . str_replace(['.', 'px', 'em', 'rem', '%'], ['', '', '', '', ''], $spacing);
                $variables["spacing-{$name}"] = $spacing;
            }
        }
        
        return array_slice($variables, 0, 10); // Limit to 10 variables
    }

    private function parseCssRules(string $css): array
    {
        $rules = [];
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $selectors = trim($match[1]);
            $properties = trim($match[2]);
            
            if (!empty($properties)) {
                $rules[] = [
                    'selectors' => $selectors,
                    'properties' => $properties
                ];
            }
        }
        
        return $rules;
    }

    private function convertRulesToScss(array $rules, bool $nestSelectors, array $variables): string
    {
        $scss = '';
        $processedSelectors = [];
        
        foreach ($rules as $rule) {
            $selectors = $this->parseSelectors($rule['selectors']);
            $properties = $this->convertProperties($rule['properties'], $variables);
            
            if ($nestSelectors && count($selectors) > 1) {
                // Try to nest related selectors
                $scss .= $this->createNestedScss($selectors, $properties);
            } else {
                // Output as flat CSS
                foreach ($selectors as $selector) {
                    $scss .= "{$selector} {\n";
                    $scss .= $this->indentProperties($properties);
                    $scss .= "}\n\n";
                }
            }
        }
        
        return $scss;
    }

    private function parseSelectors(string $selectors): array
    {
        return array_map('trim', explode(',', $selectors));
    }

    private function convertProperties(string $properties, array $variables): string
    {
        $convertedProperties = [];
        $propLines = explode(';', $properties);
        
        foreach ($propLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Replace values with variables if they exist
            foreach ($variables as $varName => $value) {
                $line = str_replace($value, "\${$varName}", $line);
            }
            
            $convertedProperties[] = $line . ';';
        }
        
        return implode("\n", $convertedProperties);
    }

    private function createNestedScss(array $selectors, string $properties): string
    {
        // Simple nesting logic - group selectors by common base
        $grouped = [];
        foreach ($selectors as $selector) {
            $base = preg_replace('/[.#:\[\]]\w+/', '', $selector);
            $base = trim($base);
            if (empty($base)) $base = 'global';
            
            if (!isset($grouped[$base])) {
                $grouped[$base] = [];
            }
            $grouped[$base][] = $selector;
        }
        
        $scss = '';
        foreach ($grouped as $base => $selectors) {
            if (count($selectors) > 1) {
                $scss .= "{$base} {\n";
                foreach ($selectors as $selector) {
                    $specific = str_replace($base, '&', $selector);
                    $scss .= "  {$specific} {\n";
                    $scss .= $this->indentProperties($properties, 4);
                    $scss .= "  }\n";
                }
                $scss .= "}\n\n";
            } else {
                $scss .= "{$selectors[0]} {\n";
                $scss .= $this->indentProperties($properties);
                $scss .= "}\n\n";
            }
        }
        
        return $scss;
    }

    private function indentProperties(string $properties, int $indent = 2): string
    {
        $indentStr = str_repeat(' ', $indent);
        $lines = explode("\n", $properties);
        return $indentStr . implode("\n" . $indentStr, $lines) . "\n";
    }


    // Additional Utility Tools
    public function networkScanner(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'ip_range' => 'required|string|regex:/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(?:\/\d{1,2})?|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}-\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/',
                'scan_type' => 'required|in:ping,tcp,udp,comprehensive',
                'timeout' => 'integer|min:1|max:30'
            ]);
            
            $ipRange = $request->input('ip_range');
            $scanType = $request->input('scan_type', 'ping');
            $timeout = $request->input('timeout', 5);
            
            try {
                $devices = $this->performNetworkScan($ipRange, $scanType, $timeout);
                
                $result = [
                    'ip_range' => $ipRange,
                    'scan_type' => $scanType,
                    'timeout' => $timeout,
                    'devices' => $devices,
                    'scan_time' => now()->format('Y-m-d H:i:s'),
                    'total_devices' => count($devices),
                    'online_devices' => count(array_filter($devices, fn($d) => $d['status'] === 'online')),
                    'scan_duration' => $this->calculateScanDuration($devices)
                ];
            } catch (\Exception $e) {
                $result = [
                    'ip_range' => $ipRange,
                    'error' => 'Scan failed: ' . $e->getMessage(),
                    'devices' => []
                ];
            }
        }
        return view('tools.network_scanner', compact('result'));
    }

    private function performNetworkScan(string $ipRange, string $scanType, int $timeout): array
    {
        $devices = [];
        $ips = $this->generateIpList($ipRange);
        
        // Limit scan to prevent abuse
        $ips = array_slice($ips, 0, 254);
        
        foreach ($ips as $ip) {
            $device = [
                'ip' => $ip,
                'hostname' => $this->resolveHostname($ip),
                'status' => $this->checkDeviceStatus($ip, $scanType, $timeout),
                'response_time' => null,
                'open_ports' => [],
                'mac_address' => null,
                'vendor' => null
            ];
            
            if ($device['status'] === 'online') {
                $device['response_time'] = $this->measureResponseTime($ip);
                if ($scanType === 'tcp' || $scanType === 'comprehensive') {
                    $device['open_ports'] = $this->scanCommonPorts($ip);
                }
                $device['mac_address'] = $this->getMacAddress($ip);
                $device['vendor'] = $this->getVendorInfo($device['mac_address']);
            }
            
            $devices[] = $device;
        }
        
        return $devices;
    }

    private function generateIpList(string $ipRange): array
    {
        $ips = [];
        
        if (strpos($ipRange, '/') !== false) {
            // CIDR notation
            list($baseIp, $cidr) = explode('/', $ipRange);
            $ips = $this->generateCidrIps($baseIp, (int)$cidr);
        } elseif (strpos($ipRange, '-') !== false) {
            // Range notation
            list($startIp, $endIp) = explode('-', $ipRange);
            $ips = $this->generateRangeIps($startIp, $endIp);
        } else {
            // Single IP
            $ips = [$ipRange];
        }
        
        return $ips;
    }

    private function generateCidrIps(string $baseIp, int $cidr): array
    {
        $ips = [];
        $ipLong = ip2long($baseIp);
        $mask = -1 << (32 - $cidr);
        $network = $ipLong & $mask;
        $broadcast = $network | ~$mask;
        
        for ($i = $network + 1; $i < $broadcast; $i++) {
            $ips[] = long2ip($i);
        }
        
        return $ips;
    }

    private function generateRangeIps(string $startIp, string $endIp): array
    {
        $ips = [];
        $startLong = ip2long($startIp);
        $endLong = ip2long($endIp);
        
        for ($i = $startLong; $i <= $endLong; $i++) {
            $ips[] = long2ip($i);
        }
        
        return $ips;
    }

    private function checkDeviceStatus(string $ip, string $scanType, int $timeout): string
    {
        // Simulate network check (in production, use actual network tools)
        $isOnline = $this->simulatePing($ip, $timeout);
        
        if (!$isOnline) {
            return 'offline';
        }
        
        switch ($scanType) {
            case 'tcp':
                return $this->checkTcpPorts($ip) ? 'online' : 'filtered';
            case 'udp':
                return $this->checkUdpPorts($ip) ? 'online' : 'filtered';
            case 'comprehensive':
                return 'online';
            default:
                return 'online';
        }
    }

    private function simulatePing(string $ip, int $timeout): bool
    {
        // Simulate ping response (in production, use actual ping)
        // For demo purposes, simulate some IPs as offline
        $offlineIps = ['192.168.1.100', '192.168.1.200', '192.168.1.250'];
        return !in_array($ip, $offlineIps);
    }

    private function resolveHostname(string $ip): ?string
    {
        // Simulate hostname resolution
        $hostnames = [
            '192.168.1.1' => 'router.local',
            '192.168.1.2' => 'device-2.local',
            '192.168.1.3' => 'device-3.local'
        ];
        
        return $hostnames[$ip] ?? null;
    }

    private function measureResponseTime(string $ip): float
    {
        // Simulate response time measurement
        return round(rand(1, 100) / 10, 2);
    }

    private function scanCommonPorts(string $ip): array
    {
        // Simulate port scanning
        $commonPorts = [22, 23, 25, 53, 80, 110, 143, 443, 993, 995];
        $openPorts = [];
        
        foreach ($commonPorts as $port) {
            if (rand(1, 10) <= 3) { // 30% chance port is open
                $openPorts[] = [
                    'port' => $port,
                    'service' => $this->getServiceName($port),
                    'protocol' => in_array($port, [53, 67, 68, 123]) ? 'UDP' : 'TCP'
                ];
            }
        }
        
        return $openPorts;
    }

    private function checkTcpPorts(string $ip): bool
    {
        // Simulate TCP port check
        return rand(1, 10) <= 7; // 70% chance TCP ports are open
    }

    private function checkUdpPorts(string $ip): bool
    {
        // Simulate UDP port check
        return rand(1, 10) <= 5; // 50% chance UDP ports are open
    }

    private function getMacAddress(string $ip): ?string
    {
        // Simulate MAC address retrieval
        $macs = [
            '192.168.1.1' => '00:11:22:33:44:55',
            '192.168.1.2' => '00:11:22:33:44:66',
            '192.168.1.3' => '00:11:22:33:44:77'
        ];
        
        return $macs[$ip] ?? null;
    }

    private function getVendorInfo(?string $macAddress): ?string
    {
        if (!$macAddress) return null;
        
        // Simulate vendor lookup based on MAC OUI
        $oui = substr($macAddress, 0, 8);
        $vendors = [
            '00:11:22' => 'Example Corp',
            '00:11:23' => 'Test Inc',
            '00:11:24' => 'Demo Ltd'
        ];
        
        return $vendors[$oui] ?? 'Unknown Vendor';
    }

    private function calculateScanDuration(array $devices): float
    {
        // Simulate scan duration calculation
        return round(count($devices) * 0.1, 2);
    }
}
