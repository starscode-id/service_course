<?php

namespace App\Http\Controllers;

use App\Models\MyCourse;
use App\Models\course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Psy\Readline\Hoa\Console;

class MyCourseController extends Controller
{
    public function index(Request $request)
    {
        $myCourse = MyCourse::query()->with('course');
        $userId = $request->query('user_id');
        $myCourse->when($userId, function ($query) use ($userId) {
            return $query->where('user_id', '=', $userId);
        });

        return response()->json([
            'status' => 'success',
            'data' => $myCourse->get()
        ]);
    }
    public function create(Request $request)
    {
        $rules = [
            'course_id' => 'required|integer',
            'user_id' => 'required|integer',
        ];

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }
        $courseId = $request->input('course_id');
        $course = course::find($courseId);

        if (!$course) {
            return response()->json([
                'status' => 'error',
                'message' => 'course not found'
            ], 404);
        }

        $userId = $request->input('user_id');

        $user = getUser($userId);

        if (isset($user['status']) && $user['status'] === 'error') {
            return response()->json([
                'status' => $user['status'],
                'message' => $user['message']
            ], $user['http_code']);
        }

        $isExistMyCourse = MyCourse::where('course_id', '=', $courseId)
            ->where('user_id', '=', $userId)
            ->exists();
        if ($isExistMyCourse) {
            return response()->json([
                'status' => 'error',
                'message' => 'user already taken this course'
            ], 409);
        }

        if ($course->type === 'premium') {
            if ($course->price === 0) {
                return response()->json([
                    'status' => 'error',
                    'course' => 'price cant be 0'
                ], 405);
            }
            // echo "<pre>" . print_r($userId, 1) . "</pre>";
            $order = postOrder([
                'user' => $user['data'],
                'course' => $course->toArray()
            ]);
            if ($order['status'] === 'error') {
                return response()->json([
                    'status' => $order['status'],
                    'message' => $order['message']
                ], $order['http_code']);
            }
            return response()->json([
                'status' => $order['status'],
                'data' => $order['data']
            ]);
        } else {
            $myCourse = MyCourse::create($data);
            return response()->json([
                'status' => 'success',
                'data' => $myCourse
            ]);
        }
    }
    public function createPremiumAccess(Request $request)
    {
        $data = $request->all();
        $myCourse = MyCourse::create($data);

        return response()->json([
            'status' => 'success',
            'data' => $myCourse
        ]);
    }
}
