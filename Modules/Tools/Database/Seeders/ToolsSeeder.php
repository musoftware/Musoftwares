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
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => false,
                        'features'       => [
                            'Creator Discovery Engine',
                            '10 Active Monitoring Jobs',
                            'UGC Vault Access',
                            'Email support']],
                    [
                        'name'           => 'Market Intelligence',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => true,
                        'features'       => [
                            'Lead Enrichment Pipeline',
                            'Unlimited Monitoring Jobs',
                            'Competitor Timeline Feed',
                            'Priority support']]],
                'version' => '3.0.0'],
            [
                'tool' => [
                    'title'             => 'Lead Intelligence Engine',
                    'slug'              => 'lead-intelligence',
                    'short_description' => 'Enterprise-grade lead discovery, enrichment, and cold email outreach built directly into your local runtime.',
                    'description'       => "The Lead Intelligence Engine is a complete B2B prospecting ecosystem designed to rival Apollo.io and Instantly.ai, but running 100% locally to ensure data privacy and maximum deliverability.\n\n- Find verified decision-maker emails across LinkedIn and millions of websites\n- AI Lead Enrichment using local pipelines\n- Built-in multi-source scraper (LinkedIn, Google Maps, Web)\n- Unlimited local email verification (SMTP/MX checks without cloud costs)\n- AI-generated cold outreach sequences\n- Connect unlimited IMAP/SMTP inboxes with local IP sending",
                    'category'          => 'intelligence',
                    'supported_os'      => ['windows', 'mac', 'linux'],
                    'is_featured'       => true,
                    'is_active'         => true,
                    'runner_component'  => 'lead-intelligence',
                    'features'          => [
                        'Multi-source scraping (LinkedIn/Maps/Web)',
                        'Unlimited local email verification',
                        'AI Lead Enrichment & Scoring',
                        'AI Outreach Sequence Generator',
                        'Unlimited Inbox Connections',
                        'Local SQLite Privacy'],
                    'requirements'      => [
                        'Musoftware Runtime Agent running locally',
                        'Internet connection required']],
                'plans' => [
                    [
                        'name'           => 'Prospector',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => false,
                        'features'       => [
                            '10,000 scrapes/day',
                            'Basic email verification',
                            '3 Connected Inboxes',
                            'CSV export']],
                    [
                        'name'           => 'Enterprise Intelligence',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => true,
                        'features'       => [
                            'Unlimited scraping limits',
                            'Advanced AI Enrichment',
                            'AI Sequence Generation',
                            'Unlimited Inboxes',
                            'API Access']]],
                'version' => '2.0.0'],
            [
                'tool' => [
                    'title'             => 'Competitor Intelligence',
                    'slug'              => 'competitor-intel',
                    'short_description' => 'Monitor competitor ads, pricing, and content changes in real time.',
                    'description'       => "Stay ahead of your competition with automated monitoring.\n\n- Track Facebook, TikTok, and Google Ads\n- Price monitoring with alert notifications\n- Website change detection\n- Weekly report generation",
                    'category'          => 'monitoring',
                    'supported_os'      => ['windows', 'mac', 'linux'],
                    'is_featured'       => true,
                    'is_active'         => false,
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
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => false,
                        'features'       => ['5 competitors', 'Weekly reports']],
                    [
                        'name'           => 'Hunter',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => true,
                        'features'       => ['Unlimited competitors', 'Daily reports', 'Slack alerts', 'API access']]],
                'version' => '3.0.1'],

            // ── WhatsApp Automation Suite ────────────────────────────────────
            [
                'tool' => [
                    'title'             => 'WhatsApp Sender Pro',
                    'slug'              => 'whatsapp-sender-pro',
                    'short_description' => 'Enterprise-grade WhatsApp Operations tool supporting multi-session automation, bulk campaigns, and AI auto-replies.',
                    'description'       => "WhatsApp Sender Pro is a complete enterprise operations platform running as a local runtime plugin.\n\n- Multi-session WhatsApp automation with Playwright\n- Bulk messaging and campaign scheduling\n- AI-powered auto-replies using ChatGPT\n- Robust anti-ban logic with human typing simulation and pacing\n- Local SQLite privacy for all contacts and chat logs",
                    'category'          => 'automation',
                    'supported_os'      => ['windows', 'mac', 'linux'],
                    'is_featured'       => true,
                    'is_active'         => true,
                    'runner_component'  => 'whatsapp-sender-pro',
                    'features'          => [
                        'Multi-session management',
                        'Bulk Campaign Engine',
                        'AI Auto-Replies (GPT-4)',
                        'Anti-Ban pacing & stealth mode',
                        'Local DB storage'],
                    'requirements'      => [
                        'Musoftware Runtime Agent running locally',
                        'Active WhatsApp account on a mobile device',
                        'Internet connection']],
                'plans' => [
                    [
                        'name'           => 'Starter',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => false,
                        'features'       => [
                            '1 Connected Number',
                            '1,000 messages/day',
                            'Basic campaigns']],
                    [
                        'name'           => 'Enterprise',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => true,
                        'features'       => [
                            'Unlimited Numbers',
                            'Unlimited sending limits',
                            'AI Auto-Reply Engine',
                            'API Access']]],
                'version' => '1.0.0'],

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
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
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
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
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
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => true,
                        'features'       => ['Unlimited extractions', 'Blueprint library (coming soon)']]],
                'version' => '1.0.0'],
            [
                'tool' => [
                    'title'             => 'IPTV Downloader & Recorder',
                    'slug'              => 'iptv-downloader',
                    'short_description' => 'A professional-grade operational workspace for loading IPTV playlists, category-browsing channels, and recording live streams or downloading VOD assets locally.',
                    'description'       => "IPTV Downloader & Recorder is a complete live stream capture and playlist management ecosystem running entirely inside your local runtime.\n\n- Load playlists via M3U URL or local file upload\n- Search and filter channels by category or group-title\n- Native, segment-by-segment HLS stream recorder\n- Preset and custom recording duration clocks\n- Live download terminal console and statistics tracking\n- 100% private data kept inside a local SQLite database",
                    'category'          => 'Media',
                    'supported_os'      => ['windows', 'mac', 'linux'],
                    'is_featured'       => true,
                    'is_active'         => true,
                    'is_free'           => true,
                    'runner_component'  => 'IPTVDownloaderRunner',
                    'features'          => [
                        'M3U/M3U8 Playlist Indexer',
                        'Category-based Channel Browser',
                        'Native Segment-by-segment HLS Recorder',
                        'Direct VOD Asset Downloader',
                        'Real-time Download Console & Speedometer',
                        'Private SQLite Storage Layer'
                    ],
                    'requirements'      => [
                        'Musoftware Runtime Agent running locally',
                        'Internet connection'
                    ]
                ],
                'plans' => [
                    [
                        'name'           => 'Free Live Stream Capture',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => true,
                        'features'       => [
                            'Unlimited Playlist Indexing',
                            'Channel Browsing & Bookmarks',
                            'Standard HLS Stream Recording',
                            'Local Download Archive'
                        ]
                    ]
                ],
                'version' => '1.0.0'
            ],
            [
                'tool' => [
                    'title'             => 'Screenshot Feedback Workspace',
                    'slug'              => 'screenshot-feedback',
                    'short_description' => 'A clean, operational workspace for UI review, feedback pins, and screenshot organization.',
                    'description'       => "Screenshot Feedback Workspace is a visual review tool that lets you drop pin-based comments directly on design screenshots.\n\n- Upload and organize screenshots by project\n- Place precise feedback pins on visual assets\n- Filter comment threads by Open, Resolved, or All\n- Sync issues in real time with local SQLite storage",
                    'category'          => 'Productivity',
                    'supported_os'      => ['windows', 'mac', 'linux'],
                    'is_featured'       => true,
                    'is_active'         => true,
                    'is_free'           => true,
                    'runner_component'  => 'ScreenshotFeedbackRunner',
                    'features'          => [
                        'Interactive Annotator Canvas',
                        'Precision coordinate pinning',
                        'Comment thread status filtering (Open/Resolved)',
                        'Synced workspace timelines',
                        'Local private SQLite storage'
                    ],
                    'requirements'      => [
                        'Musoftware Runtime Agent running locally',
                        'Internet connection'
                    ]
                ],
                'plans' => [
                    [
                        'name'           => 'Free Reviewer',
                        'price_monthly'  => 0,
                        'price_yearly'   => 0,
                        'is_popular'     => true,
                        'features'       => [
                            'Unlimited Pins',
                            'Interactive Canvas popovers',
                            'Sidebar Status filters',
                            'Offline-first SQLite DB'
                        ]
                    ]
                ],
                'version' => '1.0.0'
            ]
        ];

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
                    'file_path'   => "plugins/{$tool->slug}.zip",
                    'file_name'   => "{$tool->slug}.zip",
                    'changelog'   => "Initial release of {$entry['tool']['title']}.",
                    'released_at' => now()]
            );
        }

        $this->command->info('✅  Tools Marketplace seeded: ' . count($tools) . ' tools');
    }
}

