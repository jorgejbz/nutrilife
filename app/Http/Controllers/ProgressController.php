<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class ProgressController extends Controller
{
    
    public function index()
    {
        $patients = Patient::where('user_id', Auth::id())
            ->where('active', true)
            ->with([
                'evaluations' => function ($query) {

                    $query
                        ->whereNotNull('weight')
                        ->orderBy('evaluation_date')
                        ->orderBy('id');

                }
            ])
            ->orderBy('first_name')
            ->get();


        $progressPatients = $patients->map(function ($patient) {

            $evaluations = $patient->evaluations;

            $firstEvaluation = $evaluations->first();

            $latestEvaluation = $evaluations->last();


            $weightChange = null;

            $fatChange = null;

            $muscleChange = null;


            if ($firstEvaluation && $latestEvaluation) {

                if (
                    !is_null($firstEvaluation->weight) &&
                    !is_null($latestEvaluation->weight)
                ) {

                    $weightChange = round(
                        $latestEvaluation->weight -
                        $firstEvaluation->weight,
                        2
                    );
                }


                if (
                    !is_null($firstEvaluation->body_fat) &&
                    !is_null($latestEvaluation->body_fat)
                ) {

                    $fatChange = round(
                        $latestEvaluation->body_fat -
                        $firstEvaluation->body_fat,
                        2
                    );
                }


                if (
                    !is_null($firstEvaluation->muscle_mass) &&
                    !is_null($latestEvaluation->muscle_mass)
                ) {

                    $muscleChange = round(
                        $latestEvaluation->muscle_mass -
                        $firstEvaluation->muscle_mass,
                        2
                    );
                }

            }


            return [

                'patient' => $patient,

                'evaluation_count' =>
                    $evaluations->count(),

                'first' =>
                    $firstEvaluation,

                'latest' =>
                    $latestEvaluation,

                'weight_change' =>
                    $weightChange,

                'fat_change' =>
                    $fatChange,

                'muscle_change' =>
                    $muscleChange,

            ];

        });


        $patientsWithProgress =
            $progressPatients
                ->filter(
                    fn ($item) =>
                        $item['evaluation_count'] > 0
                )
                ->count();


        $patientsWithComparison =
            $progressPatients
                ->filter(
                    fn ($item) =>
                        $item['evaluation_count'] >= 2
                )
                ->count();


        $totalEvaluations =
            $progressPatients
                ->sum('evaluation_count');


        return view(
            'progress.index',
            compact(
                'progressPatients',
                'patientsWithProgress',
                'patientsWithComparison',
                'totalEvaluations'
            )
        );

    
    }
    public function show(Patient $paciente)
{
    abort_if($paciente->user_id !== Auth::id(), 403);

    $evaluations = $paciente->evaluations()
        ->orderBy('evaluation_date')
        ->orderBy('id')
        ->get();

    $labels = $evaluations
        ->map(fn ($evaluation) =>
            $evaluation->evaluation_date->format('d/m/Y')
        )
        ->values();

    $weights = $evaluations
        ->map(fn ($evaluation) =>
            $evaluation->weight
                ? (float) $evaluation->weight
                : null
        )
        ->values();

    $bodyFat = $evaluations
        ->map(fn ($evaluation) =>
            !is_null($evaluation->body_fat)
                ? (float) $evaluation->body_fat
                : null
        )
        ->values();

    $muscleMass = $evaluations
        ->map(fn ($evaluation) =>
            !is_null($evaluation->muscle_mass)
                ? (float) $evaluation->muscle_mass
                : null
        )
        ->values();

    $firstEvaluation = $evaluations->first();
    $latestEvaluation = $evaluations->last();

    $weightChange = null;
    $fatChange = null;
    $muscleChange = null;

    if ($firstEvaluation && $latestEvaluation) {

        if (
            !is_null($firstEvaluation->weight) &&
            !is_null($latestEvaluation->weight)
        ) {
            $weightChange = round(
                $latestEvaluation->weight -
                $firstEvaluation->weight,
                2
            );
        }

        if (
            !is_null($firstEvaluation->body_fat) &&
            !is_null($latestEvaluation->body_fat)
        ) {
            $fatChange = round(
                $latestEvaluation->body_fat -
                $firstEvaluation->body_fat,
                2
            );
        }

        if (
            !is_null($firstEvaluation->muscle_mass) &&
            !is_null($latestEvaluation->muscle_mass)
        ) {
            $muscleChange = round(
                $latestEvaluation->muscle_mass -
                $firstEvaluation->muscle_mass,
                2
            );
        }
    }

    return view('progress.show', compact(
        'paciente',
        'evaluations',
        'labels',
        'weights',
        'bodyFat',
        'muscleMass',
        'firstEvaluation',
        'latestEvaluation',
        'weightChange',
        'fatChange',
        'muscleChange'
    ));
}
}