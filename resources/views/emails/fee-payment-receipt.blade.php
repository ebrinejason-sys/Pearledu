@extends('emails.layout', ['eyebrow' => 'Fee receipt'])

@section('body')
  <p style="margin:0 0 16px">Please find a receipt for a confirmed fee payment at <strong>{{ $school->name }}</strong>.</p>
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:14px">
    <tr>
      <td style="padding:6px 0;color:#5A7180">Learner</td>
      <td style="padding:6px 0;text-align:right">{{ $payment->invoice?->student?->full_name }}</td>
    </tr>
    <tr>
      <td style="padding:6px 0;color:#5A7180">Invoice</td>
      <td style="padding:6px 0;text-align:right">{{ $payment->invoice?->reference }}</td>
    </tr>
    <tr>
      <td style="padding:6px 0;color:#5A7180">Fee</td>
      <td style="padding:6px 0;text-align:right">{{ $payment->invoice?->structure?->name ?: 'School fees' }}</td>
    </tr>
    <tr>
      <td style="padding:6px 0;color:#5A7180">Amount paid</td>
      <td style="padding:6px 0;text-align:right"><strong>UGX {{ number_format((float) $payment->amount) }}</strong></td>
    </tr>
    <tr>
      <td style="padding:6px 0;color:#5A7180">Balance remaining</td>
      <td style="padding:6px 0;text-align:right">UGX {{ number_format((float) ($payment->invoice?->balance ?? 0)) }}</td>
    </tr>
    <tr>
      <td style="padding:6px 0;color:#5A7180">Method</td>
      <td style="padding:6px 0;text-align:right">{{ str_replace('_', ' ', $payment->method) }}</td>
    </tr>
    <tr>
      <td style="padding:6px 0;color:#5A7180">Date</td>
      <td style="padding:6px 0;text-align:right">{{ $payment->verified_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? $payment->created_at?->format('d M Y') }}</td>
    </tr>
  </table>
@endsection
