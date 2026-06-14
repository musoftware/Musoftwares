<?php

namespace App\Services\Tools;

use App\Models\CurrenciesExchange;

class CalculationToolsService
{
    /**
     * Calculate InstaPay withdrawal amount
     */
    public function calculateInstapayWithdrawal(float $amount): float
    {
        $servicePrice = $amount;
        return round($servicePrice - 3 - ($servicePrice * 0.03)) - (min(20, max(0.5, $servicePrice * 0.005)));
    }

    /**
     * Calculate the amount that must be paid to receive a specific balance EGP
     * This is the reverse calculation of calculateInstapayWithdrawal
     */
    public function calculateAmountToPayForBalance(float $desiredBalance): float
    {
        // Start with the desired balance and work backwards
        $amount = $desiredBalance;

        // Add back the minimum fee (0.5 to 20 based on amount)
        $minFee = max(0.5, $amount * 0.005);
        $minFee = min(20, $minFee);
        $amount += $minFee;

        // Add back the 3 EGP fixed fee
        $amount += 3;

        // Add back the 3% fee
        $amount = $amount / (1 - 0.03);

        return round($amount, 2);
    }

    /**
     * Calculate USD payout amount
     */
    public function calculateUsdPayout(float $usdAmount): float
    {
        $totalCost = CurrenciesExchange::RateToday($usdAmount, 1, 2);
        $totalCost = round($totalCost / (1 - 0.01), 2);
        $totalCost = round($totalCost / (1 - 0.044), 2);
        $totalCost = round($totalCost / (1 - 0.05), 2);

        return round($totalCost / (1 - 0.0475), 2);
    }

    /**
     * Convert units between different measurement systems
     */
    public function convertUnits(float $value, string $fromUnit, string $toUnit): array
    {
        $category = $this->getUnitCategory($fromUnit);
        $toCategory = $this->getUnitCategory($toUnit);
        
        if ($category !== $toCategory) {
            return [
                'success' => false,
                'error' => "Cannot convert between different unit categories: {$category} and {$toCategory}",
                'value' => $value,
                'from_unit' => $fromUnit,
                'to_unit' => $toUnit
            ];
        }
        
        $convertedValue = $value;
        
        switch ($category) {
            case 'length':
                $convertedValue = $this->convertLength($value, $fromUnit, $toUnit);
                break;
            case 'weight':
                $convertedValue = $this->convertWeight($value, $fromUnit, $toUnit);
                break;
            case 'temperature':
                $convertedValue = $this->convertTemperature($value, $fromUnit, $toUnit);
                break;
        }
        
        return [
            'success' => true,
            'original_value' => $value,
            'converted_value' => round($convertedValue, 6),
            'from_unit' => $fromUnit,
            'to_unit' => $toUnit,
            'category' => $category
        ];
    }

    /**
     * Calculate factorial of a number
     */
    public function calculateFactorial(int $number): array
    {
        if ($number < 0) {
            return [
                'success' => false,
                'error' => 'Factorial is not defined for negative numbers',
                'number' => $number
            ];
        }
        
        if ($number > 170) {
            return [
                'success' => false,
                'error' => 'Number too large for factorial calculation',
                'number' => $number
            ];
        }
        
        $factorial = 1;
        for ($i = 1; $i <= $number; $i++) {
            $factorial *= $i;
        }
        
        return [
            'success' => true,
            'number' => $number,
            'factorial' => $factorial,
            'scientific_notation' => sprintf('%.2e', $factorial),
            'digit_count' => strlen((string)$factorial)
        ];
    }

