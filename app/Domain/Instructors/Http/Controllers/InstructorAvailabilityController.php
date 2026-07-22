<?php

namespace App\Domain\Instructors\Http\Controllers;

use App\Domain\Instructors\Http\Requests\StoreAvailabilityRequest;
use App\Domain\Instructors\Models\Instructor;
use App\Domain\Instructors\Models\InstructorAvailability;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class InstructorAvailabilityController extends Controller
{
    public function store(StoreAvailabilityRequest $request, Instructor $instructor): RedirectResponse
    {
        $instructor->availabilities()->create([
            ...$request->validated(),
            'structure_id' => $instructor->structure_id,
        ]);

        return back()->with('status', 'Disponibilité ajoutée.');
    }

    public function destroy(Instructor $instructor, InstructorAvailability $availability): RedirectResponse
    {
        $this->authorize('update', $instructor);

        abort_unless($availability->instructor_id === $instructor->id, 404);

        $availability->delete();

        return back()->with('status', 'Disponibilité supprimée.');
    }
}
