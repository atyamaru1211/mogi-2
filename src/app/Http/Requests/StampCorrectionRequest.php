<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StampCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'clock_in_time' => ['required', 'date_format:H:i'],
            'clock_out_time' => ['required', 'date_format:H:i', 'after:clock_in_time'],
            'note' => ['required', 'string', 'max:255']
        ];

        foreach ($this->input('rests', []) as $index => $rest) {
            $restStartTimeRules = ['nullable', 'date_format:H:i'];
            $restEndTimeRules = ['nullable', 'date_format:H:i', 'after:rests.'.$index.'.start_time'];
            if (isset($rest['start_time']) && !isset($rest['end_time'])) {
                $restEndTimeRules[] = 'required_with:rests.'.$index.'.start_time';
            }
            if (!isset($rest['start_time']) && isset($rest['end_time'])) {
                $restStartTimeRules[] = 'required_with:rests.'.$index.'.end_time';
            }
            $rules["rests.{$index}.start_time"] = $restStartTimeRules;
            $rules["rests.{$index}.end_time"] = $restEndTimeRules;
        }
        return $rules;
    }

    public function messages()
    {
        $messages = [
            'clock_in_time.required' => '出勤時間を入力してください',
            'clock_in_time.date_format' => '出勤時間は時刻形式で入力してください',
            'clock_out_time.required' => '退勤時間を入力してください',
            'clock_out_time.date_format' => '退勤時間は時刻形式で入力してください',
            'clock_out_time.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'note.required' => '備考を記入してください',
            'note.string' => '備考は文字列で入力してください',
            'note.max' => '備考は255文字以内で入力してください',
        ];

        foreach ($this->input('rests', []) as $index => $rest) {
            $messages["rests.{$index}.start_time.date_format"] = '休憩開始時間は時刻形式で入力してください';
            $messages["rests.{$index}.end_time.date_format"] = '休憩終了時間は時刻形式で入力してください';
            $messages["rests.{$index}.end_time.after"] = '休憩終了時間は休憩開始時間より後にしてください';

            $messages["rests.{$index}.start_time.required_with"] = '休憩開始時間と休憩終了時間は両方入力してください';
            $messages["rests.{$index}.end_time.required_with"] = '休憩開始時間と休憩終了時間は両方入力してください';
        }
        return $messages;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('clock_in_time');
            $clockOut = $this->input('clock_out_time');
            $parsedClockIn = Carbon::parse($clockIn);
            $parsedClockOut = Carbon::parse($clockOut);

            foreach ($this->input('rests', []) as $index => $rest) {
                $restStart = $rest['start_time'] ?? null;
                $restEnd = $rest['end_time'] ?? null;

                if ($restStart && $restEnd) {
                    $parsedRestStart = Carbon::parse($restStart);
                    $parsedRestEnd = Carbon::parse($restEnd);

                    if ($parsedRestStart->lt($parsedClockIn) || $parsedRestStart->gt($parsedClockOut)) {
                        $validator->errors()->add("rests.{$index}.start_time", '休憩時間が勤務時間外です');
                    }
                    if ($parsedRestEnd->lt($parsedClockIn) || $parsedRestEnd->gt($parsedClockOut)) {
                        $validator->errors()->add("rests.{$index}.end_time", '休憩時間が勤務時間外です');
                    }
                }
            }
        });
    }
}