    /**
     * Check if a number is prime
     */
    public function checkPrimeNumber(int $number): array
    {
        if ($number < 2) {
            return [
                'is_prime' => false,
                'number' => $number,
                'reason' => 'Numbers less than 2 are not prime',
                'factors' => []
            ];
        }
        
        $factors = [];
        $isPrime = true;
        
        for ($i = 2; $i <= sqrt($number); $i++) {
            if ($number % $i == 0) {
                $isPrime = false;
                $factors[] = $i;
                if ($i != $number / $i) {
                    $factors[] = $number / $i;
                }
            }
        }
        
        if ($isPrime) {
            $factors = [1, $number];
        } else {
            $factors[] = 1;
            $factors[] = $number;
            sort($factors);
        }
        
        return [
            'is_prime' => $isPrime,
            'number' => $number,
            'factors' => $factors,
            'factor_count' => count($factors),
            'next_prime' => $isPrime ? $this->findNextPrime($number + 1) : null
        ];
    }

    /**
     * Calculate loan payments
     */
    public function calculateLoan(float $loanAmount, float $interestRate, int $loanTerm): array
    {
        if ($loanAmount <= 0 || $interestRate < 0 || $loanTerm <= 0) {
            return [
                'success' => false,
                'error' => 'Invalid loan parameters',
                'loan_amount' => $loanAmount,
                'interest_rate' => $interestRate,
                'loan_term' => $loanTerm
            ];
        }
        
        $monthlyRate = $interestRate / 100 / 12;
        $monthlyPayment = 0;
        
        if ($monthlyRate > 0) {
            $monthlyPayment = $loanAmount * ($monthlyRate * pow(1 + $monthlyRate, $loanTerm)) / (pow(1 + $monthlyRate, $loanTerm) - 1);
        } else {
            $monthlyPayment = $loanAmount / $loanTerm;
        }
        
        $totalPayment = $monthlyPayment * $loanTerm;
        $totalInterest = $totalPayment - $loanAmount;
        
        return [
            'success' => true,
            'loan_amount' => $loanAmount,
            'interest_rate' => $interestRate,
            'loan_term_months' => $loanTerm,
            'monthly_payment' => round($monthlyPayment, 2),
            'total_payment' => round($totalPayment, 2),
            'total_interest' => round($totalInterest, 2),
            'interest_percentage' => round(($totalInterest / $loanAmount) * 100, 2)
        ];
    }

    /**
     * Calculate mortgage payments
     */
    public function calculateMortgage(float $homePrice, float $downPayment, float $interestRate, int $loanTerm): array
    {
        $loanAmount = $homePrice - $downPayment;
        
        if ($loanAmount <= 0) {
            return [
                'success' => false,
                'error' => 'Down payment cannot be greater than or equal to home price',
                'home_price' => $homePrice,
                'down_payment' => $downPayment,
                'loan_amount' => $loanAmount
            ];
        }
        
        $monthlyRate = $interestRate / 100 / 12;
        $monthlyPayment = $loanAmount * ($monthlyRate * pow(1 + $monthlyRate, $loanTerm)) / (pow(1 + $monthlyRate, $loanTerm) - 1);
        
        $totalPayment = $monthlyPayment * $loanTerm;
        $totalInterest = $totalPayment - $loanAmount;
        
        return [
            'success' => true,
            'home_price' => $homePrice,
            'down_payment' => $downPayment,
            'loan_amount' => $loanAmount,
            'interest_rate' => $interestRate,
            'loan_term_years' => $loanTerm,
            'monthly_payment' => round($monthlyPayment, 2),
            'total_payment' => round($totalPayment, 2),
            'total_interest' => round($totalInterest, 2),
            'down_payment_percentage' => round(($downPayment / $homePrice) * 100, 2)
        ];
    }

    /**
     * Calculate simple interest
     */
    public function calculateSimpleInterest(float $principal, float $rate, float $time): array
    {
        if ($principal <= 0 || $rate < 0 || $time < 0) {
            return [
                'success' => false,
                'error' => 'Invalid parameters for simple interest calculation',
                'principal' => $principal,
                'rate' => $rate,
                'time' => $time
            ];
        }
        
        $interest = ($principal * $rate * $time) / 100;
        $amount = $principal + $interest;
        
        return [
            'success' => true,
            'principal' => $principal,
            'rate_percent' => $rate,
            'time_periods' => $time,
            'interest' => round($interest, 2),
            'amount' => round($amount, 2),
            'interest_rate_decimal' => $rate / 100
        ];
    }

