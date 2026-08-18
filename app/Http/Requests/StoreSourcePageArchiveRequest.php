<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSourcePageArchiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->editor() ?? false;
    }

    public function rules(): array
    {
        return [
            'source_url' => ['required', 'url', 'max:2000'],
            'title' => ['nullable', 'string', 'max:1000'],
            'html' => ['required', 'string', 'max:5242880'],
            'captured_at' => ['nullable', 'date'],
        ];
    }
}
