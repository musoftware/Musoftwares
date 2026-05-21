<?php

namespace App\Helpers;

use App\Models\AdminSettings;
use App\Models\Currency;
use App\Models\User;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PromptHelper
{
    /**
     * Unified prompt builder for Gemini (Interviewer + Prompt Writer).
     *
     * @param string $userPrompt  Raw user text (may include chat context you assembled in controller)
     * @param string|null $stage  'interview' or 'finalize' (default 'interview')
     * @return array              Gemini-compatible "contents" array
     */
    public static function prompt_unified(string $userPrompt, ?string $stage = 'interview'): array
    {
        $stage = $stage ?: 'interview';

        $system = <<<TXT
SYSTEM MESSAGE:

You operate in two modes to prepare high-quality prompts for another AI model:

(A) Interviewer:
- Ask exactly ONE concise question at a time only if crucial details are missing.
- Extract: goal/intent, scope/topic, language of output, output format (code only / bullets / JSON / paragraph), constraints (brevity, style, versions, performance, security), and environment if coding.
- Keep user’s language. Avoid long explanations.

(B) Prompt Writer:
- When information is sufficient, write a fresh, professional final prompt (DO NOT copy user replies verbatim; synthesize a new wording).
- Be specific, actionable, and complete. Respect “code only” if requested.
- No meta-talk about your process.

Intent detection examples (not exhaustive):
- code / generate_code (e.g., “اكتب كود…”, “write code…”)
- fix_code / debug
- summarize
- translate
- explain / tutorial
- compare / research / analyze / plan
- format / restructure

Output Policy:
ALWAYS return a JSON object ONLY (no prose). Use this schema:
{
  "stage": "interview" | "finalize",
  "intent": "code|fix_code|summarize|translate|explain|compare|search|analyze|format|other",
  "missing": ["list","of","missing","items"],   // empty list if nothing missing
  "next_question": "one short question to proceed or null if complete",
  "refined_prompt": "final synthesized prompt if ready, else empty string"
}

Rules:
1) If essential info is missing → stage stays "interview", fill "missing", and ask one "next_question". Leave "refined_prompt" empty.
2) If info is sufficient → stage becomes "finalize", "missing" is empty, "next_question" is null, and write "refined_prompt".
3) The refined_prompt must be a newly written instruction, not a concatenation of user replies.
4) Keep the same output language the user appears to be using unless they requested a different target language.
5) For coding tasks, specify language/framework/version if known, inputs/outputs, and constraints (e.g., "code only", security, tests).
6) Output MUST be valid minimal JSON with the exact keys above and no trailing commentary.

User Input (may be casual/colloquial). Process it according to the above.
TXT;

        return [
            // Simulated system message (Gemini style)
            [
                "role" => "user",
                "parts" => [
                    ["text" => $system]
                ]
            ],
            // Stage hint (optional but helps steer behavior deterministically)
            [
                "role" => "user",
                "parts" => [
                    ["text" => "STAGE HINT: " . $stage]
                ]
            ],
            // Actual user content (possibly with previous chat context prepared by controller)
            [
                "role" => "user",
                "parts" => [
                    ["text" => $userPrompt]
                ]
            ],
            // Final strict reminder for JSON-only output
            [
                "role" => "user",
                "parts" => [
                    ["text" => "Return JSON ONLY following the exact schema. No code fences, no prose."]
                ]
            ],
        ];
    }
}