    /**
     * Calculate compound interest
     */
    public function calculateCompoundInterest(float $principal, float $rate, int $compoundFreq, float $time): array
    {
        if ($principal <= 0 || $rate < 0 || $compoundFreq <= 0 || $time < 0) {
            return [
                'success' => false,
                'error' => 'Invalid parameters for compound interest calculation',
                'principal' => $principal,
                'rate' => $rate,
                'compound_frequency' => $compoundFreq,
                'time' => $time
            ];
        }
        
        $rateDecimal = $rate / 100;
        $amount = $principal * pow(1 + ($rateDecimal / $compoundFreq), $compoundFreq * $time);
        $interest = $amount - $principal;
        
        return [
            'success' => true,
            'principal' => $principal,
            'rate_percent' => $rate,
            'compound_frequency' => $compoundFreq,
            'time_periods' => $time,
            'amount' => round($amount, 2),
            'interest' => round($interest, 2),
            'effective_rate' => round((pow(1 + ($rateDecimal / $compoundFreq), $compoundFreq) - 1) * 100, 4)
        ];
    }

    /**
     * Calculate BMI (Body Mass Index)
     */
    public function calculateBMI(float $height, float $weight, string $unit = 'metric'): array
    {
        if ($height <= 0 || $weight <= 0) {
            return [
                'success' => false,
                'error' => 'Height and weight must be positive values',
                'height' => $height,
                'weight' => $weight,
                'unit' => $unit
            ];
        }
        
        if ($unit === 'imperial') {
            // Convert to metric
            $height = $height * 0.0254; // inches to meters
            $weight = $weight * 0.453592; // pounds to kg
        }
        
        $bmi = $weight / ($height * $height);
        $category = $this->getBMICategory($bmi);
        
        return [
            'success' => true,
            'height' => $height,
            'weight' => $weight,
            'unit' => $unit,
            'bmi' => round($bmi, 2),
            'category' => $category,
            'healthy_weight_range' => $this->getHealthyWeightRange($height, $unit)
        ];
    }

    /**
     * Calculate BMR (Basal Metabolic Rate)
     */
    public function calculateBMR(float $height, float $weight, int $age, string $gender): array
    {
        if ($height <= 0 || $weight <= 0 || $age <= 0) {
            return [
                'success' => false,
                'error' => 'Invalid parameters for BMR calculation',
                'height' => $height,
                'weight' => $weight,
                'age' => $age,
                'gender' => $gender
            ];
        }
        
        // Mifflin-St Jeor Equation
        if (strtolower($gender) === 'male') {
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
        } else {
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
        }
        
        return [
            'success' => true,
            'height' => $height,
            'weight' => $weight,
            'age' => $age,
            'gender' => $gender,
            'bmr' => round($bmr, 2),
            'calories_per_day' => round($bmr),
            'calories_per_week' => round($bmr * 7),
            'calories_per_month' => round($bmr * 30)
        ];
    }

    /**
     * Calculate daily calorie needs
     */
    public function calculateCalories(float $height, float $weight, int $age, string $gender, string $activity): array
    {
        $bmrResult = $this->calculateBMR($height, $weight, $age, $gender);
        
        if (!$bmrResult['success']) {
            return $bmrResult;
        }
        
        $bmr = $bmrResult['bmr'];
        $activityMultipliers = [
            'sedentary' => 1.2,
            'light' => 1.375,
            'moderate' => 1.55,
            'active' => 1.725,
            'very_active' => 1.9
        ];
        
        $multiplier = $activityMultipliers[$activity] ?? 1.2;
        $dailyCalories = $bmr * $multiplier;
        
        return [
            'success' => true,
            'bmr' => $bmr,
            'activity_level' => $activity,
            'activity_multiplier' => $multiplier,
            'daily_calories' => round($dailyCalories),
            'weekly_calories' => round($dailyCalories * 7),
            'monthly_calories' => round($dailyCalories * 30)
        ];
    }

