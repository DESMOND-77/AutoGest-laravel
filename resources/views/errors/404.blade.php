<x-public-layout :title="'Page introuvable'">
    <div class="mx-auto flex w-full max-w-2xl flex-col items-center px-4 py-16 text-center sm:px-6">
        <span class="grid size-16 place-items-center rounded-full bg-accent-soft text-accent">
            <x-ui.icon name="search" size="xl" />
        </span>

        <p class="mt-6 text-eyebrow">Erreur 404</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-ink sm:text-4xl">Page introuvable</h1>
        <p class="mt-3 max-w-xl text-[15px] font-medium text-ink-2">Cette page n’existe pas ou a été déplacée.</p>
        <p class="mt-2 max-w-xl text-[14px] leading-relaxed text-ink-3">Le lien est peut-être obsolète, ou l’élément a été supprimé. Vérifiez l’adresse ou repartez du tableau de bord.</p>

        <div class="mt-7 flex flex-wrap items-center justify-center gap-2">
            <x-ui.button :href="auth()->check() ? route('dashboard') : url('/')" size="lg" icon-right="arrow-right">Retour au tableau de bord</x-ui.button>
            <x-ui.button :href="route('students.index')" variant="secondary" size="lg">Voir les élèves</x-ui.button>
        </div>

        <div class="mt-10 w-full border-t border-line pt-6">
            <p class="text-[12.5px] text-ink-3">
                Besoin d’aide ? Écrivez-nous à
                <a href="mailto:contact@auto-gestboard.com" class="font-medium text-accent hover:underline">contact@auto-gestboard.com</a>
                en précisant la page concernée et l’heure de l’erreur.
            </p>
        </div>
    </div>
</x-public-layout>
