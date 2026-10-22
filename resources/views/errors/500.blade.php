<x-error-page
    code="500"
    title="Erreur serveur"
    message="Une erreur inattendue s'est produite de notre côté. Notre équipe a été informée, veuillez réessayer dans quelques instants."
>
    <x-slot name="actions">
        <a href="/" class="rounded-ui-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition">
            Accueil
        </a>
    </x-slot>
</x-error-page>
