<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;

class ShowInvoiceAction
{
    public function __construct(private readonly FormatInvoiceAction $formatter) {}

    /** @return array<string, mixed> */
    public function execute(Invoice $invoice): array
    {
        return $this->formatter->execute($invoice);
    }
}
