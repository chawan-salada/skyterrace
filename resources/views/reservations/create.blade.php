<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            予約
        </h2>
    </x-slot>

    <div class="py-12" x-data="reservationCalendar({
        slotsUrl: @js(route('reservations.slots')),
        storeUrl: @js(route('reservations.store')),
        csrfToken: @js(csrf_token()),
        minBookingMinutes: @js(\App\Services\ReservationSlotService::MIN_BOOKING_MINUTES),
        pricing: @js($pricingConfig),
    })">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">日付を選択</h3>
                <p class="mt-1 text-sm text-gray-600">今月・翌月の2か月からお選びください。当日分はお電話にてご予約ください。</p>

                <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
                    @foreach ($calendars as $calendar)
                        <div>
                            <p class="text-center font-semibold text-gray-800 mb-3">{{ $calendar['label'] }}</p>
                            <div class="calendar-grid text-center text-xs text-gray-500 mb-1">
                                @foreach ($weekdays as $weekday)
                                    <div class="py-1">{{ $weekday }}</div>
                                @endforeach
                            </div>
                            @foreach ($calendar['weeks'] as $week)
                                <div class="calendar-grid">
                                    @foreach ($week as $day)
                                        @if ($day['date'])
                                            @if ($day['is_today'])
                                                <div
                                                    class="calendar-tel-cell aspect-square rounded-md text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 flex flex-col items-center justify-center leading-tight"
                                                    title="当日分のご予約はお電話にて承ります"
                                                >
                                                    <span>TEL</span>
                                                </div>
                                            @else
                                                <button
                                                    type="button"
                                                    @if ($day['selectable'])
                                                        @click="selectDate(@js($day['date']), @js($calendar['label'] . $day['day'] . '日'))"
                                                    @endif
                                                    :class="{
                                                        'bg-indigo-600 text-white ring-2 ring-indigo-400': selectedDate === @js($day['date']),
                                                        'bg-indigo-50 text-indigo-700 hover:bg-indigo-100': @js($day['selectable']) && selectedDate !== @js($day['date']),
                                                        'text-gray-300 cursor-not-allowed': @js(! $day['selectable']),
                                                    }"
                                                    class="aspect-square rounded-md text-sm font-medium transition"
                                                    @if (! $day['selectable']) disabled @endif
                                                >
                                                    {{ $day['day'] }}
                                                </button>
                                            @endif
                                        @else
                                            <div class="aspect-square"></div>
                                        @endif
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-show="selectedDate" x-cloak>
                <h3 class="text-lg font-semibold text-gray-900">
                    時間を選択
                    <span class="text-base font-normal text-gray-600" x-text="selectedDateLabel ? '（' + selectedDateLabel + '）' : ''"></span>
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    9:00〜21:00の間で、30分単位・最低2時間から開始・終了時刻を選んでください。
                </p>
                <p class="mt-1 text-sm text-gray-600">
                    最低利用時間は、2時間となっています。
                </p>

                <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-600">
                    <span class="inline-flex items-center gap-1"><span class="w-4 h-4 rounded border bg-white"></span> 空き</span>
                    <span class="inline-flex items-center gap-1"><span class="w-4 h-4 rounded bg-indigo-600"></span> 選択中</span>
                    <span class="inline-flex items-center gap-1"><span class="w-4 h-4 rounded bg-gray-300"></span> 予約済み・ブロック・過去</span>
                </div>

                <div class="mt-4" x-show="loadingSlots">
                    <p class="text-sm text-gray-500">読み込み中…</p>
                </div>

                <div class="mt-4 space-y-4" x-show="!loadingSlots && slots.length">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700">開始時刻</label>
                            <select
                                id="start_time"
                                x-model="selectionStart"
                                @change="onStartChange()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="">選択してください</option>
                                <template x-for="time in availableStartTimes" :key="'start-' + time">
                                    <option :value="time" x-text="time"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700">終了時刻</label>
                            <select
                                id="end_time"
                                x-model="selectionEnd"
                                @change="onEndChange()"
                                :disabled="!selectionStart"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm disabled:bg-gray-100 disabled:text-gray-500"
                            >
                                <option value="">選択してください</option>
                                <template x-for="time in availableEndTimes" :key="'end-' + time">
                                    <option :value="time" x-text="time"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 mb-2">空き状況（ボタンをクリックしても選択できます）</p>
                        <div class="time-slot-grid">
                            <template x-for="slot in slots" :key="slot.time">
                                <button
                                    type="button"
                                    @click="clickSlot(slot)"
                                    :disabled="slot.status !== 'available'"
                                    :class="slotButtonClass(slot)"
                                    class="rounded-md px-2 py-2 text-sm font-medium border transition"
                                    x-text="slot.time"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-lg bg-gray-50 p-4 text-sm text-gray-700 space-y-2" x-show="selectionStart">
                    <p>
                        <span class="font-medium">開始:</span>
                        <span x-text="selectionStart || '—'"></span>
                    </p>
                    <p>
                        <span class="font-medium">終了:</span>
                        <span x-text="selectionEnd || '終了時刻を選択してください'"></span>
                    </p>
                    <p x-show="selectionError" class="text-red-600" x-text="selectionError"></p>
                </div>

                <div class="mt-8 border-t border-gray-200 pt-6 space-y-6" x-show="selectionEnd" x-cloak>
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">利用料金</h4>
                        <p class="mt-1 text-sm text-gray-600">
                            2時間 10,000円（税込）／以降30分ごとに 2,500円（税込）
                        </p>
                        <p class="mt-3 text-sm text-gray-700">
                            <span class="font-medium">スペース利用料:</span>
                            <span class="ml-2 text-lg font-semibold text-gray-900" x-text="formatYen(rentalFee)"></span>
                        </p>
                    </div>

                    <div>
                        <h4 class="text-base font-semibold text-gray-900">消耗品をお使いになりますか？</h4>
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-4 py-3 font-medium w-16">選択</th>
                                        <th class="px-4 py-3 font-medium">項目</th>
                                        <th class="px-4 py-3 font-medium text-right">料金</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <template x-for="item in pricing.consumables" :key="item.id">
                                        <tr>
                                            <td class="px-4 py-3">
                                                <input
                                                    type="checkbox"
                                                    :value="item.id"
                                                    x-model="selectedConsumables"
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                >
                                            </td>
                                            <td class="px-4 py-3" x-text="item.name"></td>
                                            <td class="px-4 py-3 text-right" x-text="formatYen(item.price)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <p
                            x-show="!selectedConsumables.includes('garbage')"
                            x-cloak
                            class="mt-3 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-4 py-3"
                        >
                            ゴミ回収は、お客様ご自身でお願いします。
                        </p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 space-y-2 text-sm text-gray-700">
                        <div class="flex justify-between">
                            <span>スペース利用料</span>
                            <span x-text="formatYen(rentalFee)"></span>
                        </div>
                        <div class="flex justify-between" x-show="consumablesFee > 0">
                            <span>消耗品</span>
                            <span x-text="formatYen(consumablesFee)"></span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-2 font-medium">
                            <span>小計（税込）</span>
                            <span x-text="formatYen(subtotal)"></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>うち消費税（{{ \App\Services\ReservationPricingService::TAX_RATE }}%）</span>
                            <span x-text="formatYen(taxAmount)"></span>
                        </div>
                        <div class="flex justify-between border-t border-gray-300 pt-2 text-base font-bold text-gray-900">
                            <span>合計（税込）</span>
                            <span x-text="formatYen(totalAmount)"></span>
                        </div>
                    </div>
                </div>

                <form class="mt-6" method="POST" :action="storeUrl" x-show="canSubmit" @submit="confirmSubmit">
                    @csrf
                    <input type="hidden" name="reserved_on" :value="selectedDate">
                    <input type="hidden" name="start_time" :value="selectionStart">
                    <input type="hidden" name="end_time" :value="selectionEnd">
                    <template x-for="itemId in selectedConsumables" :key="'form-' + itemId">
                        <input type="hidden" name="consumables[]" :value="itemId">
                    </template>
                    <x-primary-button type="submit">
                        予約内容を確認する
                    </x-primary-button>
                </form>
            </div>

            <p class="text-center">
                <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                    ← SkyTerraceの説明に戻る
                </a>
            </p>
        </div>
    </div>

</x-app-layout>
