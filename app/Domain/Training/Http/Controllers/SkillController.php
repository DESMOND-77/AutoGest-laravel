<?php

namespace App\Domain\Training\Http\Controllers;

use App\Domain\Training\Http\Requests\StoreSkillRequest;
use App\Domain\Training\Models\Skill;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Skill::class);

        return view('training.skills.index', [
            'skills' => Skill::query()->orderBy('category')->orderBy('position')->get(),
        ]);
    }

    public function store(StoreSkillRequest $request): RedirectResponse
    {
        Skill::query()->create($request->validated());

        return back()->with('status', 'Compétence ajoutée.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $this->authorize('delete', $skill);

        $skill->delete();

        return back()->with('status', 'Compétence supprimée.');
    }
}
