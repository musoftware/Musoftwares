<?php

// Fix double-encoded JSON arrays in tools tables.
// The data is stored as: "[\"windows\",\"mac\"]" (a JSON-encoded string of a JSON array)
// When Eloquent casts with 'array', it json_decodes to the string: ["windows","mac"]
// Then json_decodes again to get: ["windows","mac"] as PHP array... 
// But if already double-encoded, it ends up as a string.

use Modules\Tools\Models\Tool;
use Modules\Tools\Models\ToolPricingPlan;

$fixed = 0;

Tool::all()->each(function ($t) use (&$fixed) {
    $rawOs    = $t->getRawOriginal('supported_os');
    $rawFeats = $t->getRawOriginal('features');
    $rawReqs  = $t->getRawOriginal('requirements');

    $changed = false;

    // Raw value in DB is a quoted JSON string: "\"[...]\"" 
    // When we json_decode it, we might get a string like: "[\"windows\",\"mac\"]"
    // So we need to double-decode

    $fixField = function($raw) {
        if (is_null($raw)) return null;
        $first = json_decode($raw, true);
        if (is_array($first)) return $first;       // already a proper array
        if (is_string($first)) {
            $second = json_decode($first, true);
            if (is_array($second)) return $second;  // was double-encoded
        }
        return null; // can't fix
    };

    $fixedOs = $fixField($rawOs);
    if ($fixedOs !== null && !is_array(json_decode($rawOs, true))) {
        // Need to re-save with proper single-encoded value
        \DB::table('tools')->where('id', $t->id)->update(['supported_os' => json_encode($fixedOs)]);
        $changed = true;
    }

    $fixedFeats = $fixField($rawFeats);
    if ($fixedFeats !== null && !is_array(json_decode($rawFeats, true))) {
        \DB::table('tools')->where('id', $t->id)->update(['features' => json_encode($fixedFeats)]);
        $changed = true;
    }

    $fixedReqs = $fixField($rawReqs);
    if ($fixedReqs !== null && !is_array(json_decode($rawReqs, true))) {
        \DB::table('tools')->where('id', $t->id)->update(['requirements' => json_encode($fixedReqs)]);
        $changed = true;
    }

    if ($changed) {
        $fixed++;
        echo "Fixed Tool #{$t->id}: {$t->title}\n";
    }
});

ToolPricingPlan::all()->each(function ($p) use (&$fixed) {
    $rawFeats = $p->getRawOriginal('features');

    $fixField = function($raw) {
        if (is_null($raw)) return null;
        $first = json_decode($raw, true);
        if (is_array($first)) return $first;
        if (is_string($first)) {
            $second = json_decode($first, true);
            if (is_array($second)) return $second;
        }
        return null;
    };

    $fixedFeats = $fixField($rawFeats);
    if ($fixedFeats !== null && !is_array(json_decode($rawFeats, true))) {
        \DB::table('tool_pricing_plans')->where('id', $p->id)->update(['features' => json_encode($fixedFeats)]);
        $fixed++;
        echo "Fixed Plan #{$p->id}: {$p->name}\n";
    }
});

echo "Total fixed: {$fixed} records\n";

// Verify
echo "\nVerification:\n";
$t = Tool::first();
echo "Tool supported_os type: " . gettype($t->supported_os) . "\n";
print_r($t->supported_os);
$p = ToolPricingPlan::first();
echo "\nPlan features type: " . gettype($p->features) . "\n";
print_r($p->features);
