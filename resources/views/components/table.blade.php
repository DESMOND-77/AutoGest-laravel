@props(['headers' => []])

<div {{ $attributes->merge(['class' => 'bg-surface rounded-ui-lg shadow-soft overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            @if (count($headers))
                <thead>
                    <tr class="text-left text-content-secondary border-b border-border">
                        @foreach ($headers as $header)
                            <th class="px-4 py-3 font-medium whitespace-nowrap">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-border/60">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
