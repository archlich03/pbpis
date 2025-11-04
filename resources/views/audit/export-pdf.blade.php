<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Audit Logs Export') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #333;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #1f2937;
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        .filters {
            background-color: #f9fafb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .filters-table {
            width: 100%;
            border-collapse: collapse;
        }
        .filters-table td {
            padding: 3px 5px;
            border: none;
        }
        .filter-label {
            font-weight: bold;
            width: 120px;
            vertical-align: top;
        }
        .filter-value {
            vertical-align: top;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #d1d5db;
            font-size: 9px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .details {
            font-size: 8px;
            color: #6b7280;
            max-width: 200px;
            word-wrap: break-word;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>POBIS {{ __('Audit Logs Export') }}</h1>
        <div style="font-size: 9px; color: #6b7280;">
            {{ __('Generated on') }}: {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>

    @if($filters['search'] || $filters['user_id'] || $filters['action'] || $filters['date_from'] || $filters['date_to'])
        <div class="filters">
            <strong>{{ __('Applied Filters') }}:</strong>
            <table class="filters-table">
                @if($filters['search'])
                    <tr>
                        <td class="filter-label">{{ __('Search') }}:</td>
                        <td class="filter-value">{{ $filters['search'] }}</td>
                    </tr>
                @endif
                
                @if($filters['user_id'])
                    <tr>
                        <td class="filter-label">{{ __('User ID') }}:</td>
                        <td class="filter-value">{{ $filters['user_id'] }}</td>
                    </tr>
                @endif
                
                @if($filters['action'])
                    <tr>
                        <td class="filter-label">{{ __('Action') }}:</td>
                        <td class="filter-value">{{ \App\Services\AuditLogService::getActionName($filters['action']) }}</td>
                    </tr>
                @endif
                
                @if($filters['date_from'])
                    <tr>
                        <td class="filter-label">{{ __('Date From') }}:</td>
                        <td class="filter-value">{{ $filters['date_from'] }}</td>
                    </tr>
                @endif
                
                @if($filters['date_to'])
                    <tr>
                        <td class="filter-label">{{ __('Date To') }}:</td>
                        <td class="filter-value">{{ $filters['date_to'] }}</td>
                    </tr>
                @endif
                
                <tr>
                    <td class="filter-label">{{ __('Sorted by') }}:</td>
                    <td class="filter-value">{{ ucfirst($filters['sort']) }} ({{ strtoupper($filters['direction']) }})</td>
                </tr>
            </table>
        </div>
    @endif

    @if($auditLogs->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">{{ __('ID') }}</th>
                    <th style="width: 15%;">{{ __('User') }}</th>
                    <th style="width: 15%;">{{ __('Action') }}</th>
                    <th style="width: 10%;">{{ __('IP Address') }}</th>
                    <th style="width: 15%;">{{ __('Date') }}</th>
                    <th style="width: 37%;">{{ __('Details') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($auditLogs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>
                            @if($log->user)
                                {{ $log->user->name }}<br>
                                <span style="font-size: 7px; color: #9ca3af;">{{ $log->user->email }}</span>
                            @else
                                <span style="color: #9ca3af;">{{ __('Deleted User') }}</span>
                            @endif
                        </td>
                        <td>{{ \App\Services\AuditLogService::getActionName($log->action) }}</td>
                        <td>{{ $log->ip_address }}</td>
                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="details">
                            @if($log->details)
                                @if(is_array($log->details))
                                    @foreach($log->details as $key => $value)
                                        <strong>{{ $key }}:</strong> {{ is_array($value) ? json_encode($value) : $value }}<br>
                                    @endforeach
                                @else
                                    {{ $log->details }}
                                @endif
                            @else
                                <span style="color: #9ca3af;">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="footer">
            {{ __('Total records') }}: {{ $auditLogs->count() }}
        </div>
    @else
        <div class="no-data">
            {{ __('No audit logs found matching the selected filters.') }}
        </div>
    @endif
</body>
</html>
