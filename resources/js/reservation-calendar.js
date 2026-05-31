import Alpine from 'alpinejs';

Alpine.data('reservationCalendar', (config) => ({
    slotsUrl: config.slotsUrl,
    storeUrl: config.storeUrl,
    minBookingMinutes: config.minBookingMinutes,
    pricing: config.pricing,
    selectedDate: null,
    selectedDateLabel: '',
    slots: [],
    loadingSlots: false,
    selectionStart: '',
    selectionEnd: '',
    selectedConsumables: ['garbage'],
    selectionError: null,

    get canSubmit() {
        return this.selectedDate && this.selectionStart && this.selectionEnd && !this.selectionError;
    },

    get availableStartTimes() {
        return this.slots
            .filter((slot) => slot.status === 'available' && this.hasMinimumAvailability(slot.time))
            .map((slot) => slot.time);
    },

    get availableEndTimes() {
        if (!this.selectionStart) {
            return [];
        }

        const ends = [];
        let end = this.addMinutes(this.selectionStart, this.minBookingMinutes);

        while (this.compareTime(end, '21:00') <= 0) {
            if (!this.isRangeAvailable(this.selectionStart, end)) {
                break;
            }
            ends.push(end);
            end = this.addMinutes(end, 30);
        }

        return ends;
    },

    get durationMinutes() {
        if (!this.selectionStart || !this.selectionEnd) {
            return 0;
        }

        return this.compareTime(this.selectionEnd, this.selectionStart);
    },

    get rentalFee() {
        if (!this.selectionEnd) {
            return 0;
        }

        let fee = this.pricing.basePrice;

        if (this.durationMinutes > this.pricing.minBookingMinutes) {
            const extraSlots = (this.durationMinutes - this.pricing.minBookingMinutes) / this.pricing.slotMinutes;
            fee += extraSlots * this.pricing.extensionPrice;
        }

        return fee;
    },

    get consumablesFee() {
        return this.pricing.consumables
            .filter((item) => this.selectedConsumables.includes(item.id))
            .reduce((sum, item) => sum + item.price, 0);
    },

    get subtotal() {
        return this.rentalFee + this.consumablesFee;
    },

    get taxAmount() {
        if (!this.subtotal) {
            return 0;
        }

        return Math.floor(this.subtotal * this.pricing.taxRate / (100 + this.pricing.taxRate));
    },

    get totalAmount() {
        return this.subtotal;
    },

    formatYen(amount) {
        return `${Number(amount).toLocaleString('ja-JP')}円`;
    },

    confirmSubmit(event) {
        if (! confirm('予約を確定しますか？')) {
            event.preventDefault();
        }
    },

    async selectDate(date, label) {
        this.selectedDate = date;
        this.selectedDateLabel = label;
        this.selectionStart = '';
        this.selectionEnd = '';
        this.selectedConsumables = ['garbage'];
        this.selectionError = null;
        this.loadingSlots = true;
        this.slots = [];

        try {
            const response = await fetch(`${this.slotsUrl}?date=${encodeURIComponent(date)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                this.selectionError = '時間枠の取得に失敗しました。';

                return;
            }

            const data = await response.json();
            this.slots = data.slots;
        } catch {
            this.selectionError = '時間枠の取得に失敗しました。';
        } finally {
            this.loadingSlots = false;
        }
    },

    onStartChange() {
        this.selectionEnd = '';
        this.selectionError = null;
    },

    onEndChange() {
        this.selectionError = null;
    },

    slotButtonClass(slot) {
        if (this.isSelectedSlot(slot.time)) {
            return 'bg-indigo-600 text-white border-indigo-600';
        }
        if (slot.status === 'available') {
            return 'bg-white text-gray-800 border-gray-300 hover:bg-indigo-50';
        }

        return 'bg-gray-300 text-gray-500 border-gray-300 cursor-not-allowed';
    },

    isSelectedSlot(time) {
        if (!this.selectionStart) {
            return false;
        }
        if (!this.selectionEnd) {
            return time === this.selectionStart;
        }

        return this.compareTime(time, this.selectionStart) >= 0
            && this.compareTime(time, this.selectionEnd) < 0;
    },

    clickSlot(slot) {
        if (slot.status !== 'available') {
            return;
        }

        this.selectionError = null;

        if (!this.selectionStart || this.selectionEnd) {
            if (!this.hasMinimumAvailability(slot.time)) {
                this.selectionError = 'この時間からは2時間以上の予約が取れません。';

                return;
            }

            this.selectionStart = slot.time;
            this.selectionEnd = '';

            return;
        }

        if (this.compareTime(slot.time, this.selectionStart) < 0) {
            if (!this.hasMinimumAvailability(slot.time)) {
                this.selectionError = 'この時間からは2時間以上の予約が取れません。';

                return;
            }

            this.selectionStart = slot.time;
            this.selectionEnd = '';

            return;
        }

        const end = this.addMinutes(slot.time, 30);
        const minEnd = this.addMinutes(this.selectionStart, this.minBookingMinutes);

        if (this.compareTime(end, minEnd) < 0) {
            this.selectionError = '最低2時間以上でご予約ください。';

            return;
        }

        if (!this.isRangeAvailable(this.selectionStart, end)) {
            this.selectionError = '選択範囲に予約済み・ブロック時間が含まれています。';

            return;
        }

        this.selectionEnd = end;
    },

    hasMinimumAvailability(start) {
        const minEnd = this.addMinutes(start, this.minBookingMinutes);

        if (this.compareTime(minEnd, '21:00') > 0) {
            return false;
        }

        return this.isRangeAvailable(start, minEnd);
    },

    isRangeAvailable(start, end) {
        const slotsByTime = Object.fromEntries(this.slots.map((s) => [s.time, s]));
        let cursor = start;

        while (this.compareTime(cursor, end) < 0) {
            const slot = slotsByTime[cursor];
            if (!slot || slot.status !== 'available') {
                return false;
            }
            cursor = this.addMinutes(cursor, 30);
        }

        return true;
    },

    compareTime(a, b) {
        const [ah, am] = a.split(':').map(Number);
        const [bh, bm] = b.split(':').map(Number);

        return ah * 60 + am - (bh * 60 + bm);
    },

    addMinutes(time, minutes) {
        const [h, m] = time.split(':').map(Number);
        const total = h * 60 + m + minutes;
        const nh = Math.floor(total / 60);
        const nm = total % 60;

        return `${String(nh).padStart(2, '0')}:${String(nm).padStart(2, '0')}`;
    },
}));
