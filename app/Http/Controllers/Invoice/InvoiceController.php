<?php

namespace App\Http\Controllers\Invoice;

use App\Actions\Invoice\GetMyInvoicesAction;
use App\Actions\Invoice\ShowInvoiceAction;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    public function index(GetMyInvoicesAction $action): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);
        return response()->json(['status' => 'success', 'invoices' => $action->execute(request()->user())]);
    }

    public function show(Invoice $invoice, ShowInvoiceAction $action): JsonResponse
    {
        Gate::authorize('view', $invoice);
        return response()->json(['status' => 'success', 'invoice' => $action->execute($invoice)]);
    }
}
