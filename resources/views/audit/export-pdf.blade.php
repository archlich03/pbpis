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
        .filter-item {
            margin-bottom: 5px;
        }
        .filter-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
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
        <h1>{{ __('Audit Logs Export') }}</h1>
        <div style="font-size: 9px; color: #6b7280;">
            {{ __('Generated on') }}: {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>

    @if($filters['search'] || $filters['user_id'] || $filters['action'] || $filters['date_from'] || $filters['date_to'])
        <div class="filters">
            <strong>{{ __('Applied Filters') }}:</strong>
            
            @if($filters['search'])
                <div class="filter-item">
                    <span class="filter-label">{{ __('Search') }}:</span>
                    <span>{{ $filters['search'] }}</span>
                </div>
            @endif
            
            @if($filters['user_id'])
                <div class="filter-item">
                    <span class="filter-label">{{ __('User ID') }}:</span>
                    <span>{{ $filters['user_id'] }}</span>
                </div>
            @endif
            
            @if($filters['action'])
                <div class="filter-item">
                    <span class="filter-label">{{ __('Action') }}:</span>
                    <span>{{ \App\Services\AuditLogService::getActionName($filters['action']) }}</span>
                </div>
            @endif
            
            @if($filters['date_from'])
                <div class="filter-item">
                    <span class="filter-label">{{ __('Date From') }}:</span>
                    <span>{{ $filters['date_from'] }}</span>
                </div>
            @endif
            
            @if($filters['date_to'])
                <div class="filter-item">
                    <span class="filter-label">{{ __('Date To') }}:</span>
                    <span>{{ $filters['date_to'] }}</span>
                </div>
            @endif
            
            <div class="filter-item">
                <span class="filter-label">{{ __('Sorted by') }}:</span>
                <span>{{ ucfirst($filters['sort']) }} ({{ strtoupper($filters['direction']) }})</span>
            </div>
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
