@extends('layouts.default')

@section('title', 'Groups - SpotLite')

@section('links')
    <link rel="stylesheet" href="{{mix('/css/app.css')}}">
@stop

@section('body')
    <div id="sl">
        <default-template v-cloak>
            <default-header slot="default-header"></default-header>
            <default-sidebar slot="default-sidebar"></default-sidebar>
            <default-content slot="default-content">
                <content-header slot="content-header">
                    <span slot="content-header-title">Group Details</span>
                </content-header>
                <content-body slot="content-body"></content-body>
            </default-content>
        </default-template>
    </div>
@stop

@section('scripts')
    <script>
        var showingGroup = JSON.parse('{!! addslashes($group->toJson()) !!}');
    </script>
    <script src="{{mix('/js/app/group/show.js')}}"></script>
@stop