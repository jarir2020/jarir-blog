@extends('layouts.todo')

@section('content')
    <h1>Add Todo</h1>
    <form action="{{ route('todos.store') }}" method="POST">
        @csrf
        <input type="text" name="task" placeholder="New Task" style="margin-bottom:5px;" required>
        <center><button type="submit">Add Todo</button><center>
    </form>
@endsection
