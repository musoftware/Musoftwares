<?php

namespace Modules\AffiliatePos\Traits;

use Modules\AffiliatePos\Models\Tag;
use Illuminate\Support\Str;

trait Taggable
{
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable', 'affiliate_pos_taggables');
    }

    public function syncTags($tags)
    {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }
        
        $tagIds = [];
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                $tagModel = Tag::firstOrCreate([
                    'tenant_id' => $this->tenant_id,
                    'name' => $tag
                ], [
                    'slug' => Str::slug($tag)
                ]);
                $tagIds[] = $tagModel->id;
            }
        }
        
        $this->tags()->sync($tagIds);
    }
}
