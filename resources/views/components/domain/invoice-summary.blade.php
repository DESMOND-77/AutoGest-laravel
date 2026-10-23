@props(['invoice'])

@php
    $due = (float) $invoice->amount_due;
    $paid = (float) $invoice->amount_paid;
    $balance = $invoice->balanceDue();
    $percent = $due > 0 ? min(100, round($paid / $due * 100)) : 100;
@endphp

<div {{ $attributes->class(['space-y-3']) }}>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-[13px] text-ink-2">Solde restant</p>
        <x-ui.status :value="$invoice->status" />
    </div>
    <p class="text-[26px] font-bold leading-none tracking-tight text-ink"><x-ui.money :amount="$balance" /></p>
    <x-ui.progress :value="$paid" :max="$due ?: 1" :tone="$balance <= 0 ? 'success' : 'accent'" :label="$percent.' % réglé'" />
    <dl class="grid grid-cols-2 gap-3 border-t border-line pt-3 text-sm">
        <div>
            <dt class="text-[12px] uppercase tracking-wide text-ink-3">Total</dt>
            <dd class="font-semibold text-ink"><x-ui.money :amount="$due" /></dd>
        </div>
        <div>
            <dt class="text-[12px] uppercase tracking-wide text-ink-3">Payé</dt>
            <dd class="font-semibold text-ink"><x-ui.money :amount="$paid" /></dd>
        </div>
    </dl>
</div>
