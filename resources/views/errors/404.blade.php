<x-error-page
    code="404"
    title="Page introuvable"
    message="La page que vous recherchez n'existe pas ou a été déplacée."
>
    <x-slot name="actions">
        <a href="/" class="rounded-ui-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition">
            Retour à l'accueil
        </a>
    </x-slot>
</x-error-page>
