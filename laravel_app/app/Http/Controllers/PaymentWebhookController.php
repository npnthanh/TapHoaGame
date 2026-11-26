<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments)
    {
        abort_unless(config('shop.payments.enabled') && strlen((string) config('shop.payments.secret')) >= 32 && config('shop.payments.account'), 503);
        abort_if(strlen($request->getContent()) > 16384, 413);
        $timestamp = (string) $request->header('X-Payment-Timestamp', '');
        $signature = (string) $request->header('X-Payment-Signature', '');
        abort_unless(ctype_digit($timestamp) && abs(time() - (int) $timestamp) <= 300, 401);
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), config('shop.payments.secret'));
        abort_unless(hash_equals($expected, $signature), 401);
        $data = $request->validate([
            'event_id' => 'required|string|max:100', 'transaction_id' => 'required|string|max:100',
            'reference' => 'required|string|max:40', 'amount' => 'required|integer|min:10000|max:50000000',
            'currency' => 'required|in:VND', 'direction' => 'required|in:in', 'status' => 'required|in:paid', 'account' => 'required|string|max:100',
        ]);
        try { $payments->confirm($data, hash('sha256', $request->getContent())); }
        catch (UniqueConstraintViolationException) { abort(409, 'Transaction or event already used.'); }
        return response()->json(['ok' => true]);
    }
}
