<x-app-layout>
    <x-slot name="header">Catalogue boutique</x-slot>

    <div class="py-6 space-y-5 max-w-3xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert variant="danger">{{ $errors->first() }}</x-alert>
        @endif

        @include('store.partials.produits', ['catalogProducts' => $products])
    </div>
</x-app-layout>