    /**
     * Calculate body fat percentage
     */
    public function calculateBodyFat(float $height, float $weight, int $age, string $gender): array
    {
        if ($height <= 0 || $weight <= 0 || $age <= 0) {
            return [
                'success' => false,
                'error' => 'Invalid parameters for body fat calculation',
                'height' => $height,
                'weight' => $weight,
                'age' => $age,
                'gender' => $gender
            ];
        }
        
        $bmi = $weight / (($height / 100) * ($height / 100));
        
        if (strtolower($gender) === 'male') {
            $bodyFat = (1.20 * $bmi) + (0.23 * $age) - 16.2;
        } else {
            $bodyFat = (1.20 * $bmi) + (0.23 * $age) - 5.4;
        }
        
        $bodyFat = max(0, min(100, $bodyFat)); // Clamp between 0 and 100
        
        return [
            'success' => true,
            'height' => $height,
            'weight' => $weight,
            'age' => $age,
            'gender' => $gender,
            'bmi' => round($bmi, 2),
            'body_fat_percentage' => round($bodyFat, 2),
            'category' => $this->getBodyFatCategory($bodyFat, $gender)
        ];
    }

    /**
     * Calculate heart rate zones
     */
    public function calculateHeartRate(int $age, int $restingHR): array
    {
        if ($age <= 0 || $restingHR < 0) {
            return [
                'success' => false,
                'error' => 'Invalid parameters for heart rate calculation',
                'age' => $age,
                'resting_hr' => $restingHR
            ];
        }
        
        $maxHR = 220 - $age;
        $hrReserve = $maxHR - $restingHR;
        
        return [
            'success' => true,
            'age' => $age,
            'resting_hr' => $restingHR,
            'max_hr' => $maxHR,
            'hr_reserve' => $hrReserve,
            'zones' => [
                'recovery' => [
                    'min' => $restingHR + ($hrReserve * 0.5),
                    'max' => $restingHR + ($hrReserve * 0.6),
                    'description' => 'Recovery and warm-up'
                ],
                'aerobic' => [
                    'min' => $restingHR + ($hrReserve * 0.6),
                    'max' => $restingHR + ($hrReserve * 0.7),
                    'description' => 'Aerobic base building'
                ],
                'tempo' => [
                    'min' => $restingHR + ($hrReserve * 0.7),
                    'max' => $restingHR + ($hrReserve * 0.8),
                    'description' => 'Tempo training'
                ],
                'threshold' => [
                    'min' => $restingHR + ($hrReserve * 0.8),
                    'max' => $restingHR + ($hrReserve * 0.9),
                    'description' => 'Lactate threshold'
                ],
                'vo2_max' => [
                    'min' => $restingHR + ($hrReserve * 0.9),
                    'max' => $maxHR,
                    'description' => 'VO2 Max intervals'
                ]
            ]
        ];
    }

    /**
     * Calculate water intake recommendation
     */
    public function calculateWaterIntake(float $weight, string $activity, string $climate): array
    {
        if ($weight <= 0) {
            return [
                'success' => false,
                'error' => 'Weight must be a positive value',
                'weight' => $weight,
                'activity' => $activity,
                'climate' => $climate
            ];
        }
        
        $baseIntake = $weight * 35; // 35ml per kg
        
        $activityMultipliers = [
            'sedentary' => 1.0,
            'light' => 1.2,
            'moderate' => 1.4,
            'high' => 1.6,
            'extreme' => 1.8
        ];
        
        $climateMultipliers = [
            'cool' => 1.0,
            'moderate' => 1.1,
            'warm' => 1.2,
            'hot' => 1.3,
            'very_hot' => 1.4
        ];
        
        $activityMultiplier = $activityMultipliers[$activity] ?? 1.0;
        $climateMultiplier = $climateMultipliers[$climate] ?? 1.0;
        
        $dailyIntake = $baseIntake * $activityMultiplier * $climateMultiplier;
        
        return [
            'success' => true,
            'weight' => $weight,
            'activity_level' => $activity,
            'climate' => $climate,
            'base_intake_ml' => round($baseIntake),
            'daily_intake_ml' => round($dailyIntake),
            'daily_intake_liters' => round($dailyIntake / 1000, 2),
            'glasses_8oz' => round($dailyIntake / 240),
            'glasses_12oz' => round($dailyIntake / 355)
        ];
    }

