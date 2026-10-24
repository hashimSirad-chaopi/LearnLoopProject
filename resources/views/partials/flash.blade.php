@if(session('success'))
    <div class="ll-flash">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="ll-flash error">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
