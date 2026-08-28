@extends('layouts.todo')

@section('content')
    <h1>Edit Todo</h1>
    <form action="{{ route('todos.update', $todo['id']) }}" method="POST">
        @csrf
        <input type="text" name="task" value="{{ $todo['task'] }}" required>
        <button type="submit">Update Todo</button>
    </form>
@endsection
