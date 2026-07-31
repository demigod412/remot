<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;

class SkillController extends Controller
{
    /** GET /api/v1/skills — the master catalogue users pick from. */
    public function index(): JsonResponse
    {
        $skills = Skill::active()->with('category:id,name')->orderBy('name')->get();

        return response()->json([
            'data' => $skills->map(fn ($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'category'    => $s->category?->name,
                'category_id' => $s->category_id,
            ]),
        ]);
    }
}
