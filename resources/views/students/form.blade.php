<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $student->exists ? 'Modifier '.$student->fullName() : 'Nouvel élève' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ $student->exists ? route('students.update', $student) : route('students.store') }}">
                    @csrf
                    @if ($student->exists) @method('PUT') @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="first_name" value="Prénom" />
                            <x-text-input id="first_name" name="first_name" class="block mt-1 w-full" :value="old('first_name', $student->first_name)" required />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="last_name" value="Nom" />
                            <x-text-input id="last_name" name="last_name" class="block mt-1 w-full" :value="old('last_name', $student->last_name)" required />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="birth_date" value="Date de naissance" />
                            <x-text-input id="birth_date" type="date" name="birth_date" class="block mt-1 w-full" :value="old('birth_date', optional($student->birth_date)->toDateString())" />
                        </div>
                        <div>
                            <x-input-label for="birth_place" value="Lieu de naissance" />
                            <x-text-input id="birth_place" name="birth_place" class="block mt-1 w-full" :value="old('birth_place', $student->birth_place)" />
                        </div>
                        <div>
                            <x-input-label for="phone" value="Téléphone" />
                            <x-text-input id="phone" name="phone" class="block mt-1 w-full" :value="old('phone', $student->phone)" />
                        </div>
                        <div>
                            <x-input-label for="email" value="E-mail" />
                            <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email', $student->email)" />
                        </div>
                        <div>
                            <x-input-label for="license_category" value="Catégorie de permis" />
                            <select id="license_category" name="license_category" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                                @foreach (\App\Domain\Students\Enums\LicenseCategory::cases() as $case)
                                    <option value="{{ $case->value }}" @selected(old('license_category', $student->license_category?->value) === $case->value)>{{ $case->value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="course_type" value="Type de cours" />
                            <select id="course_type" name="course_type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                                @foreach (\App\Domain\Students\Enums\CourseType::cases() as $case)
                                    <option value="{{ $case->value }}" @selected(old('course_type', $student->course_type?->value) === $case->value)>{{ $case->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="instructor_id" value="Moniteur" />
                            <select id="instructor_id" name="instructor_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">—</option>
                                @foreach ($instructors as $instructor)
                                    <option value="{{ $instructor->id }}" @selected(old('instructor_id', $student->instructor_id) == $instructor->id)>{{ $instructor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="address" value="Adresse" />
                            <textarea id="address" name="address" rows="2" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">{{ old('address', $student->address) }}</textarea>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <x-primary-button>{{ $student->exists ? 'Enregistrer' : 'Créer' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
