<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Income Report</title>
  <style>
      body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
      h1 { text-align: center; color: #b36b6b; margin-bottom: 10px; }
      table { width: 100%; border-collapse: collapse; margin-top: 15px; }
      th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
      th { background: #d4a1a1; color: #fff; font-weight: bold; }
      tr:nth-child(even) { background: #f9f9f9; }
      .note { max-width: 300px; word-wrap: break-word; }
      .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #777; }
  </style>
</head>
<body>
  <h1>Income / Sales Report</h1>
  @if($start || $end)
    <p style="text-align:center; margin:0;">
      Period: {{ $start ?? '—' }} to {{ $end ?? '—' }}
    </p>
  @endif

  <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Customer</th>
          <th>Category</th>
          <th>Service</th>
          <th>Amount (£)</th>
          <th>Note</th>
        </tr>
      </thead>
      <tbody>
        @forelse($incomes as $i)
          <tr>
            <td>{{ \Carbon\Carbon::parse($i->PaymentDateTime)->format('d/m/Y') }}</td>
            <td>{{ $i->customer?->Name ?? '—' }}</td>
            <td>{{ $i->category?->Name ?? '—' }}</td>
            <td>{{ $i->service?->Name ?? '—' }}</td>
            <td>£{{ number_format($i->Amount, 2) }}</td>
            <td class="note">{{ $i->Notes ?? $i->Description ?? '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align:center;">No records found</td>
          </tr>
        @endforelse
      </tbody>
  </table>

  <div class="footer">
    Generated on {{ now()->format('d M Y, h:i A') }} by appt.live
  </div>
</body>
</html>
