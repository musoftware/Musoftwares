<?php

namespace App\Helpers;

use Modules\Core\Models\Action;
use Modules\Core\Models\Invoice;
use Modules\Core\Models\PointSupport;
use Modules\Core\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UnifiedPointsHelper
{
    /**
     * Calculate unified performance score for a user
     *
     * @param User $user
     * @param array $options
     * @return array
     */
    public static function calculateUnifiedScore(User $user, array $options = []): array
    {
        $weights = array_merge([
            'points' => 1.0,
            'spending' => 0.5,
            'activity' => 10.0,
            'job_success' => 50.0,
            'membership_bonus' => 0.2,
            'invoice_performance' => 10.0,
            'payment_reliability' => 15.0
        ], $options['weights'] ?? []);

        // Calculate individual components
        $pointsComponent = self::calculatePointsComponent($user) * $weights['points'];
        $spendingComponent = self::calculateSpendingComponent($user) * $weights['spending'];
        $activityComponent = self::calculateActivityComponent($user) * $weights['activity'];
        $jobComponent = self::calculateJobComponent($user) * $weights['job_success'];
        $membershipComponent = self::calculateMembershipComponent($user) * $weights['membership_bonus'];

        // New business-critical components
        $invoiceComponent = self::calculateInvoiceComponent($user) * $weights['invoice_performance'];
        $paymentReliabilityComponent = self::calculatePaymentReliabilityComponent($user, $invoiceComponent) * $weights['payment_reliability'];

        $totalScore = $pointsComponent + $spendingComponent + $activityComponent + $jobComponent +
            $membershipComponent + $invoiceComponent + $paymentReliabilityComponent;
        $totalScore = max(0, round($totalScore, 0));
        return [
            'total_score' => $totalScore,
            'components' => [
                'points' => round($pointsComponent, 2),
                'spending' => round($spendingComponent, 2),
                'activity' => round($activityComponent, 2),
                'job_success' => round($jobComponent, 2),
                'membership' => round($membershipComponent, 2),
                'invoice_performance' => round($invoiceComponent, 2),
                'payment_reliability' => round($paymentReliabilityComponent, 2)
            ],
            'breakdown' => [
                'raw_points' => $user->total_points ?? 0,
                'raw_spending' => $user->total_spending ?? 0,
                'activity_hours' => $user->total_activity_hours ?? 0,
                'job_success_rate' => $user->job_success_rate ?? 0,
                'invoice_metrics' => self::getInvoiceMetrics($user),
                'payment_metrics' => self::getPaymentMetrics($user)
            ]
        ];
    }

    /**
     * Calculate points component for a user
     */
    private static function calculatePointsComponent(User $user): float
    {
        if (!isset($user->total_points)) {
            $user->total_points = $user->actions()
                ->where('status', 'completed')
                ->sum('coins_reward');
        }

        return (float)($user->total_points ?? 0);
    }

    /**
     * Calculate spending component for a user
     */
    private static function calculateSpendingComponent(User $user): float
    {
        if (!isset($user->total_spending)) {
            $user->total_spending = $user->transactions()
                ->where('type', 'used')
                ->where('business_calculated', '1')
                ->sum(DB::raw('ABS(business_amount)'));
        }

        return (float)($user->total_spending ?? 0);
    }

    /**
     * Calculate activity component for a user
     */
    private static function calculateActivityComponent(User $user): float
    {
        if (!isset($user->total_activity_hours)) {
            $totalSeconds = $user->user_activity()
                ->where('activity_date', '>=', now()->subDays(30))
                ->sum('total_seconds');

            $user->total_activity_hours = round($totalSeconds / 3600, 1);
        }

        return (float)($user->total_activity_hours ?? 0);
    }

    /**
     * Calculate job success component for a user
     */
    private static function calculateJobComponent(User $user): float
    {
        if (!isset($user->job_success_rate)) {
            $completedJobs = $user->userJobs()->where('status', 'completed')->count();
            $totalJobs = $user->userJobs()->count();

            $user->job_success_rate = $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 1) : 0;
        }

        return (float)($user->job_success_rate ?? 0);
    }

    /**
     * Calculate membership bonus component
     */
    private static function calculateMembershipComponent(User $user): float
    {
        // Check if user has active membership
        $activeMembership = $user->memberships()
            ->where('expires_at', '>', now())
            ->first();

        if ($activeMembership) {
            // Bonus based on membership tier
            $membershipBonus = match ($activeMembership->membership->name ?? 'basic') {
                'premium' => 1000,
                'gold' => 2000,
                'platinum' => 3000,
                default => 500
            };

            // Additional bonus for membership duration
            $membershipDays = Carbon::parse($activeMembership->created_at)->diffInDays(now());
            $durationBonus = min(500, $membershipDays * 2); // Max 500 bonus points

            return $membershipBonus + $durationBonus;
        }

        return 0;
    }

    /**
     * Calculate performance tier based on unified score
     */
    public static function calculatePerformanceTier(float $unifiedScore): array
    {
        if ($unifiedScore >= 100000) {
            return [
                'tier' => 'Platinum',
                'level' => 'Elite',
                'badge_color' => 'warning',
                'description' => 'Top-tier performer with exceptional metrics'
            ];
        } elseif ($unifiedScore >= 50000) {
            return [
                'tier' => 'Gold',
                'level' => 'Advanced',
                'badge_color' => 'success',
                'description' => 'High achiever with strong performance'
            ];
        } elseif ($unifiedScore >= 25000) {
            return [
                'tier' => 'Silver',
                'level' => 'Intermediate',
                'badge_color' => 'info',
                'description' => 'Above average performance across metrics'
            ];
        } elseif ($unifiedScore >= 10000) {
            return [
                'tier' => 'Bronze',
                'level' => 'Developing',
                'badge_color' => 'primary',
                'description' => 'Good foundation with room for growth'
            ];
        } else {
            return [
                'tier' => 'Starter',
                'level' => 'Beginning',
                'badge_color' => 'secondary',
                'description' => 'New member building their profile'
            ];
        }
    }

    /**
     * Get user ranking position based on unified score
     */
    public static function getUserRanking(User $user, $allUsers = null): array
    {
        if ($allUsers === null) {
            $allUsers = User::with(['actions', 'transactions', 'user_activity', 'userJobs'])
                ->get()
                ->map(function ($u) {
                    $score = self::calculateUnifiedScore($u);
                    $u->unified_score = $score['total_score'];
                    return $u;
                })
                ->sortByDesc('unified_score')
                ->values();
        }

        $userRank = $allUsers->search(function ($u) use ($user) {
            return $u->id === $user->id;
        });

        $rank = $userRank !== false ? $userRank + 1 : null;
        $totalUsers = $allUsers->count();
        $percentile = $rank ? round((($totalUsers - $rank + 1) / $totalUsers) * 100, 1) : 0;

        return [
            'rank' => $rank,
            'total_users' => $totalUsers,
            'percentile' => $percentile,
            'is_top_10' => $rank && $rank <= 10,
            'is_top_50' => $rank && $rank <= 50,
            'is_top_100' => $rank && $rank <= 100
        ];
    }

    /**
     * Calculate conversion rate for points to currency
     */
    public static function calculateConversionRate(): float
    {
        $cacheKey = 'unified_conversion_rate_' . now()->format('Y-m-d-H');

        return Cache::remember($cacheKey, 3600, function () {
            $endDate = Carbon::now();

            // Calculate total money from transactions
            $totalMoney = abs(Transaction::where('type', 'used')
                    ->where('created_at', '>', Carbon::parse('2025-1-1'))
                    ->where('created_at', '<=', $endDate)
                    ->sum('business_amount')) / 400;

            // Calculate total points from actions
            $totalPoints = Action::where('status', 'completed')
                ->where('created_at', '<=', $endDate)
                ->sum(DB::raw('CASE WHEN coins_reward > 0 THEN coins_reward ELSE ABS(coins_reward) * 8 END'));

            // Calculate support money
            $totalSupportMoney = PointSupport::where('created_at', '>', Carbon::parse('2025-1-1'))
                ->where('created_at', '<=', $endDate)
                ->sum('business_amount');

            // Prevent division by zero
            if ($totalPoints == 0) {
                return 0.01;
            }

            $conversionRate = ($totalMoney + $totalSupportMoney) / $totalPoints;
            return max(0.01, round($conversionRate, 15));
        });
    }

    /**
     * Generate performance insights for a user
     */
    public static function generatePerformanceInsights(User $user): array
    {
        $unifiedData = self::calculateUnifiedScore($user);
        $tier = self::calculatePerformanceTier($unifiedData['total_score']);
        $ranking = self::getUserRanking($user);
        $paymentInsights = self::getPaymentBehaviorInsights($user);

        $insights = [];

        // Ranking insights
        if ($ranking['is_top_10']) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Top Performer',
                'message' => "You're in the top 10 members! Excellent performance across all metrics."
            ];
        } elseif ($ranking['is_top_50']) {
            $insights[] = [
                'type' => 'info',
                'title' => 'High Achiever',
                'message' => "You're in the top 50 members. Keep up the great work!"
            ];
        }

        // Component-specific insights
        $components = $unifiedData['components'];
        $maxComponent = array_keys($components, max($components))[0];
        $minComponent = array_keys($components, min($components))[0];

        $insights[] = [
            'type' => 'primary',
            'title' => 'Strength Area',
            'message' => "Your strongest performance area is " . ucfirst(str_replace('_', ' ', $maxComponent))
        ];

        if ($components[$minComponent] < ($unifiedData['total_score'] * 0.1)) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Growth Opportunity',
                'message' => "Consider focusing on " . ucfirst(str_replace('_', ' ', $minComponent)) . " to boost your overall score"
            ];
        }

        // Add payment behavior insights
        $insights = array_merge($insights, $paymentInsights);

        // Invoice-specific insights
        $invoiceMetrics = $unifiedData['breakdown']['invoice_metrics'];
        if ($invoiceMetrics['total_invoices'] === 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Business Activity',
                'message' => 'Start generating invoices to improve your business performance score.'
            ];
        }

        return [
            'tier' => $tier,
            'ranking' => $ranking,
            'insights' => $insights,
            'score_breakdown' => $unifiedData,
            'payment_behavior' => $paymentInsights
        ];
    }

    /**
     * Get leaderboard with unified scoring
     */
    public static function getUnifiedLeaderboard(int $limit = 50): \Illuminate\Support\Collection
    {
        $cacheKey = "unified_leaderboard_{$limit}_" . now()->format('Y-m-d-H');

        return Cache::remember($cacheKey, 1800, function () use ($limit) {
            return User::with(['actions', 'transactions', 'user_activity', 'userJobs', 'memberships.membership'])
                ->get()
                ->map(function ($user) {
                    $scoreData = self::calculateUnifiedScore($user);
                    $user->unified_score = $scoreData['total_score'];
                    $user->score_components = $scoreData['components'];
                    $user->performance_tier = self::calculatePerformanceTier($scoreData['total_score']);

                    // Add member since info
                    $user->member_since = Carbon::parse($user->created_at)->diffForHumans();

                    return $user;
                })
                ->sortByDesc('unified_score')
                ->take($limit)
                ->values()
                ->map(function ($user, $index) {
                    $user->rank = $index + 1;
                    return $user;
                });
        });
    }

    /**
     * Calculate invoice performance component
     */
    private static function calculateInvoiceComponent(User $user): float
    {
        $invoiceMetrics = self::getInvoiceMetrics($user);

        // Base score from invoice count (more invoices = more business activity)
        $invoiceCountScore = min(100, $invoiceMetrics['total_invoices']);

        // Payment completion rate bonus
        $paymentRateBonus = $invoiceMetrics['payment_completion_rate'] * 5;

        // Invoice value bonus (normalized)
        $valueBonus = min(50, $invoiceMetrics['total_invoice_value'] / 1000);

        return $invoiceCountScore > 0 ? $invoiceCountScore + $paymentRateBonus + $valueBonus : 0;
    }

    /**
     * Calculate payment reliability component with penalties for late payments
     */
    private static function calculatePaymentReliabilityComponent(User $user, $invoiceComponent): float
    {
        $paymentMetrics = self::getPaymentMetrics($user);

        // Start with base reliability score
        $baseScore = 10;

        // Apply penalties for late payments
        $latePaymentPenalty = $paymentMetrics['late_payment_count'] * 10; // -10 points per late payment
        $veryLatePaymentPenalty = $paymentMetrics['very_late_payment_count'] * 25; // -25 points per very late payment

        // Apply bonus for consistent on-time payments
        $onTimeBonus = $paymentMetrics['on_time_payment_rate'] * 1;

        // Apply penalty for unpaid invoices
        $unpaidPenalty = $paymentMetrics['unpaid_invoices_count'] * 15; // -15 points per unpaid invoice

        $finalScore = $baseScore + $onTimeBonus - $latePaymentPenalty - $veryLatePaymentPenalty - $unpaidPenalty;

        return $invoiceComponent > 0 ? max(0, $finalScore) : 0; // Ensure non-negative
    }

    /**
     * Get detailed invoice metrics for a user
     */
    private static function getInvoiceMetrics(User $user): array
    {
        $invoices = $user->invoices()->get();

        $totalInvoices = $invoices->count();
        $paidInvoices = $invoices->where('status', 'paid')->count();
        $partiallyPaidInvoices = $invoices->where('status', 'partially_paid')->count();
        $unpaidInvoices = $invoices->where('status', 'unpaid')->count();

        $totalInvoiceValue = $invoices->sum('paid');
        $paymentCompletionRate = $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 100;

        return [
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'partially_paid_invoices' => $partiallyPaidInvoices,
            'unpaid_invoices' => $unpaidInvoices,
            'total_invoice_value' => $totalInvoiceValue,
            'payment_completion_rate' => round($paymentCompletionRate, 2),
            'average_invoice_value' => $totalInvoices > 0 ? round($totalInvoiceValue / $totalInvoices, 2) : 0
        ];
    }

    /**
     * Get detailed payment behavior metrics for a user
     */
    private static function getPaymentMetrics(User $user): array
    {
        $invoices = $user->invoices()->get();
        $now = Carbon::now();

        $onTimePayments = 0;
        $latePayments = 0;
        $veryLatePayments = 0;
        $unpaidCount = 0;

        foreach ($invoices as $invoice) {
            if ($invoice->status === 'unpaid') {
                $unpaidCount++;

                // Check if unpaid invoice is overdue (assuming 30 days standard payment terms)
                $daysOverdue = $now->diffInDays(Carbon::parse($invoice->created_at));
                if ($daysOverdue > 30) {
                    if ($daysOverdue > 60) {
                        $veryLatePayments++; // Very late (60+ days)
                        // Late (30-60 days)
                    }
                    $latePayments++;
                }
            } elseif ($invoice->status === 'paid') {
                // For paid invoices, check payment timing based on created_at vs updated_at
                $paymentTime = Carbon::parse($invoice->updated_at);
                $invoiceDate = Carbon::parse($invoice->created_at);
                $daysToPay = $invoiceDate->diffInDays($paymentTime);

                if ($daysToPay <= 30) {
                    $onTimePayments++;
                } elseif ($daysToPay <= 60) {
                    $latePayments++;
                } else {
                    $veryLatePayments++;
                }
            } elseif ($invoice->status === 'partially_paid') {
                // Treat partially paid as late
                $latePayments++;
            }
        }

        $totalProcessedInvoices = $onTimePayments + $latePayments + $veryLatePayments;
        $onTimePaymentRate = $totalProcessedInvoices > 0 ? ($onTimePayments / $totalProcessedInvoices) * 100 : 100;

        // Calculate payment reliability score
        $reliabilityScore = 100;
        if ($totalProcessedInvoices > 0) {
            $lateRate = ($latePayments / $totalProcessedInvoices) * 100;
            $veryLateRate = ($veryLatePayments / $totalProcessedInvoices) * 100;
            $reliabilityScore = 100 - ($lateRate * 0.5) - ($veryLateRate * 1.5);
        }

        return [
            'on_time_payments' => $onTimePayments,
            'late_payment_count' => $latePayments,
            'very_late_payment_count' => $veryLatePayments,
            'unpaid_invoices_count' => $unpaidCount,
            'on_time_payment_rate' => round($onTimePaymentRate, 2),
            'payment_reliability_score' => round(max(0, $reliabilityScore), 2),
            'average_payment_days' => self::calculateAveragePaymentDays($invoices)
        ];
    }

    /**
     * Calculate average days to pay invoices
     */
    private static function calculateAveragePaymentDays($invoices): float
    {
        $paidInvoices = $invoices->where('status', 'paid');

        if ($paidInvoices->count() === 0) {
            return 0;
        }

        $totalDays = 0;
        foreach ($paidInvoices as $invoice) {
            $invoiceDate = Carbon::parse($invoice->created_at);
            $paymentDate = Carbon::parse($invoice->updated_at);
            $totalDays += $invoiceDate->diffInDays($paymentDate);
        }

        return round($totalDays / $paidInvoices->count(), 1);
    }

    /**
     * Get payment behavior insights for a user
     */
    public static function getPaymentBehaviorInsights(User $user): array
    {
        $paymentMetrics = self::getPaymentMetrics($user);
        $invoiceMetrics = self::getInvoiceMetrics($user);

        $insights = [];

        // Payment reliability insights
        if ($paymentMetrics['on_time_payment_rate'] >= 90) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Excellent Payment Record',
                'message' => 'You have an outstanding payment history with ' . $paymentMetrics['on_time_payment_rate'] . '% on-time payments.'
            ];
        } elseif ($paymentMetrics['on_time_payment_rate'] >= 70) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Good Payment Reliability',
                'message' => 'Your payment record is solid. Consider improving timing for better scores.'
            ];
        } else {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Payment Improvement Needed',
                'message' => 'Late payments are affecting your score. Focus on timely payments to improve your ranking.'
            ];
        }

        // Unpaid invoice warnings
        if ($paymentMetrics['unpaid_invoices_count'] > 0) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Outstanding Invoices',
                'message' => "You have {$paymentMetrics['unpaid_invoices_count']} unpaid invoice(s). Pay them to improve your score."
            ];
        }

        // Invoice volume insights
        if ($invoiceMetrics['total_invoices'] >= 50) {
            $insights[] = [
                'type' => 'success',
                'title' => 'High Business Activity',
                'message' => 'Your high invoice volume demonstrates strong business engagement.'
            ];
        }

        return $insights;
    }
}
