@props(['name' => 'code', 'length' => 6, 'id' => null])

@php
    $fieldId = $id ?? $name;
    $hasError = $errors->has($name);
@endphp

<div
    x-data="{
        digits: Array({{ $length }}).fill(''),
        paste(event) {
            const text = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, {{ $length }});
            if (!text) return;
            event.preventDefault();
            text.split('').forEach((c, i) => this.digits[i] = c);
            this.$refs.cells[Math.min(text.length, {{ $length }}) - 1]?.focus();
        },
        input(i, event) {
            const value = event.target.value.replace(/\D/g, '').slice(-1);
            this.digits[i] = value;
            event.target.value = value;
            if (value && i < {{ $length }} - 1) this.$refs.cells[i + 1].focus();
        },
        back(i, event) {
            if (event.key === 'Backspace' && !this.digits[i] && i > 0) this.$refs.cells[i - 1].focus();
            if (event.key === 'ArrowLeft' && i > 0) this.$refs.cells[i - 1].focus();
            if (event.key === 'ArrowRight' && i < {{ $length }} - 1) this.$refs.cells[i + 1].focus();
        }
    }"
    {{ $attributes->class(['flex flex-wrap items-center gap-2']) }}
>
    <input type="hidden" name="{{ $name }}" :value="digits.join('')">
    @for ($i = 0; $i < $length; $i++)
        <input
            x-ref="cells"
            type="text"
            inputmode="numeric"
            maxlength="1"
            autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
            aria-label="Chiffre {{ $i + 1 }} sur {{ $length }}"
            @input="input({{ $i }}, $event)"
            @keydown="back({{ $i }}, $event)"
            @paste="paste($event)"
            @if ($hasError) aria-invalid="true" @endif
            class="size-12 rounded-md border bg-inset text-center text-lg font-semibold tabular-nums text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)] {{ $hasError ? 'border-danger' : 'border-control' }}"
        >
    @endfor
</div>
