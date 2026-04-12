<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class SubmitExamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        if (!$exam) {
            return false;
        }

        return (int) $exam->user_id === (int) $this->user()?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|distinct|exists:questions,id',
            'answers.*.selected_index' => 'required|integer|min:0|max:3',
            'answers.*.time_spent' => 'nullable|integer|min:0|max:7200',
        ];
    }
}
