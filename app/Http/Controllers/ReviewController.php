<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\course;
use App\Models\MyCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{

    public function create(Request $request)
    {
        $rules = [
            'course_id' => 'required|integer',
            'user_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'note' => 'string'
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
                'message' => 'Course not found'
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
        $isExistReview = Review::where('course_id', '=', $courseId)->where('user_id', '=', $userId)->exists();
        if ($isExistReview) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already reviewed this course'
            ], 409);
        }
        $review = Review::create($data);
        return response()->json([
            'status' => 'success',
            'data' => $review
        ]);
    }
    public function update(Request $request, $id)
    {
        $rules = [
            'rating' => 'integer|min:1|max:5',
            'note' => 'string'
        ];
        // mengambil semua data dari body tanpa 'user_id' dan 'course_id'
        $data = $request->except('user_id', 'course_id');

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }
        $review = Review::find($id);
        if (!$review) {
            return response()->json([
                'status' => 'error',
                'message' => 'Review not found'
            ]);
        }
        $review->fill($data);
        $review->save();
        return response()->json([
            'status' => 'success',
            'data' => $review
        ]);
    }
    public function destroy($id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json([
                'status' => 'error',
                'message' => 'Review not found'
            ], 404);
        }
        $review->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Review deleted'
        ]);
    }
}
