<?php

namespace App\Http\Requests;

use App\Models\Exercise;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $query = Exercise::query()
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $value))]);

                    $exercise = $this->route('exercise');

                    if ($exercise instanceof Exercise) {
                        $query->whereKeyNot($exercise->getKey());
                    }

                    if ($query->exists()) {
                        $fail('The name has already been taken.');
                    }
                },
            ],
            'muscle_group' => [
                'required',
                'string',
                'max:60',
            ],
            'equipment_type' => [
                'nullable',
                'string',
                'max:60',
            ],
            'video_url' => [
                'nullable',
                'url',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