    /**
     * Calculate percentage
     */
    public function calculatePercentage(float $value, float $percentage, string $operation): array
    {
        if ($percentage < 0 || $percentage > 100) {
            return [
                'success' => false,
                'error' => 'Percentage must be between 0 and 100',
                'value' => $value,
                'percentage' => $percentage,
                'operation' => $operation
            ];
        }
        
        $result = 0;
        $description = '';
        
        switch ($operation) {
            case 'of':
                $result = ($value * $percentage) / 100;
                $description = "{$percentage}% of {$value}";
                break;
            case 'increase':
                $result = $value + (($value * $percentage) / 100);
                $description = "{$value} increased by {$percentage}%";
                break;
            case 'decrease':
                $result = $value - (($value * $percentage) / 100);
                $description = "{$value} decreased by {$percentage}%";
                break;
            case 'change':
                $result = (($percentage - $value) / $value) * 100;
                $description = "Change from {$value} to {$percentage}";
                break;
            default:
                return [
                    'success' => false,
                    'error' => 'Invalid operation. Use: of, increase, decrease, or change',
                    'value' => $value,
                    'percentage' => $percentage,
                    'operation' => $operation
                ];
        }
        
        return [
            'success' => true,
            'value' => $value,
            'percentage' => $percentage,
            'operation' => $operation,
            'result' => round($result, 2),
            'description' => $description
        ];
    }

    /**
     * Calculate distance between two GPS coordinates
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2, string $unit): array
    {
        $earthRadius = [
            'km' => 6371,
            'miles' => 3959,
            'meters' => 6371000,
            'feet' => 20902231
        ];
        
        if (!isset($earthRadius[$unit])) {
            return [
                'success' => false,
                'error' => 'Invalid unit. Use: km, miles, meters, or feet',
                'lat1' => $lat1,
                'lon1' => $lon1,
                'lat2' => $lat2,
                'lon2' => $lon2,
                'unit' => $unit
            ];
        }
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius[$unit] * $c;
        
        return [
            'success' => true,
            'point1' => ['lat' => $lat1, 'lon' => $lon1],
            'point2' => ['lat' => $lat2, 'lon' => $lon2],
            'distance' => round($distance, 2),
            'unit' => $unit,
            'distance_km' => round($distance * ($unit === 'km' ? 1 : 1.60934), 2),
            'distance_miles' => round($distance * ($unit === 'miles' ? 1 : 0.621371), 2)
        ];
    }

    /**
     * Scientific calculation using expression evaluation
     */
    public function scientificCalculation(string $expression): array
    {
        try {
            $result = $this->evaluateExpression($expression);
            
            return [
                'success' => true,
                'expression' => $expression,
                'result' => $result,
                'formatted' => number_format($result, 6),
                'scientific' => sprintf('%.6e', $result)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid mathematical expression',
                'expression' => $expression,
                'details' => $e->getMessage()
            ];
        }
    }

    // Private helper methods

    private function getUnitCategory(string $unit): string
    {
        $categories = [
            'length' => ['m', 'cm', 'mm', 'km', 'in', 'ft', 'yd', 'mi'],
            'weight' => ['kg', 'g', 'mg', 'lb', 'oz', 'ton'],
            'temperature' => ['c', 'f', 'k', 'celsius', 'fahrenheit', 'kelvin']
        ];
        
        foreach ($categories as $category => $units) {
            if (in_array(strtolower($unit), $units)) {
                return $category;
            }
        }
        
        return 'unknown';
    }

