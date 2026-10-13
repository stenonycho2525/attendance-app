@extends('layouts.admin-app')

@section('css')
@vite('resources/css/admin/admin-application-list.css')
@endsection

@section('content')
<div class="application-list__content">
    <div class="content__header">
        <h1 class="content__header--item">申請一覧</h1>
    </div>
    <div class="application__tab">
        <input class="application__tab--input" id="tab1" type="radio" name="tab_item" checked>
        <label class="application__tab--label" for="tab1">承認待ち</label>
        <input class="application__tab--input" id="tab2" type="radio" name="tab_item">
        <label class="application__tab--label" for="tab2">承認済み</label>
        <div class="tab__content" id="content1">
            @include('partials.application-table', ['applications' => $pendingApplications, 'isAdmin' => true])
        </div>
        <div class="tab__content" id="content2">
            @include('partials.application-table', ['applications' => $approvedApplications, 'isAdmin' => true])
        </div>
    </div>
</div>
@endsection
