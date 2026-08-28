@extends('layouts.todo')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Todo List</h1>
    <form action="{{ route('todos.store') }}" method="POST">
        @csrf
        <input type="text" name="task" placeholder="New Task" style="text-align: left; margin-bottom:5px;" required>
        <center><button type="submit" style="text-align: center; margin-bottom:5px;">Add Todo</button></center>
    </form>
    <table class="min-w-full bg-white border">
        <thead>
            <tr>
                <th class="px-4 py-2 border">Task Name</th>
                <th class="px-4 py-2 border">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($todos as $todo)
                <tr>
                    <!-- Task Name Column -->
                    <td class="px-4 py-2 border">{{ $todo['task'] }}</td>

                    <!-- Action Column -->
                    <td class="px-4 py-2 border text-center">
                        <div class="flex justify-center space-x-2">
                            <!-- Edit Button -->
                            <a href="{{ route('todos.edit', $todo['id']) }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                Edit
                            </a>

                            <!-- Delete Form -->
                            <form action="{{ route('todos.destroy', $todo['id']) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
