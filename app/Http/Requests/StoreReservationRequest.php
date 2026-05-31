<?php

namespace App\Http\Requests;

use App\Services\ReservationPricingService;
use App\Services\ReservationSlotService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reserved_on' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'consumables' => ['nullable', 'array'],
            'consumables.*' => ['string', 'in:'.implode(',', ReservationPricingService::consumableIds())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reserved_on.required' => '日付を選択してください。',
            'start_time.required' => '開始時間を選択してください。',
            'end_time.required' => '終了時間を選択してください。',
            'end_time.after' => '終了時間は開始時間より後にしてください。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $service = app(ReservationSlotService::class);
            $date = Carbon::parse($this->input('reserved_on'))->startOfDay();
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            if (! $service->isDateBookable($date)) {
                $validator->errors()->add('reserved_on', $date->isToday()
                    ? '当日分のご予約はお電話にて承ります。'
                    : '選択できない日付です。');

                return;
            }

            $start = Carbon::createFromTimeString($startTime);
            $end = Carbon::createFromTimeString($endTime);
            $duration = $start->diffInMinutes($end);

            if ($duration < ReservationSlotService::MIN_BOOKING_MINUTES) {
                $validator->errors()->add('start_time', '最低2時間からご予約いただけます。');

                return;
            }

            if ($duration % ReservationSlotService::SLOT_MINUTES !== 0) {
                $validator->errors()->add('end_time', '予約時間は30分単位でお選びください。');

                return;
            }

            $open = Carbon::createFromTimeString(ReservationSlotService::OPEN_TIME);
            $close = Carbon::createFromTimeString(ReservationSlotService::CLOSE_TIME);

            if ($start->lt($open) || $end->gt($close)) {
                $validator->errors()->add('start_time', '利用時間は9:00〜21:00の範囲でお選びください。');

                return;
            }

            if (! $service->rangeUsesOnlyAvailableSlots($date, $startTime, $endTime)) {
                $validator->errors()->add('start_time', '選択した時間帯は予約できません。');

                return;
            }

            if ($service->hasConflict($date, $startTime, $endTime)) {
                $validator->errors()->add('start_time', '既存の予約（前後30分のブロック含む）と重なります。');
            }
        });
    }
}
