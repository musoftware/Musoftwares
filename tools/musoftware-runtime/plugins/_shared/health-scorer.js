/**
 * WhatsApp Health Scorer — Shared Module
 * ========================================
 * Calculates and tracks trust/health scores for WhatsApp numbers.
 * Used by warmup system, sender, and deliverability tracker to
 * decide whether a number is safe to use for campaigns.
 *
 * Scoring dimensions:
 *   - Age factor:           how old the number is (days active)
 *   - Reply ratio:          % of sent messages that got replies
 *   - Conversation ratio:   % of sessions with bi-directional chat
 *   - Spam inverse:         inverse of reports/blocks received
 *   - Activity consistency: regularity of daily usage
 *   - Warmup progress:      days completed in warmup schedule
 *
 * Usage:
 *   const HealthScorer = require('../_shared/health-scorer');
 *   const scorer = new HealthScorer();
 *
 *   const score = scorer.calculate(numberMetrics);
 *   // { score: 78, grade: 'B+', risk: 'low', recommendation: '...' }
 */

'use strict';

// ── Scoring Weights ──────────────────────────────────────────────────────────

const WEIGHTS = {
    ageFactor:            0.15,   // 15% — how long the number has been active
    replyRatio:           0.25,   // 25% — most important signal
    conversationRatio:    0.15,   // 15% — bi-directional conversations
    spamInverse:          0.20,   // 20% — blocks and reports
    activityConsistency:  0.10,   // 10% — regularity
    warmupProgress:       0.10,   // 10% — completed warmup days
    volumeScore:          0.05,   // 5%  — messages per day (too many = bad)
};

// ── Risk Thresholds ──────────────────────────────────────────────────────────

const RISK_LEVELS = {
    critical: { min: 0,  max: 20,  label: 'critical', color: 'red',    action: 'STOP immediately — high ban risk' },
    high:     { min: 20, max: 40,  label: 'high',     color: 'orange', action: 'Reduce volume, start recovery warmup' },
    moderate: { min: 40, max: 60,  label: 'moderate', color: 'yellow', action: 'Monitor closely, reduce volume by 30%' },
    low:      { min: 60, max: 80,  label: 'low',      color: 'green',  action: 'Safe — maintain current behavior' },
    minimal:  { min: 80, max: 100, label: 'minimal',  color: 'blue',   action: 'Excellent — can increase volume carefully' },
};

// ── Grade Scale ──────────────────────────────────────────────────────────────

const GRADES = [
    { min: 95, grade: 'A+' },
    { min: 90, grade: 'A'  },
    { min: 85, grade: 'A-' },
    { min: 80, grade: 'B+' },
    { min: 75, grade: 'B'  },
    { min: 70, grade: 'B-' },
    { min: 65, grade: 'C+' },
    { min: 60, grade: 'C'  },
    { min: 55, grade: 'C-' },
    { min: 50, grade: 'D+' },
    { min: 40, grade: 'D'  },
    { min: 30, grade: 'D-' },
    { min: 0,  grade: 'F'  },
];

// ── Class ────────────────────────────────────────────────────────────────────

class HealthScorer {

