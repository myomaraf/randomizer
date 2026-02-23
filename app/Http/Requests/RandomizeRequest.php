<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RandomizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $uuids = $this->input('uuids');

        if (! is_array($uuids)) {
            return;
        }

        $this->merge([
            'uuids' => array_map(
                static fn ($uuid) => is_string($uuid) ? trim($uuid) : $uuid,
                $uuids
            ),
        ]);
    }

    public function rules(): array
    {
        $maxUuids = max((int) config('omaraf.max_uuids', 20000), 1);

        return [
            'uuids' => ['required', 'array', 'min:1', 'max:'.$maxUuids],
            'uuids.*' => [
                'required',
                'string',
                'regex:/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
            ],
        ];
    }

    public function messages(): array
    {
        $maxUuids = max((int) config('omaraf.max_uuids', 20000), 1);

        return [
            'uuids.required' => 'The uuids field is required.',
            'uuids.array' => 'The uuids field must be an array of UUID strings.',
            'uuids.min' => 'The uuids field must contain at least one UUID.',
            'uuids.max' => "The uuids field may not contain more than {$maxUuids} UUIDs.",
            'uuids.*.required' => 'Each UUID value is required.',
            'uuids.*.string' => 'Each UUID value must be a string.',
            'uuids.*.regex' => 'Each element in uuids must be a valid UUID (versions 1-5).',
        ];
    }
}
