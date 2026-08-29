<x-app-layout>
    <x-slot name="header">Boutique</x-slot>

    <div class="py-6 max-w-6xl mx-auto">
        @if (session('status'))
            <x-alert :variant="str_contains(session('status'), 'stock insuffisant') ? 'warning' : 'success'" class="mb-5">{{ session('status') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert variant="danger" class="mb-5">{{ $errors->first() }}</x-alert>
        @endif

        <x-tabs :tabs="[
            'ventes' => 'Ventes',
            'rapports' => 'Rapports',
            'produits' => 'Produits',
            'reapprovisionnement' => 'Réapprovisionnement',
        ]">
            <div x-show="tab === 'ventes'" x-cloak class="space-y-5">
                @include('store.partials.ventes', ['saleProducts' => $products])
            </div>

            <div x-show="tab === 'rapports'" x-cloak class="space-y-5">
                @include('store.partials.rapports')
            </div>

            <div x-show="tab === 'produits'" x-cloak class="space-y-5">
                @include('store.partials.produits', ['catalogProducts' => $allProducts])
            </div>

            <div x-show="tab === 'reapprovisionnement'" x-cloak class="space-y-5">
                @include('store.partials.reapprovisionnement', ['catalogProducts' => $allProducts])
            </div>
        </x-tabs>
    </div>
</x-app-layout>
