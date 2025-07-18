<?php

namespace App\Http\Controllers\Task;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest\DeleteRequest;
use App\Http\Requests\TaskRequest\StoreRequest;
use App\Http\Requests\TaskRequest\UpdateRequest;
use App\Models\Group;
use App\Models\Task;
use App\Models\User;
use App\Notifications\Tasks\Taskdeleted;
use App\Notifications\Tasks\TaskUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Notifications\Tasks\TaskAssigned;
use Illuminate\Support\Facades\Http;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request):JsonResponse
    {
        $group=$request->attributes->get('group');
        $tasks= $group->tasks()->with(['author', 'assignedTo'])->get();
        return response()->json([
            'tasks' => $tasks,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request):JsonResponse
    {
        $data = $request->validated();
        $group = $request->attributes->get('group');

        $data['author_id'] = $request->user()->id;
        $task = $group->tasks()->create($data);
        $task->load(['author', 'assignedTo']);

        if($task->assignedTo->id !== $task->author->id) {
            $task->assignedTo->notify(new TaskAssigned($task));
        }

        return response()->json(['task' => $task], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show($groupId, $id):JsonResponse
    {
        $task = Task::with(['author', 'assignedTo'])->findOrFail($id);
        return response()->json(['task' => $task], 200);

    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request , string $groupId,Task $task):JsonResponse
    {
        $data = $request->validated();
        $task->Urgency = $data['Urgency'];
        $task->update($data);
        $task->load(['author', 'assignedTo']);

        if($task->assignedTo->id !== auth()->user()->id) {
            $task->assignedTo->notify(new TaskUpdated($task));
        }
        return response()->json(['task' => $task], 200);

    }

    public function updateTaskStatus(Request $request ,$groupId,$TaskId):JsonResponse
    {

        $validatedData = $request->validate([
            'status' => 'required|string|in:ToDo,Ongoing,Done,Canceled'
        ]);
        $userId= auth()->user()->id;
        $user=User::find($userId);


        $checkIfUserInGroup = $user->groups()->where('groups.id', $groupId)->exists();
        if(!$checkIfUserInGroup) {
            return response()->json(['message' => 'You are not a member of this group'], 403);
        }


        $task = Task::findOrFail($TaskId);
        $checkIfTaskInGroup = $task->group_id == $groupId;
        if(!$checkIfTaskInGroup) {
            return response()->json(['message' => 'This task does not belong to the group'], 404);
        }


        $task->status = request('status');
        $task->save();

        return response()->json(['message' => 'Task status updated successfully', 'task' => $task], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteRequest $request, $groupId,string $id):JsonResponse
    {
        $task = Task::findOrFail($id);

        $task->delete();

        if($task->assignedTo->id !== auth()->user()->id) {
            $task->assignedTo->notify(new Taskdeleted($task));
        }

        return response()->json(['message' => 'Task deleted successfully'], 200);
        //
    }

    public function getTasksByUrgency($groupId,$Urgency): JsonResponse
    {

        $validator = validator()->make(['Urgency' => $Urgency], [
            'Urgency' => 'required|in:Later,Normal,Urgent',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        $group = Group::findOrFail($groupId);
        $tasks = $group->tasks()->where('Urgency', $Urgency)->with(['author', 'assignedTo'])->get();

        return response()->json(['tasks' => $tasks], 200);
    }


    public function makeDocs($id):JsonResponse
    {
        $group = Group::find($id);
        if(!$group)
            throw new NotFoundException('group');

        $data = [
            'tasks' => $group->tasks->map(function ($task) {
                $taskDescription = $task->title;

                foreach ($task->challenges as $challenge) {
                    $taskDescription .= ' and these the challenges with solution challenges ';
                    $taskDescription .= $challenge->content;
                    $taskDescription .= ' and the solution ';
                    $taskDescription .= $challenge->solution->contents ?? 'no solution';
                }

                return ['task' => $taskDescription];
            })->values()->all() // optional: make it a clean array
        ];

        return response()->json($data, 200);
    }

    public function getGroupMembers(int $groupId): \Illuminate\Http\JsonResponse
    {
        $group=Group::findOrFail($groupId);
        $members = $group->users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ];
        })->toArray();
        return response()->json([
            'status'=>true,
            'members'=>$members,
        ],200);
    }

}
