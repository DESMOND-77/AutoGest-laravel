@php
    $isEdit = $student->exists;
    $licenses = \App\Domain\Students\Enums\LicenseCategory::cases();
    $courses = \App\Domain\Students\Enums\CourseType::cases();
@endphp

<x-app-layout :title="$isEdit ? 'Modifier un élève' : 'Nouvel élève'">
    <x-layout.page-header
        :title="$isEdit ? 'Modifier l’élève' : 'Nouvel élève'"
        :description="$isEdit ? 'Mettez à jour les informations administratives. L’étape du parcours se change depuis la fiche.' : 'Créez la fiche administrative. L’élève démarrera à l’étape Prospect.'"
        :breadcrumbs="[
            ['label' => 'Élèves', 'href' => route('students.index')],
            ['label' => $isEdit ? $student->fullName() : 'Nouvel élève', 'href' => $isEdit ? route('students.show', $student) : null],
        ]"
    />

    <div class="mx-auto max-w-3xl">
        <x-form.errors class="mb-4" />

        <form method="POST" action="{{ $isEdit ? route('students.update', $student) : route('students.store') }}" class="material-solid p-5 sm:p-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <x-form.section title="Identité" description="Nom, prénoms et état civil de l’élève.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="last_name" label="Nom" required>
                        <x-form.input name="last_name" :value="$student->last_name" autocomplete="family-name" required />
                    </x-form.field>
                    <x-form.field name="first_name" label="Prénom" required>
                        <x-form.input name="first_name" :value="$student->first_name" autocomplete="given-name" required />
                    </x-form.field>
                    <x-form.field name="birth_date" label="Date de naissance" optional>
                        <x-form.input name="birth_date" type="date" :value="$student->birth_date?->format('Y-m-d')" />
                    </x-form.field>
                    <x-form.field name="birth_place" label="Lieu de naissance" optional>
                        <x-form.input name="birth_place" :value="$student->birth_place" />
                    </x-form.field>
                </div>
            </x-form.section>

            <x-form.section title="Contact" description="Coordonnées utilisées pour les rappels et le compte élève.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="phone" label="Téléphone" required hint="Avec ou sans indicatif +241.">
                        <x-form.input name="phone" type="tel" inputmode="tel" icon="phone" :value="$student->phone" autocomplete="tel" required />
                    </x-form.field>
                    <x-form.field name="phone_secondary" label="Téléphone secondaire" optional>
                        <x-form.input name="phone_secondary" type="tel" inputmode="tel" icon="phone" :value="$student->phone_secondary" />
                    </x-form.field>
                    <x-form.field name="email" label="Adresse e-mail" required hint="Identifiant du compte élève, unique dans votre établissement.">
                        <x-form.input name="email" type="email" icon="envelope" :value="$student->email" autocomplete="email" required />
                    </x-form.field>
                    <x-form.field name="address" label="Adresse" optional>
                        <x-form.input name="address" :value="$student->address" autocomplete="street-address" />
                    </x-form.field>
                </div>
            </x-form.section>

            <x-form.section title="Formation" description="Permis visé, formule et moniteur référent.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="license_category" label="Catégorie de permis" required>
                        <x-form.select name="license_category" :selected="$student->license_category?->value"
                            :options="collect($licenses)->mapWithKeys(fn ($c) => [$c->value => 'Permis '.$c->value])->all()" />
                    </x-form.field>
                    <x-form.field name="course_type" label="Formule" required>
                        <x-form.select name="course_type" :selected="$student->course_type?->value"
                            :options="collect($courses)->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
                    </x-form.field>
                    <x-form.field name="instructor_id" label="Moniteur référent" optional>
                        <x-form.select name="instructor_id" :selected="$student->instructor_id" placeholder="Non affecté"
                            :options="collect($instructors)->pluck('name', 'id')->all()" />
                    </x-form.field>
                    <x-form.field name="neph" label="NEPH" optional hint="Numéro d’enregistrement préfectoral harmonisé, si connu.">
                        <x-form.input name="neph" :value="$student->neph" />
                    </x-form.field>
                </div>
            </x-form.section>

            <x-form.actions>
                <x-ui.button :href="$isEdit ? route('students.show', $student) : route('students.index')" variant="ghost">Annuler</x-ui.button>
                <x-ui.button type="submit" icon="check">{{ $isEdit ? 'Enregistrer les modifications' : 'Créer l’élève' }}</x-ui.button>
            </x-form.actions>
        </form>
    </div>
</x-app-layout>
