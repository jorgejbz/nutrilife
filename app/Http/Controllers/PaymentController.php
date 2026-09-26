<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::where('user_id', Auth::id())
            ->with('patient')
            ->orderByDesc('paid_at')
            ->get();

        $totalIncome = Payment::where('user_id', Auth::id())
            ->where('status', 'Pagado')
            ->sum('amount');

        $monthIncome = Payment::where('user_id', Auth::id())
            ->where('status', 'Pagado')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('amount');

        $paidPayments = Payment::where('user_id', Auth::id())
            ->where('status', 'Pagado')
            ->count();

        $pendingPayments = Payment::where('user_id', Auth::id())
            ->where('status', 'Pendiente')
            ->count();

        return view('payments.index', compact(
            'payments',
            'totalIncome',
            'monthIncome',
            'paidPayments',
            'pendingPayments'
        ));
    }


    public function create()
    {
        $patients = Patient::where('user_id', Auth::id())
            ->where('active', true)
            ->orderBy('first_name')
            ->get();

        return view('payments.create', compact('patients'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => [
                'required',
                'exists:patients,id'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01'
            ],

            'paid_at' => [
                'required',
                'date'
            ],

            'concept' => [
                'required',
                'string',
                'max:255'
            ],

            'payment_method' => [
                'required',
                'in:Efectivo,Transferencia,Tarjeta,Otro'
            ],

            'status' => [
                'required',
                'in:Pagado,Pendiente,Cancelado'
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255'
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000'
            ],
        ]);

        $patient = Patient::where('id', $validated['patient_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validated['user_id'] = Auth::id();
        $validated['patient_id'] = $patient->id;

        Payment::create($validated);

        return redirect()
            ->route('pagos.index')
            ->with('success', 'Pago registrado correctamente.');
    }



}