<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Expense Report</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
    h2 { color: #C08080; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #C08080; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    .meta { font-size: 12px; color: #666; margin-bottom: 5px; }
  </style>
</head>
<body>
  <h2>Expense Report</h2>

  @if($filters['start'] || $filters['end'])
    <div class="meta">
      <strong>Period:</strong>
      {{ $filters['start'] ?? '—' }} to {{ $filters['end'] ?? '—' }}
    </div>
  @endif

  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Supplier</th>
        <th>Amount (£)</th>
        <th>Payment</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
      @forelse($expenses as $exp)
        <tr>
          <td>{{ \Carbon\Carbon::parse($exp->PaidDateTime)->format('d M Y') }}</td>
          <td>{{ $exp->Supplier }}</td>
          <td>{{ number_format($exp->Amount, 2) }}</td>
          <td>{{ $exp->PaymentMethod }}</td>
          <td>{{ $exp->Notes ?? '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="5" align="center">No records found</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
