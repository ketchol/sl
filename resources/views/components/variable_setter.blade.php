@if(auth()->check())
    <script type="text/javascript">
        var user = {!! auth()->user()->toJson() !!};
    </script>
@endif