<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $todos = $request->session()->get('todos', []);
        return view('todos.index', compact('todos'));
    }

    public function create()
    {
        return view('todos.create');
    }


    public function store(Request $request)
    {
        $request->validate(['task' => 'required']);
        $todos = $request->session()->get('todos', []);
        $todos[] = ['task' => $request->task, 'id' => uniqid()];
        $request->session()->put('todos', $todos);
        return redirect()->route('todos.index');
    }

    public function edit(Request $request, $id)
    {
        $todos = $request->session()->get('todos', []);
        $todo = collect($todos)->firstWhere('id', $id);
        if (!$todo) {
            return redirect()->route('todos.index');
        }
        return view('todos.edit', compact('todo'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['task' => 'required']);
        $todos = $request->session()->get('todos', []);
        foreach ($todos as &$todo) {
            if ($todo['id'] == $id) {
                $todo['task'] = $request->task;
                break;
            }
        }
        $request->session()->put('todos', $todos);
        return redirect()->route('todos.index');
    }

    public function destroy(Request $request, $id)
    {
        $todos = $request->session()->get('todos', []);
        $todos = array_filter($todos, fn($todo) => $todo['id'] !== $id);
        $request->session()->put('todos', $todos);
        return redirect()->route('todos.index');
    }
}
