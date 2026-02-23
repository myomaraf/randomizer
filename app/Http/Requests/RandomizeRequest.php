<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RandomizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ticketUuids = $this->input('ticket_uuids');

        if (! is_array($ticketUuids)) {
            return;
        }

        $this->merge([
            'ticket_uuids' => array_map(
                static fn ($uuid) => is_string($uuid) ? trim($uuid) : $uuid,
                $ticketUuids
            ),
        ]);
    }

    public function rules(): array
    {
        $maxUuids = max((int) config('omaraf.max_uuids', 20000), 1);

        return [
            'raffle_id' => ['required', 'string', 'max:100', 'unique:raffles,raffle_id'],
            'ticket_uuids' => ['required', 'array', 'min:1', 'max:'.$maxUuids],
            'ticket_uuids.*' => [
                'required',
                'string',
                'uuid',
            ],
        ];
    }

    public function messages(): array
    {
        $maxUuids = max((int) config('omaraf.max_uuids', 20000), 1);

        return [
            'raffle_id.required' => 'The raffle_id field is required.',
            'raffle_id.string' => 'The raffle_id field must be a string.',
            'raffle_id.max' => 'The raffle_id field may not be greater than 100 characters.',
            'raffle_id.unique' => 'raffle_id already exists',
            'ticket_uuids.required' => 'The ticket_uuids field is required.',
            'ticket_uuids.array' => 'The ticket_uuids field must be an array of UUID strings.',
            'ticket_uuids.min' => 'The ticket_uuids field must contain at least one UUID.',
            'ticket_uuids.max' => "The ticket_uuids field may not contain more than {$maxUuids} UUIDs.",
            'ticket_uuids.*.required' => 'Each ticket UUID value is required.',
            'ticket_uuids.*.string' => 'Each ticket UUID value must be a string.',
            'ticket_uuids.*.uuid' => 'Each element in ticket_uuids must be a valid UUID.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $failed = $validator->failed();

        if (isset($failed['raffle_id']['Unique'])) {
            throw new HttpResponseException(response()->json([
                'message' => 'raffle_id already exists',
            ], 409));
        }

        parent::failedValidation($validator);
    }
}
