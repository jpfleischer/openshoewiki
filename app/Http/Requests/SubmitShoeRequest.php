<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitShoeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'english_name' => ['required', 'string', 'max:255'],
            'foreign_name' => ['nullable', 'string', 'max:255'],
            'brand_id' => ['required', 'string', 'uuid', 'exists:brands,id'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['string', 'uuid', 'exists:categories,id'],
            'feature_ids' => ['nullable', 'array'],
            'feature_ids.*' => ['string', 'uuid', 'exists:features,id'],
            'color_ids' => ['nullable', 'array'],
            'color_ids.*' => ['string', 'uuid', 'exists:colors,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['string', 'uuid', 'exists:tags,id'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'product_number' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::in(array_keys(\App\Models\Item::CURRENCIES))],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:5120'],
        ];
    }
}
