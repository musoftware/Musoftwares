<?php

namespace Modules\Tools\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tools\Models\Tool;
use Modules\Tools\Models\ToolPricingPlan;
use Modules\Tools\Models\ToolVersion;

class ToolsSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            [
                'tool' => [
                    'title'             => 'TikTok Intelligence Engine',
                    'slug'              => 'tiktok-intelligence',
                    'short_description' => 'Discover viral creators, monitor competitors, track UGC trends, and build actionable market intelligence pipelines.',
                    'description'       => "The TikTok Intelligence Engine is a production-grade intelligence platform that runs entirely locally on your machine.\n\n- Discover viral creators by niche and engagement\n- Track competitors and hashtag trends automatically\n- Build a local UGC vault of top-performing content\n- Extract leads and contact data\n- Ensure privacy with 100% local SQLite storage",
                    'category'          => 'intelligence',
                    'supported_os'      => ['windows', 'mac', 'linux'],
                    'is_featured'       => true,
                    'is_active'         => true,
                    'runner_component'  => 'tiktok-intelligence',
                    'features'          => [
                        'Creator Discovery Engine',
                        'Competitor Monitoring Jobs',
                        'UGC Vault & Analytics',
                        'Lead Intelligence Pipeline',
                        'Anti-detection Stealth Mode',
                        'Local Execution Environment'],
                    'requirements'      => [
                        'Musoftware Runtime Agent running locally',
                        'Internet connection required for execution']],
                'plans' =>[
                    [
                        'name'           => 'Creator Intelligence',
                        'price_monthly'  => 19.99,
                        'price_yearly'   => 190.00,
                        'is_popular'     => false,
                        'features'       => [
                            'Creator Discovery Engine',
                            '10 Active Monitoring Jobs',
                            'UGC Vault Access',
                            'Email support']],
                    [
                        'name'           => 'Market Intelligence',
                        'price_monthly'  => 49.99,
                        'price_yearly'   => 490.00,
                        'is_popular'     => true,
                        'features'       => [
                            'Lead Enrichment Pipeline',
                            'Unlimited Monitoring Jobs',
                            'Competitor Timeline Feed',
                            'Priority support']]],
                'version' => '3.0.0'],
            [
                'tool' => [
                    'title'             => 'Email Prospector',
                    'slug'              => 'email-prospector',
                    'short_description' => 'Find and verify professional emails from LinkedIn, websites, and directories.',
                    'description'       => "Email Prospector automates lead generation by extracting and verifying email addresses from multiple sources.\n\n- Scrape emails from any website\n- LinkedIn integration (bring your own account)\n- Real-time email verification\n- CRM export formats",
                    'category'          => 'scraper',
                    'supported_os'      => ['windows'],
                    'is_featured'       => false,
                    'is_active'         => true,
                    'features'          => [
                        'Multi-source scraping',
                        'Real-time email verification',
                        'LinkedIn integration',
                        'CRM-ready CSV export',
                        'Duplicate removal',
                        'Domain blacklist filter'],
                    'requirements'      => [
                        'Windows 10 or later',
                        '2 GB RAM minimum',
                        'Active internet connection']],
                'plans' => [
                    [
                        'name'           => 'Solo',
                        'price_monthly'  => 0.99,
                        'price_yearly'   => 5.00,
                        'is_popular'     => false,
                        'features'       => [
                            '1,000 emails/day',
                            'CSV export']],
                    [
                        'name'           => 'Growth',
                        'price_monthly'  => 1.99,
                        'price_yearly'   => 15.00,
                        'is_popular'     => true,
                        'features'       => [
                            '10,000 emails/day',
                            'Verification included',
                            'CRM export']]],
                'version' => '1.3.0'],
            [
                'tool' => [
                    'title'             => 'Competitor Intelligence',
                    'slug'              => 'competitor-intel',
                    'short_description' => 'Monitor competitor ads, pricing, and content changes in real time.',
                    'description'       => "Stay ahead of your competition with automated monitoring.\n\n- Track Facebook, TikTok, and Google Ads\n- Price monitoring with alert notifications\n- Website change detection\n- Weekly report generation",
                    'category'          => 'monitoring',
                    'supported_os'      => ['windows', 'mac', 'linux'],
                    'is_featured'       => true,
                    'is_active'         => true,
                    'features'          => [
                        'Facebook & TikTok Ad spy',
                        'Price tracking with alerts',
                        'Website change detection',
                        'Competitor keyword tracking',
                        'Automated weekly reports',
                        'Slack / Email notifications'],
                    'requirements'      => [
                        'Windows 10 / macOS 12 / Ubuntu 20+',
                        '4 GB RAM',
                        'Internet connection']],
                'plans' => [
                    [
                        'name'           => 'Watcher',
                        'price_monthly'  => 0.99,
                        'price_yearly'   => 5.00,
                        'is_popular'     => false,
                        'features'       => ['5 competitors', 'Weekly reports']],
                    [
                        'name'           => 'Hunter',
                        'price_monthly'  => 1.99,
                        'price_yearly'   => 15.00,
                        'is_popular'     => true,
                        'features'       => ['Unlimited competitors', 'Daily reports', 'Slack alerts', 'API access']]],
                'version' => '3.0.1'],

            // ── TikTok Intelligence Suite ────────────────────────────────────
            [
                'tool' => [
                    'title'              => 'Viral Autopsy',
                    'slug'               => 'viral-autopsy',
                    'short_description'  => 'Paste any TikTok URL — get a full breakdown of WHY it went viral with a 0-100 Viral Score.',
                    'description'        => "Viral Autopsy analyzes any TikTok video across 5 dimensions to explain exactly why it performed the way it did.\n\n- Caption & Hook psychology analysis\n- Engagement metrics vs. TikTok benchmarks\n- Sound strategy evaluation\n- Content structure breakdown\n- Posting timing analysis\n- Actionable improvement suggestions\n\nGet a Viral Score (0-100) with detailed per-dimension breakdown.",
                    'category'           => 'intelligence',
                    'supported_os'       => ['windows', 'mac', 'linux'],
                    'is_featured'        => true,
                    'is_active'          => true,
                    'is_free'            => true,
                    'runner_component'   => 'viral-autopsy',
                    'features'           => [
                        '5-dimension viral analysis',
                        'Viral Score (0-100)',
                        'Caption psychology breakdown',
                        'Engagement benchmarking',
                        'Sound strategy evaluation',
                        'Improvement suggestions'],
                    'requirements'       => [
                        'Musoftware Runtime Agent running locally',
                        'Internet connection']],
                'plans' => [
                    [
                        'name'           => 'Free',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => false,
                        'features'       => ['3 analyses/day', 'Basic report']],
                    [
                        'name'           => 'Pro',
                        'price_monthly'  => 1.99,
                        'price_yearly'   => 15.00,
                        'is_popular'     => true,
                        'features'       => ['Unlimited analyses', 'Full report + JSON export', 'Priority support']]],
                'version' => '1.0.0'],
            [
                'tool' => [
                    'title'              => 'Hook Analyzer',
                    'slug'               => 'hook-analyzer',
                    'short_description'  => 'Analyze TikTok hooks — get a Hook Score, pattern detection, and specific suggestions to improve retention.',
                    'description'        => "Hook Analyzer scores the first 3 seconds of any TikTok video using proven hook pattern matching.\n\n- Hook Score (0-100) with grade (S/A/B/C/D/F)\n- Pattern detection (curiosity gap, scroll stopper, POV, etc.)\n- Power word analysis\n- Retention estimation\n- Alternative hook suggestions\n- Batch analysis mode (compare multiple videos)",
                    'category'           => 'intelligence',
                    'supported_os'       => ['windows', 'mac', 'linux'],
                    'is_featured'        => true,
                    'is_active'          => true,
                    'is_free'            => true,
                    'runner_component'   => 'hook-analyzer',
                    'features'           => [
                        'Hook Score grading system',
                        '10+ hook pattern recognition',
                        'Batch analysis mode',
                        'Power word analysis',
                        'Alternative hook generator',
                        'Retention prediction'],
                    'requirements'       => [
                        'Musoftware Runtime Agent running locally',
                        'Internet connection']],
                'plans' => [
                    [
                        'name'           => 'Free',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => false,
                        'features'       => ['5 analyses/day', 'Single URL mode']],
                    [
                        'name'           => 'Pro',
                        'price_monthly'  => 1.99,
                        'price_yearly'   => 15.00,
                        'is_popular'     => true,
                        'features'       => ['Unlimited analyses', 'Batch mode (10 URLs)', 'JSON export']]],
                'version' => '1.0.0'],
            [
                'tool' => [
                    'title'              => 'Format DNA Extractor',
                    'slug'               => 'format-extractor',
                    'short_description'  => 'Extract the viral format blueprint from any TikTok video — reusable hook templates, structure patterns, and sound strategy.',
                    'description'        => "Format DNA Extractor reverse-engineers viral TikTok formats into reusable blueprints.\n\nNot copying content — copying the psychology.\n\n- Hook template with fill-in-the-blank format\n- Content structure detection (6 proven structures)\n- Sound strategy extraction\n- Caption format template\n- Duration recommendation\n- Copyable blueprint for your own content",
                    'category'           => 'intelligence',
                    'supported_os'       => ['windows', 'mac', 'linux'],
                    'is_featured'        => true,
                    'is_active'          => true,
                    'is_free'            => true,
                    'runner_component'   => 'format-extractor',
                    'features'           => [
                        'Hook template extraction',
                        '6 content structure patterns',
                        'Sound strategy analysis',
                        'Caption format template',
                        'Copyable text blueprint',
                        'Niche-aware recommendations'],
                    'requirements'       => [
                        'Musoftware Runtime Agent running locally',
                        'Internet connection']],
                'plans' => [
                    [
                        'name'           => 'Free',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => false,
                        'features'       => ['3 extractions/day']],
                    [
                        'name'           => 'Pro',
                        'price_monthly'  => 1.99,
                        'price_yearly'   => 15.00,
                        'is_popular'     => true,
                        'features'       => ['Unlimited extractions', 'Blueprint library (coming soon)']]],
                'version' => '1.0.0']];

        foreach ($tools as $entry) {
            $tool = Tool::firstOrCreate(
                ['slug' => $entry['tool']['slug']],
                array_merge($entry['tool'], [
                    'current_version' => $entry['version'],
                    'download_count'  => rand(100, 5000)])
            );

            foreach ($entry['plans'] as $planData) {
                ToolPricingPlan::firstOrCreate(
                    ['tool_id' => $tool->id, 'name' => $planData['name']],
                    array_merge($planData, [
                        'tool_id' => $tool->id])
                );
            }

            ToolVersion::firstOrCreate(
                ['tool_id' => $tool->id, 'version' => $entry['version']],
                [
                    'tool_id'     => $tool->id,
                    'version'     => $entry['version'],
                    'is_latest'   => true,
                    'is_beta'     => false,
                    'changelog'   => "Initial release of {$entry['tool']['title']}.",
                    'released_at' => now()]
            );
        }

        $this->command->info('✅  Tools Marketplace seeded: ' . count($tools) . ' tools');
    }
}
