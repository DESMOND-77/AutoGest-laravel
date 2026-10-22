<x-app-layout>
    <x-slot name="header">Commandes</x-slot>

    <div class="py-6 space-y-5 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert :variant="str_contains(session('status'), 'stock insuffisant') ? 'warning' : 'success'">{{ session('status') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert variant="danger">{{ $errors->first() }}</x-alert>
        @endif

        @include('store.partials.ventes', ['saleProducts' => $products])
    </div>
</x-app-layout>
