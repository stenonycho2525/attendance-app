{{-- 申請一覧のテーブル。$applications と、詳細リンク先を決める $isAdmin を受け取る --}}
<table class="table">
    <tr class="table__row">
        <th class="table__header">
            <p class="table__header--item">状態</p>
        </th>
        <th class="table__header">
            <p class="table__header--item">名前</p>
        </th>
        <th class="table__header">
            <p class="table__header--item">対象日時</p>
        </th>
        <th class="table__header">
            <p class="table__header--item">申請理由</p>
        </th>
        <th class="table__header">
            <p class="table__header--item">申請日時</p>
        </th>
        <th class="table__header">
            <p class="table__header--item">詳細</p>
        </th>
    </tr>
    @foreach ($applications as $application)
    <tr class="table__row">
        <td class="table__description">
            <p class="table__description--item">{{ $application->status }}</p>
        </td>
        <td class="table__description">
            <p class="table__description--item">{{ $application->user->name }}</p>
        </td>
        <td class="table__description">
            <p class="table__description--item">{{ $application->date->format('Y/m/d') }}</p>
        </td>
        <td class="table__description">
            <p class="table__description--item">{{ $application->comment }}</p>
        </td>
        <td class="table__description">
            <p class="table__description--item">{{ $application->created_at->format('Y/m/d') }}</p>
        </td>
        <td class="table__description">
            @if ($isAdmin)
            <a class="table__item--detail-link" href="{{ url('/stamp_correction_request/approve/' . $application->id) }}">詳細</a>
            @else
            <a class="table__item--detail-link" href="{{ url('/attendance/detail/' . $application->attendance_record_id) }}">詳細</a>
            @endif
        </td>
    </tr>
    @endforeach
</table>
