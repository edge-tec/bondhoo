<?php

declare(strict_types=1);

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'interview_type' => 'required|string|in:video,phone,in_person',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'timezone' => 'nullable|string|max:50',
            'meeting_url' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'instructions' => 'nullable|string|max:2000',
        ];
    }
}
