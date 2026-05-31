<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            SkyTerrace
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'reservation-cancelled')
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
                    予約をキャンセルしました。
                </div>
            @endif

            @if ($reservations->isNotEmpty())
                <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 sm:p-8">
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">ご予約内容</h2>
                        <div class="mt-4 space-y-4">
                            @foreach ($reservations as $reservation)
                                @php
                                    $consumableItems = $pricingService->selectedConsumables($reservation->consumables ?? []);
                                @endphp
                                <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <dt class="font-medium text-gray-500">利用日</dt>
                                            <dd class="mt-1 text-gray-900 font-semibold">
                                                {{ $reservation->reserved_on->format('Y年n月j日') }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500">時間</dt>
                                            <dd class="mt-1 text-gray-900 font-semibold">
                                                {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}
                                                〜
                                                {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-500">合計（税込）</dt>
                                            <dd class="mt-1 text-gray-900 font-semibold">
                                                {{ number_format($reservation->total_amount) }}円
                                            </dd>
                                        </div>
                                        @if ($consumableItems !== [])
                                            <div class="sm:col-span-2">
                                                <dt class="font-medium text-gray-500">消耗品</dt>
                                                <dd class="mt-1 text-gray-700">
                                                    {{ collect($consumableItems)->pluck('name')->join('、') }}
                                                </dd>
                                            </div>
                                        @endif
                                    </dl>
                                    <div class="flex flex-wrap gap-3 pt-2">
                                        <a href="{{ route('reservations.show', $reservation) }}"
                                           class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                            詳細を見る
                                        </a>
                                        <form method="POST"
                                              action="{{ route('reservations.destroy', $reservation) }}"
                                              onsubmit="return confirm('予約をキャンセルしますか？')">
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button type="submit">
                                                キャンセル
                                            </x-danger-button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <article class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 sm:p-8 text-gray-800 space-y-10">

                    <header class="border-b border-gray-200 pb-8">
                        <p class="text-sm font-medium text-indigo-600 tracking-wide uppercase">5階 屋上レンタルスペース</p>
                        <h1 class="mt-2 text-3xl font-bold text-gray-900">SkyTerrace</h1>
                        <p class="mt-4 text-gray-600 leading-relaxed">
                            SkyTerraceは、都会の空を眺めながら、BBQやパーティ、交流会を楽しめる屋上レンタルスペースです。
                            開放感のある空間で、ゆったりとした時間をお過ごしください。
                        </p>
                        <dl class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                            <div class="rounded-lg bg-gray-50 px-4 py-3">
                                <dt class="font-medium text-gray-500">定員</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900">12名</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-4 py-3">
                                <dt class="font-medium text-gray-500">利用時間</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900">9:00 ～ 21:00</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-4 py-3">
                                <dt class="font-medium text-gray-500">予約</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900">完全予約制</dd>
                            </div>
                        </dl>
                    </header>

                    <section>
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">利用料金</h2>
                        <ul class="mt-4 space-y-2 text-gray-700">
                            <li><span class="font-medium">1時間：</span>5,000円（税込）</li>
                            <li><span class="font-medium">延長料金：</span>30分ごとに 2,500円（税込）</li>
                        </ul>
                        <p class="mt-4 text-sm text-gray-600 leading-relaxed">
                            ご利用前に料金をお支払いいただきます。<br>
                            お支払い完了後、4階受付にて専用QRコードを発行いたします。
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">設備・利用可能なもの</h2>
                        <p class="mt-4 text-gray-600">SkyTerraceでは、以下の設備をご利用いただけます。</p>
                        <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 text-gray-700">
                            <li class="flex items-start gap-2">
                                <span class="text-indigo-500 mt-0.5" aria-hidden="true">✓</span>
                                BBQコンロ
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-indigo-500 mt-0.5" aria-hidden="true">✓</span>
                                皿・コップ
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-indigo-500 mt-0.5" aria-hidden="true">✓</span>
                                はし・フォーク・ナイフ
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-indigo-500 mt-0.5" aria-hidden="true">✓</span>
                                フライパン・なべ
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-indigo-500 mt-0.5" aria-hidden="true">✓</span>
                                キッチン用品一式
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-indigo-500 mt-0.5" aria-hidden="true">✓</span>
                                テーブル・椅子
                            </li>
                            <li class="flex items-start gap-2 sm:col-span-2">
                                <span class="text-indigo-500 mt-0.5" aria-hidden="true">✓</span>
                                屋外照明
                            </li>
                        </ul>
                        <p class="mt-4 text-sm text-gray-500">※ 食材・飲み物はご持参ください。</p>
                    </section>

                    <section>
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">ご利用方法</h2>
                        <ol class="mt-4 space-y-3 list-decimal list-inside text-gray-700">
                            <li>予約を行う</li>
                            <li>利用料金をお支払い</li>
                            <li>4階受付でQRコードを受け取る</li>
                            <li>QRコードを使ってSkyTerraceへ入場</li>
                        </ol>
                    </section>

                    <section class="rounded-lg bg-indigo-50 border border-indigo-100 p-6 text-center">
                        <h2 class="text-lg font-semibold text-gray-900">ご予約</h2>
                        <p class="mt-2 text-gray-600">ご予約は、下記ボタンよりお願いいたします。</p>
                        <div class="mt-6">
                            <a href="{{ route('reservations.create') }}"
                               class="inline-flex items-center px-6 py-3 bg-gray-800 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                予約する
                            </a>
                        </div>
                    </section>

                    <section>
                        <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">ご利用時の注意事項</h2>
                        <p class="mt-4 text-gray-600">皆さまに気持ちよくご利用いただくため、以下のルールをお守りください。</p>
                        <ul class="mt-4 space-y-2 text-gray-700 list-disc list-inside">
                            <li>大声で騒ぐなど、近隣の迷惑となる行為は禁止です</li>
                            <li>食器・調理器具は洗って元の場所へお戻しください</li>
                            <li>ゴミは分別にご協力ください</li>
                            <li>屋上での事故・ケガには十分ご注意ください</li>
                            <li>喫煙は指定場所のみでお願いいたします</li>
                            <li>設備を破損した場合はご連絡ください</li>
                        </ul>
                    </section>

                </div>
            </article>
        </div>
    </div>
</x-app-layout>
