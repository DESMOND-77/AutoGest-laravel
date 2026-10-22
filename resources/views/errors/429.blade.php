<x-error-page
    code="429"
    title="Trop de requêtes"
    message="Vous avez effectué trop de tentatives en peu de temps. Veuillez patienter quelques instants avant de réessayer."
>
    <x-slot name="actions">
        <a href="/" class="rounded-ui-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition">
            Accueil
        </a>
    </x-slot>
</x-error-page>
