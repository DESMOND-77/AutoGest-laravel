@props(['vehicle', 'expiring' => false])

<article {{ $attributes->class(['material-solid flex flex-col gap-3 p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[15px] font-bold tracking-tight text-ink">{{ $vehicle->plate }}</p>
            <p class="truncate text-[13px] text-ink-2">{{ $vehicle->brand }} {{ $vehicle->model }}{{ $vehicle->year ? ' · '.$vehicle->year : '' }}</p>
        </div>
        <x-ui.status :value="$vehicle->status" />
    </div>

    <dl class="grid grid-cols-2 gap-2 text-[12.5px]">
        <div>
            <dt class="text-ink-3">Kilométrage</dt>
            <dd class="font-medium tabular-nums text-ink">{{ number_format((int) $vehicle->mileage, 0, ',', ' ') }} km</dd>
        </div>
        <div>
            <dt class="text-ink-3">Catégorie</dt>
            <dd class="font-medium text-ink">{{ $vehicle->category ?: '—' }}</dd>
        </div>
    </dl>

    <div class="mt-auto space-y-1.5 border-t border-line pt-3">
        <x-domain.expiry-chip :date="$vehicle->technical_inspection_expires_at" label="Contrôle technique" icon="wrench" />
        <x-domain.expiry-chip :date="$vehicle->insurance_expires_at" label="Assurance" icon="shield" />
    </div>

    @if ($expiring)
        <p class="flex items-center gap-1.5 text-[12px] font-medium text-warning">
            <x-ui.icon name="circle-alert" class="w-3.5 h-3.5" /> Échéance dans moins de 30 jours
        </p>
    @endif

    <a href="{{ route('fleet.show', $vehicle) }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-accent hover:underline focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
        Ouvrir la fiche <x-ui.icon name="chevron-right" class="w-3.5 h-3.5" />
    </a>
</article>
