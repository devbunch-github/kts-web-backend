<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Expense Details Breakdown</title>

    <style>
    body {
        margin: 50px;
        font-family: 'Inter', sans-serif;
        color: #333;
    }

    table {
        border-collapse: collapse;
        border-spacing: 0;
        width: 100%;
        font-size: 13px;
    }

    th,
    td {
        text-align: left;
        padding: 10px 12px;
        vertical-align: middle;
    }

    th {
        background: #fafafa;
        border-bottom: 1px solid #e1e1e1;
        color: #111;
        font-weight: 600;
    }

    td {
        border-bottom: 1px solid #e1e1e1;
    }

    h3,
    h5 {
        margin: 0;
        font-weight: normal;
    }

    h3 {
        margin-bottom: 5px;
        font-size: 18px;
    }

    h5 {
        color: #666;
        margin-bottom: 10px;
        font-size: 14px;
    }
    </style>
</head>

<body>
    @php
    $tax_free_category_ids = [20, 21, 22];
    $tax_free_categories = [];

    function asPounds($value) {
    if ($value < 0) { return '-' . asPounds(-$value); } return '£' . number_format($value, 2); } if
        (!empty($this_year_expense)) { foreach ($this_year_expense as $key=> $expense) {
        if (!empty($expense['category']['id']) && in_array($expense['category']['id'], $tax_free_category_ids)) {
        $tax_free_categories[] = $expense;
        unset($this_year_expense[$key]);
        unset($previous_year_expense[$key]);
        }
        }
        }

        $tax_free_categories = array_merge(array_splice($tax_free_categories, -1), $tax_free_categories);
        @endphp

        <h3>{{ $user->name ?? 'Account Summary' }}</h3>
        <h5>Detailed Profit &amp; Loss Account</h5>
        <h5>For Current &amp; Previous Tax Year</h5>

        <table>
            <thead>
                <tr>
                    <th width="50%">&nbsp;</th>
                    <th width="25%">
                        {{ date('Y', strtotime($previous_start_date ?? now())) }}
                        - {{ date('Y', strtotime($previous_end_date ?? now())) }}
                    </th>
                    <th width="25%">
                        {{ date('Y-m-d', strtotime($start_date ?? now())) }}
                        - {{ date('Y-m-d', strtotime($end_date ?? now())) }}
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr style="font-weight:bold;">
                    <td>Turnover</td>
                    <td>{{ asPounds(array_sum(array_column(($previous_year_income ?? collect())->toArray(), 'Amount'))) }}
                    </td>
                    <td>{{ asPounds(array_sum(array_column(($this_year_income ?? collect())->toArray(), 'Amount'))) }}
                    </td>
                </tr>

                <tr style="font-weight:bold;">
                    <td>Tips</td>
                    <td>{{ asPounds(array_sum(array_column(($previous_year_tips ?? []), 'Tip'))) }}</td>
                    <td>{{ asPounds(array_sum(array_column(($this_year_tips ?? []), 'Tip'))) }}</td>
                </tr>

                <tr style="font-weight:bold;">
                    <td>Total Turnover</td>
                    <td>{{ asPounds(array_sum(array_column(($previous_year_income ?? collect())->toArray(), 'Amount')) + array_sum(array_column(($previous_year_tips ?? []), 'Tip'))) }}
                    </td>
                    <td>{{ asPounds(array_sum(array_column(($this_year_income ?? collect())->toArray(), 'Amount')) + array_sum(array_column(($this_year_tips ?? []), 'Tip'))) }}
                    </td>
                </tr>

                @if(!empty($this_year_expense))
                @foreach($this_year_expense as $key => $expense)
                <tr>
                    <td>{{ $expense['category']['name'] ?? 'Unknown Category' }}</td>
                    <td>{{ asPounds($previous_year_expense[$key]['total'] ?? 0) }}</td>
                    <td>{{ asPounds($expense['total'] ?? 0) }}</td>
                </tr>
                @endforeach
                @endif

                <tr style="font-weight:bold;">
                    <td>Total Expense</td>
                    <td>{{ asPounds(array_sum(($previous_year_total_expense ?? collect())->toArray())) }}</td>
                    <td>{{ asPounds(array_sum(($this_year_total_expense ?? collect())->toArray())) }}</td>
                </tr>
            </tbody>

            <tfoot>
                <tr style="font-weight:bold;">
                    <td>Net Profit/Loss</td>
                    <td>{{ asPounds(
                    (array_sum(array_column(($previous_year_income ?? collect())->toArray(), 'Amount')) + array_sum(array_column(($previous_year_tips ?? []), 'Tip')))
                    - doubleval(array_sum(($previous_year_total_expense ?? collect())->toArray()))
                ) }}</td>
                    <td>{{ asPounds(
                    (array_sum(array_column(($this_year_income ?? collect())->toArray(), 'Amount')) + array_sum(array_column(($this_year_tips ?? []), 'Tip')))
                    - doubleval(array_sum(($this_year_total_expense ?? collect())->toArray()))
                ) }}</td>
                </tr>

                @if(!empty($tax_free_categories))
                @foreach($tax_free_categories as $key => $expense)
                <tr style="font-weight:bold;">
                    <td>{{ $expense['category']['name'] ?? 'Tax Free Category' }}</td>
                    <td>{{ asPounds($previous_year_expense[$key]['total'] ?? 0) }}</td>
                    <td>{{ asPounds($expense['total'] ?? 0) }}</td>
                </tr>
                @endforeach
                @endif
            </tfoot>
        </table>
</body>

</html>