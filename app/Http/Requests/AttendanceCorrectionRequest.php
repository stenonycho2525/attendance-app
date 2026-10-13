<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['required', 'date_format:H:i'],
            'new_break_in' => ['nullable', 'array'],
            'new_break_in.*' => ['nullable', 'date_format:H:i'],
            'new_break_out' => ['nullable', 'array'],
            'new_break_out.*' => ['nullable', 'date_format:H:i'],
            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時間を入力してください',
            'new_clock_in.date_format' => '出勤時間は「09:00」の形式で入力してください',
            'new_clock_out.required' => '退勤時間を入力してください',
            'new_clock_out.date_format' => '退勤時間は「18:00」の形式で入力してください',
            'new_break_in.*.date_format' => '休憩時間は「12:00」の形式で入力してください',
            'new_break_out.*.date_format' => '休憩時間は「13:00」の形式で入力してください',
            'comment.required' => '備考を記入してください',
            'comment.max' => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['new_clock_in', 'new_clock_out'])) {
                return;
            }

            $clockIn = $this->input('new_clock_in');
            $clockOut = $this->input('new_clock_out');

            if ($clockIn > $clockOut) {
                $validator->errors()->add('new_clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            $breakIns = $this->input('new_break_in', []);
            $breakOuts = $this->input('new_break_out', []);

            foreach (array_keys($breakIns + $breakOuts) as $index) {
                if ($validator->errors()->hasAny(["new_break_in.$index", "new_break_out.$index"])) {
                    continue;
                }

                $breakIn = $breakIns[$index] ?? null;
                $breakOut = $breakOuts[$index] ?? null;

                if (!$breakIn && !$breakOut) {
                    continue;
                }

                if (!$breakIn || $breakIn < $clockIn || $breakIn > $clockOut) {
                    $validator->errors()->add("new_break_in.$index", '休憩時間が不適切な値です');
                } elseif (!$breakOut || $breakOut < $breakIn) {
                    $validator->errors()->add("new_break_out.$index", '休憩時間が不適切な値です');
                } elseif ($breakOut > $clockOut) {
                    $validator->errors()->add("new_break_out.$index", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }

    public function breaks(): array
    {
        $breakIns = $this->input('new_break_in', []);
        $breakOuts = $this->input('new_break_out', []);

        $breaks = [];
        foreach ($breakIns as $index => $breakIn) {
            if ($breakIn && !empty($breakOuts[$index])) {
                $breaks[] = ['break_in' => $breakIn, 'break_out' => $breakOuts[$index]];
            }
        }

        return $breaks;
    }
}
