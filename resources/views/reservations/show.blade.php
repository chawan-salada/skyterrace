<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            予約内容の確認
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status') === 'reservation-created')
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
                    ご予約を受け付けました。
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 sm:p-8 text-gray-800 space-y-6">
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">利用日</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">
                                {{ $reservation->reserved_on->format('Y年n月j日') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">時間</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">
                                {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}
                                〜
                                {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}
                            </dd>
                        </div>
                    </dl>

                    <section class="border-t border-gray-200 pt-6 space-y-4">
                        <h3 class="text-base font-semibold text-gray-900">料金内訳</h3>

                        <dl class="space-y-2 text-sm text-gray-700">
                            <div class="flex justify-between">
                                <dt>スペース利用料</dt>
                                <dd>{{ number_format($reservation->rental_fee) }}円</dd>
                            </div>
                            @if ($reservation->consumables_fee > 0)
                                <div class="flex justify-between">
                                    <dt>消耗品</dt>
                                    <dd>{{ number_format($reservation->consumables_fee) }}円</dd>
                                </div>
                            @endif
                            <div class="flex justify-between border-t border-gray-200 pt-2 font-medium">
                                <dt>小計（税込）</dt>
                                <dd>{{ number_format($reservation->total_amount) }}円</dd>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <dt>うち消費税（{{ \App\Services\ReservationPricingService::TAX_RATE }}%）</dt>
                                <dd>{{ number_format($reservation->tax_amount) }}円</dd>
                            </div>
                            <div class="flex justify-between border-t border-gray-300 pt-2 text-base font-bold text-gray-900">
                                <dt>合計（税込）</dt>
                                <dd>{{ number_format($reservation->total_amount) }}円</dd>
                            </div>
                        </dl>

                        @if ($consumableItems !== [])
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">消耗品</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    @foreach ($consumableItems as $item)
                                        <li>{{ $item['name'] }}（{{ number_format($item['price']) }}円）</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>

                    <p class="text-sm text-gray-600 leading-relaxed">
                        利用料金は4階受付にて現金でお支払いください。お支払い完了後、専用QRコードを発行いたします。
                    </p>

                    <div class="flex flex-wrap gap-4 pt-2">
                        <a href="{{ route('reservations.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            別の日時を予約する
                        </a>
                        @if ($reservation->isUpcoming())
                            <form method="POST"
                                  action="{{ route('reservations.destroy', $reservation) }}"
                                  onsubmit="return confirm('予約をキャンセルしますか？')">
                                @csrf
                                @method('DELETE')
                                <x-danger-button type="submit">
                                    キャンセル
                                </x-danger-button>
                            </form>
                        @endif
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            SkyTerraceの説明に戻る
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
