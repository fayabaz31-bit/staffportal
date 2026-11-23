<?php

namespace App\Support;

use App\Models\Student;
use App\Models\StudentInvoice;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RemitaGateway
{
    public function generate(StudentInvoice $invoice, Student $student): void
    {
        $this->ensureConfigured();
        DB::transaction(function () use ($invoice, $student): void {
            $locked = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($locked->paid_at || $locked->rrr) {
                return;
            }
            $amount = number_format($locked->amount_kobo / 100, 2, '.', '');
            $merchant = config('student.remita.merchant_id');
            $service = config('student.remita.service_type_id');
            $hash = hash('sha512', $merchant.$service.$locked->reference.$amount.config('student.remita.api_key'));
            $payload = [
                'serviceTypeId' => $service, 'amount' => $amount, 'orderId' => $locked->reference,
                'payerName' => $student->name, 'payerEmail' => $student->email,
                'payerPhone' => $student->phone ?? '', 'description' => $locked->description,
            ];
            try {
                $response = Http::connectTimeout(4)->timeout(15)->acceptJson()
                    ->withHeaders(['Authorization' => "remitaConsumerKey=$merchant,remitaConsumerToken=$hash"])
                    ->post(config('student.remita.base_url').'/remita/exapp/api/v1/send/api/echannelsvc/merchant/api/paymentinit', $payload);
            } catch (ConnectionException) {
                throw ValidationException::withMessages(['payment' => 'Remita could not be reached. Retry with this same bill later.']);
            }
            $data = $this->decode($response->body());
            if (! $response->successful() || ($data['statuscode'] ?? '') !== '025' || ! preg_match('/^[0-9]{6,20}$/', (string) ($data['RRR'] ?? ''))) {
                throw ValidationException::withMessages(['payment' => 'Remita has not issued a reference. Contact Bursary with your bill reference before retrying.']);
            }
            $locked->update(['rrr' => $data['RRR']]);
        });
    }

    public function verify(StudentInvoice $invoice): void
    {
        $this->ensureConfigured();
        if ($invoice->paid_at) {
            return;
        }
        if (! $invoice->rrr) {
            throw ValidationException::withMessages(['payment' => 'Generate a Remita reference for this bill first.']);
        }
        $merchant = config('student.remita.merchant_id');
        $hash = hash('sha512', $invoice->rrr.config('student.remita.api_key').$merchant);
        try {
            $response = Http::connectTimeout(4)->timeout(15)->acceptJson()->get(
                config('student.remita.base_url')."/remita/exapp/api/v1/send/api/echannelsvc/$merchant/{$invoice->rrr}/$hash/status.reg"
            );
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['payment' => 'Remita could not be reached. Your bill is still available to check later.']);
        }
        $data = $this->decode($response->body());
        if (! $response->successful()) {
            throw ValidationException::withMessages(['payment' => 'Remita is temporarily unavailable. Please try again later.']);
        }
        if (! in_array($data['status'] ?? '', ['00', '01'], true)) {
            return;
        }
        if ((string) ($data['RRR'] ?? '') !== $invoice->rrr ||
            (string) ($data['orderId'] ?? '') !== $invoice->reference ||
            ! is_numeric($data['amount'] ?? null) ||
            (int) round((float) $data['amount'] * 100) !== $invoice->amount_kobo) {
            throw ValidationException::withMessages(['payment' => 'The payment details do not match this bill. Bursary must reconcile this transaction.']);
        }
        StudentInvoice::query()->whereKey($invoice->id)->whereNull('paid_at')->update(['paid_at' => now()]);
    }

    private function ensureConfigured(): void
    {
        if (! config('student.remita.merchant_id') || ! config('student.remita.service_type_id') || ! config('student.remita.api_key')) {
            throw ValidationException::withMessages(['payment' => 'Online payment is awaiting NFLV Remita configuration. Your bill remains outstanding. Contact Bursary for assistance.']);
        }
    }

    /** @return array<string, mixed> */
    private function decode(string $body): array
    {
        $body = preg_replace('/^\s*jsonp\s*\((.*)\)\s*;?\s*$/s', '$1', $body);
        $data = json_decode($body, true);

        return is_array($data) ? $data : [];
    }
}
