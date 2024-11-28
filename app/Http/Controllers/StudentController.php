<?php

namespace App\Http\Controllers;

use App\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    protected $studentService;

    // Inject StudentService vào Controller
    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    public function list(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $currentPage = $request->input('page', 1);

            $students = $this->studentService->getStudentList($perPage, $currentPage);

            return response()->json([
                'code' => 200,
                'message' => 'Thành công',
                'data' => $students,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 11,
                'message' => 'Lỗi khi lấy danh sách sinh viên: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function add(Request $request)
    {
        $result = $this->studentService->createStudent($request->all());
        
        return response()->json([
            'code' => $result['code'],
            'message' =>$result['message'],
        ], $result['code'] ===200 ? 200 : 400);   
    }
    public function update(Request $request,  $id)
    {

        $result = $this->studentService->updateStudent($id, $request->all(), $request->user());

        return response()->json([
            'code' => $result['code'],
            'message' => $result['code'],
        ], $result['code']===200 ? 200 :400);
    }
}