    constructor(opts = {}) {
        this.weights = { ...WEIGHTS, ...(opts.weights || {}) };
        this.log = opts.log || (() => {});
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Calculate health score for a number.
     *
     * @param {object} metrics
     * @param {number} metrics.ageDays         — days since number activation
     * @param {number} metrics.messagesSent    — total messages sent
     * @param {number} metrics.messagesReceived — total messages received (replies)
     * @param {number} metrics.conversations   — total conversation threads
     * @param {number} metrics.biDirectional   — conversations with both sent + received
     * @param {number} metrics.blocksReceived  — times blocked/reported
     * @param {number} metrics.activeDays      — days with at least 1 message
     * @param {number} metrics.totalDays       — total days tracked
     * @param {number} metrics.warmupDaysCompleted — warmup schedule days finished
     * @param {number} metrics.warmupDaysTotal — total warmup schedule days
     * @param {number} metrics.avgDailyMessages — average messages per active day
     *
     * @returns {{ score, grade, risk, riskLevel, recommendation, breakdown, banProbability }}
     */
    calculate(metrics) {
        const m = this._normalizeMetrics(metrics);
        const breakdown = {};

        // 1. Age Factor (0-100)
        //    Peaks at 30+ days, minimal for <3 days
        breakdown.ageFactor = this._sigmoid(m.ageDays, 15, 0.15) * 100;

        // 2. Reply Ratio (0-100)
        //    replies / sent — target >30% for good health
        const replyRatio = m.messagesSent > 0 ? m.messagesReceived / m.messagesSent : 0;
        breakdown.replyRatio = Math.min(100, (replyRatio / 0.4) * 100);

        // 3. Conversation Ratio (0-100)
        //    bi-directional / total conversations — target >50%
        const convRatio = m.conversations > 0 ? m.biDirectional / m.conversations : 0;
        breakdown.conversationRatio = Math.min(100, (convRatio / 0.6) * 100);

        // 4. Spam Inverse (0-100)
        //    Penalize blocks — even 1 block is significant
        const blockRate = m.messagesSent > 0 ? m.blocksReceived / m.messagesSent : 0;
        breakdown.spamInverse = Math.max(0, 100 - (blockRate * 5000)); // 2% blocks → score 0

        // 5. Activity Consistency (0-100)
        //    activeDays / totalDays — irregular = suspicious
        const consistency = m.totalDays > 0 ? m.activeDays / m.totalDays : 0;
        breakdown.activityConsistency = Math.min(100, (consistency / 0.7) * 100);

        // 6. Warmup Progress (0-100)
        const warmupPct = m.warmupDaysTotal > 0 ? m.warmupDaysCompleted / m.warmupDaysTotal : 0;
        breakdown.warmupProgress = warmupPct * 100;

        // 7. Volume Score (0-100)
        //    Sweet spot: 20-80 msgs/day. Too low = suspicious. Too high = spammy.
        breakdown.volumeScore = this._volumeCurve(m.avgDailyMessages);

        // ── Weighted total ───────────────────────────────────────────────
        let score = 0;
        for (const [key, weight] of Object.entries(this.weights)) {
            score += (breakdown[key] || 0) * weight;
        }
        score = Math.round(Math.max(0, Math.min(100, score)));

        // ── Derived values ───────────────────────────────────────────────
        const grade = this._getGrade(score);
        const riskLevel = this._getRiskLevel(score);
        const banProbability = this._estimateBanProbability(score, m);

        return {
            score,
            grade,
            risk:           riskLevel.label,
            riskColor:      riskLevel.color,
            recommendation: riskLevel.action,
            banProbability: `${banProbability}%`,
            breakdown,
            maxDailyRecommended: this._recommendDailyMax(score),
        };
    }

    /**
     * Calculate ban probability percentage.
     * @param {object} metrics
     * @returns {number} 0-100
     */
    estimateBanRisk(metrics) {
        const result = this.calculate(metrics);
        return parseFloat(result.banProbability);
    }

    /**
     * Get recommended daily send limits based on health.
     * @param {number} score — health score 0-100
     * @returns {number}
     */
    _recommendDailyMax(score) {
        if (score >= 90) return 300;
        if (score >= 80) return 200;
        if (score >= 70) return 150;
        if (score >= 60) return 80;
        if (score >= 50) return 40;
        if (score >= 40) return 20;
        if (score >= 20) return 5;
        return 0; // stop sending
    }

    /**
     * Track a send event and return updated recommendation.
     * @param {object} health — current health object from session
     * @param {'sent'|'delivered'|'read'|'replied'|'blocked'|'failed'} status
     * @returns {object} updated health
     */
    recordEvent(health, status) {
        const h = { ...health };

        switch (status) {
            case 'sent':
                h.messagesSent = (h.messagesSent || 0) + 1;
                break;
            case 'delivered':
                // Positive signal but no score change
                break;
            case 'read':
                // Positive signal
                break;
            case 'replied':
                h.messagesRecv = (h.messagesRecv || 0) + 1;
                h.replyRatio = h.messagesSent > 0 ? h.messagesRecv / h.messagesSent : 0;
                break;
            case 'blocked':
                h.warningCount = (h.warningCount || 0) + 1;
                // Blocks are severe — immediate trust drop
                h.trustScore = Math.max(0, (h.trustScore || 50) - 5);
                break;
            case 'failed':
                h.errorCount = (h.errorCount || 0) + 1;
                break;
        }

        return h;
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    _normalizeMetrics(m) {
        return {
            ageDays:              Math.max(0, m.ageDays || 0),
            messagesSent:         Math.max(0, m.messagesSent || 0),
            messagesReceived:     Math.max(0, m.messagesReceived || 0),
            conversations:        Math.max(0, m.conversations || 0),
            biDirectional:        Math.max(0, m.biDirectional || 0),
            blocksReceived:       Math.max(0, m.blocksReceived || 0),
            activeDays:           Math.max(0, m.activeDays || 0),
            totalDays:            Math.max(1, m.totalDays || 1),
            warmupDaysCompleted:  Math.max(0, m.warmupDaysCompleted || 0),
            warmupDaysTotal:      Math.max(0, m.warmupDaysTotal || 14),
            avgDailyMessages:     Math.max(0, m.avgDailyMessages || 0),
        };
    }

    /**
     * Sigmoid curve — smooth ramp from 0 to 1.
     * @param {number} x — input value
     * @param {number} midpoint — where curve hits 0.5
     * @param {number} steepness — how sharp the transition is
     */
    _sigmoid(x, midpoint, steepness) {
        return 1 / (1 + Math.exp(-steepness * (x - midpoint)));
    }

    /**
     * Bell curve for daily volume — sweet spot around 30-80 msgs/day.
     */
    _volumeCurve(avgDaily) {
        if (avgDaily <= 0) return 20;  // no activity = somewhat suspicious
        if (avgDaily <= 5) return 40;  // very low
        if (avgDaily <= 20) return 70; // warming up
        if (avgDaily <= 80) return 100; // sweet spot
        if (avgDaily <= 150) return 80; // getting high
        if (avgDaily <= 300) return 50; // risky
        return 20; // danger zone
    }

    _getGrade(score) {
        for (const { min, grade } of GRADES) {
            if (score >= min) return grade;
        }
        return 'F';
    }

    _getRiskLevel(score) {
        for (const level of Object.values(RISK_LEVELS)) {
            if (score >= level.min && score < level.max) return level;
        }
        return RISK_LEVELS.critical;
    }

    _estimateBanProbability(score, metrics) {
        let prob = Math.max(0, 100 - score);

        // Amplifiers
        if (metrics.blocksReceived > 0 && metrics.messagesSent > 0) {
            const blockRate = metrics.blocksReceived / metrics.messagesSent;
            if (blockRate > 0.05) prob = Math.min(95, prob + 30); // >5% block rate
            if (blockRate > 0.02) prob = Math.min(90, prob + 15); // >2% block rate
        }

        if (metrics.ageDays < 3) prob = Math.min(90, prob + 20);   // very new number
        if (metrics.ageDays < 7) prob = Math.min(85, prob + 10);   // new number

        if (metrics.avgDailyMessages > 200) prob = Math.min(90, prob + 15);

        // Dampeners
        if (metrics.ageDays > 30 && metrics.blocksReceived === 0) {
            prob = Math.max(2, prob - 15);
        }

        return Math.round(Math.max(1, Math.min(95, prob)));
    }
}

// ── Export ────────────────────────────────────────────────────────────────────

module.exports = HealthScorer;
module.exports.WEIGHTS     = WEIGHTS;
module.exports.RISK_LEVELS = RISK_LEVELS;
