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
        //★固定ルールと動的ルールを合わせて適用させるために、一度$rulesにおく。
        $rules = [
            'clock_in_time' => ['required', 'date_format:H:i'],
            'clock_out_time' => ['required', 'date_format:H:i', 'after:clock_in_time'],
            'note' => ['required', 'string', 'max:255']
        ];

        //★動的なルール生成。実際に存在する休憩データだけをループ
        foreach ($this->input('rests', []) as $index => $rest) {
            //★例　$indexが0なら、rests.0.start_timeというキーのルールが追加される
            $restStartTimeRules = ['nullable', 'date_format:H:i'];
            $restEndTimeRules = ['nullable', 'date_format:H:i', 'after:rests.'.$index.'.start_time'];
            //★休憩開始と終了がどちらか片方だけ入力されている場合はエラー
            if (isset($rest['start_time']) && !isset($rest['end_time'])) {
                $restEndTimeRules[] = 'required_with:rests.'.$index.'.start_time';
            }
            if (!isset($rest['start_time']) && isset($rest['end_time'])) {
                $restStartTimeRules[] = 'required_with:rests.'.$index.'.end_time';
            }
            // 最終的なルールを$rules配列に追加
            $rules["rests.{$index}.start_time"] = $restStartTimeRules;
            $rules["rests.{$index}.end_time"] = $restEndTimeRules;
        }
        //★全てのルールを定義し終えたあとにreturnする。
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

        //★休憩時間のバリデーションメッセージを動的に追加
        foreach ($this->input('rests', []) as $index => $rest) {
            $messages["rests.{$index}.start_time.date_format"] = '休憩開始時間は時刻形式で入力してください';
            $messages["rests.{$index}.end_time.date_format"] = '休憩終了時間は時刻形式で入力してください';
            $messages["rests.{$index}.end_time.after"] = '休憩終了時間は休憩開始時間より後にしてください';

            $messages["rests.{$index}.start_time.required_with"] = '休憩開始時間と休憩終了時間は両方入力してください';
            $messages["rests.{$index}.end_time.required_with"] = '休憩開始時間と休憩終了時間は両方入力してください';
        }
        return $messages;
    }

    //★他のバリデーションが全て成功した後に実行される
    public function withValidator($validator)
    {
        //★カスタムバリデーションルールを追加
        $validator->after(function ($validator) {
            $clockIn = $this->input('clock_in_time');
            $clockOut = $this->input('clock_out_time');
            //★Carbonインスタンスに変換（存在する場合のみ）
            // ここに到達した時点で必ず値が存在し、date_format:H:i を満たしているはず
            $parsedClockIn = Carbon::parse($clockIn);
            $parsedClockOut = Carbon::parse($clockOut);

            //★休憩開始時刻および休憩終了時間が出勤時間および退勤時間を越えている場合のバリデーション
            foreach ($this->input('rests', []) as $index => $rest) {
                $restStart = $rest['start_time'] ?? null;
                $restEnd = $rest['end_time'] ?? null;

                //★休憩開始と終了の両方が入力されている場合のみチェック
                if ($restStart && $restEnd) {
                    $parsedRestStart = Carbon::parse($restStart);
                    $parsedRestEnd = Carbon::parse($restEnd);

                    // ★簡略化: clock_in_time と clock_out_time は required なので、
                    // ここでは両方存在することを前提にチェックできる
                    if ($parsedRestStart->lt($parsedClockIn) || $parsedRestEnd->gt($parsedClockOut)) {
                        $validator->errors()->add("rests.{$index}.start_time", '休憩時間が勤務時間外です');
                        //$validator->errors()->add("rests.{$index}.end_time", '休憩時間が勤務時間外です');
                    }
                }
            }
        });
    }
}