    private function convertLength(float $value, string $from, string $to): float
    {
        $toMeters = [
            'mm' => 0.001, 'cm' => 0.01, 'm' => 1, 'km' => 1000,
            'in' => 0.0254, 'ft' => 0.3048, 'yd' => 0.9144, 'mi' => 1609.34
        ];
        
        $meters = $value * ($toMeters[$from] ?? 1);
        return $meters / ($toMeters[$to] ?? 1);
    }

    private function convertWeight(float $value, string $from, string $to): float
    {
        $toKg = [
            'mg' => 0.000001, 'g' => 0.001, 'kg' => 1, 'ton' => 1000,
            'oz' => 0.0283495, 'lb' => 0.453592
        ];
        
        $kg = $value * ($toKg[$from] ?? 1);
        return $kg / ($toKg[$to] ?? 1);
    }

    private function convertTemperature(float $value, string $from, string $to): float
    {
        $celsius = $value;
        
        // Convert to Celsius
        switch (strtolower($from)) {
            case 'f':
            case 'fahrenheit':
                $celsius = ($value - 32) * 5/9;
                break;
            case 'k':
            case 'kelvin':
                $celsius = $value - 273.15;
                break;
        }
        
        // Convert from Celsius
        switch (strtolower($to)) {
            case 'f':
            case 'fahrenheit':
                return ($celsius * 9/5) + 32;
            case 'k':
            case 'kelvin':
                return $celsius + 273.15;
            default:
                return $celsius;
        }
    }

    private function findNextPrime(int $start): int
    {
        for ($i = $start; $i < $start + 1000; $i++) {
            if ($this->isPrime($i)) {
                return $i;
            }
        }
        return $start + 1000; // Fallback
    }

    private function isPrime(int $number): bool
    {
        if ($number < 2) return false;
        for ($i = 2; $i <= sqrt($number); $i++) {
            if ($number % $i == 0) return false;
        }
        return true;
    }

    private function getBMICategory(float $bmi): string
    {
        if ($bmi < 18.5) return 'Underweight';
        if ($bmi < 25) return 'Normal weight';
        if ($bmi < 30) return 'Overweight';
        return 'Obese';
    }

    private function getHealthyWeightRange(float $height, string $unit): array
    {
        $heightM = $unit === 'imperial' ? $height * 0.0254 : $height / 100;
        $minWeight = 18.5 * $heightM * $heightM;
        $maxWeight = 24.9 * $heightM * $heightM;
        
        if ($unit === 'imperial') {
            $minWeight *= 2.20462; // kg to lbs
            $maxWeight *= 2.20462;
        }
        
        return [
            'min_weight' => round($minWeight, 1),
            'max_weight' => round($maxWeight, 1),
            'unit' => $unit
        ];
    }

    private function getBodyFatCategory(float $bodyFat, string $gender): string
    {
        if (strtolower($gender) === 'male') {
            if ($bodyFat < 6) return 'Essential fat';
            if ($bodyFat < 14) return 'Athletes';
            if ($bodyFat < 18) return 'Fitness';
            if ($bodyFat < 25) return 'Average';
            return 'Obese';
        } else {
            if ($bodyFat < 10) return 'Essential fat';
            if ($bodyFat < 16) return 'Athletes';
            if ($bodyFat < 20) return 'Fitness';
            if ($bodyFat < 25) return 'Average';
            return 'Obese';
        }
    }

    private function evaluateExpression(string $expression): float
    {
        // Remove any potentially dangerous characters
        $expression = preg_replace('/[^0-9+\-*\/\(\)\.\s]/', '', $expression);
        
        // Basic validation
        if (empty($expression)) {
            throw new \Exception('Empty expression');
        }
        
        // Use eval for simple mathematical expressions (in production, use a proper math parser)
        $result = eval("return {$expression};");
        
        if (!is_numeric($result)) {
            throw new \Exception('Invalid mathematical expression');
        }
        
        return (float)$result;
    }
}
