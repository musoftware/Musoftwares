<?php

namespace App\Http\Requests\Admin\BlogArticle;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']); // Assuming admin middleware handles auth
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id'       => 'nullable|exists:marketplace_services,id',
            'language'         => 'required|string|max:5',
            'group_id'         => 'nullable|integer',
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255|unique:blog_articles,slug',
            'content'          => 'required|string',
            'excerpt'          => 'nullable|string',
            'featured_image'   => 'nullable|string', // Usually a URL or path, assuming handled by media library or simple string upload
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'variation_group'  => 'nullable|string|max:255',
            'cycle_number'     => 'nullable|integer',
            'is_published'     => 'boolean',
            'published_at'     => 'nullable|date',
        ];
    }
}
