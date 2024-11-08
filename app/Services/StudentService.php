<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StudentService
{
    public function getStudentList($perPage, $currentPage)
    {
        try {
            return DB::table('mdl_user')
                ->select('id as _id', 'firstname', 'lastname', 'email')
                ->where('deleted', 0)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('mdl_role_assignments')
                          ->join('mdl_role', 'mdl_role.id', '=', 'mdl_role_assignments.roleid')
                          ->where('mdl_role.shortname', 'student')
                          ->whereRaw('mdl_role_assignments.userid = mdl_user.id');
                })
                ->paginate($perPage, ['*'], 'page', $currentPage);
        } catch (\Exception $e) {
            throw new \Exception('Lỗi khi lấy danh sách sinh viên: ' . $e->getMessage());
        }
    }

    public function createStudent(array $data)
    {

        $token = request()->bearerToken();
        if (!$token || !$this->isValidToken($token)) {
            return [
                'status' => false,
                'code' => 401,
                'message' => 'Tài khoản token không hợp lệ',
            ];
        }

        $user = Auth::guard('sanctum')->user();


        if (!$user) {
            return [
                'status' => false,
                'code' => 401,
                'message' => 'User không tồn tại hoặc token hết hạn'
            ];
        }

        $validator = Validator::make($data, [
            'hometown' => 'nullable|string|max:255',
            'current_residence' => 'nullable|string|max:255',
            'firstname' => 'nullable|string|max:25',
            'lastname' => 'nullable|string|max:25',
            'description' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return [
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
            ];
        }

        try {
            DB::beginTransaction();

            DB::table('mdl_user')
                ->where('id', $user->id)
                ->update([
                    'city' => $data['hometown'],
                    'address' => $data['current_residence'],
                    'firstname' => $data['firstname'],
                    'lastname' => $data['lastname'],
                    'description' => $data['description'],
                    'timecreated' => now()->timestamp,
                ]);

            $studentRoleId = DB::table('mdl_role') //chia role sinh vien
                ->where('shortname', 'student')
                ->value('id');
            
            if (!$studentRoleId) {
                DB::rollBack();
                return [
                    'status' => false,
                    'code' => 11,
                    'message' => 'Không tìm thấy role student',
                ];
            }

            $roleAssignmentExists = DB::table('mdl_role_assignments')
                ->where('userid', $user->id)
                ->where('roleid', $studentRoleId)
                ->exists();

            if (!$roleAssignmentExists) {
                DB::table('mdl_role_assignments')->insert([
                    'roleid' => $studentRoleId,
                    'contextid' => 1,
                    'userid' => $user->id,
                    'timemodified' => now()->timestamp,
                ]);
            }

            DB::commit();

            return [
                'status' => true,
                'code' => 200,
                'message' => 'Cập nhật thông tin sinh viên thành công'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'code' => 11,
                'message' => 'Lỗi khi cập nhật sinh viên: ' . $e->getMessage(),
            ];
        }
    }

    private function isValidToken($token)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            return $user !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function updateStudent($id, array $data)
    {
        $authUser = Auth::guard('sanctum')->user();
    
        if (!$authUser) {
            return [
                'status' => false,
                'code' => 401,
                'message' => 'Token không hợp lệ',
            ];
        }
    
        $validator = Validator::make($data, [
            'username' => 'required|string',
            'email' => 'required|email',
            'password' => 'nullable|string',
            'phone1' => 'nullable|string',
            'hometown' => 'nullable|string|max:255',
            'current_residence' => 'nullable|string|max:255',
            'firstname' => 'nullable|string|max:25',
            'lastname' => 'nullable|string|max:25',
            'description' => 'nullable|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return [
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
            ];
        }
    
        try {
            DB::beginTransaction();
    
            $student = DB::table('mdl_user')->where('id', $id)->first();
            if (!$student) {
                return [
                    'status' => false,
                    'code' => 11,
                    'message' => 'Không tìm thấy sinh viên với ID: ' . $id,
                ];
            }

            $hashedPassword = $data['password'] 
                ? $this->hashInternalUserPassword($data['password']) 
                : $student->password;
            DB::table('mdl_user')->where('id', $id)->update([
                'username' => isset($data['username']) ? $data['username'] : $student->username,
                'email' => isset($data['email']) ? $data['email'] : $student->email,
                'password' => isset($data['password']) ? $hashedPassword : $student->password,
                'phone1' => isset($data['phone1']) ? $data['phone1'] : $student->phone1,
                'city' => isset($data['hometown']) ? $data['hometown'] : $student->city,
                'address' => isset($data['current_residence']) ? $data['current_residence'] : $student->address,
                'firstname' => isset($data['firstname']) ? $data['firstname'] : $student->firstname,
                'lastname' => isset($data['lastname']) ? $data['lastname'] : $student->lastname,
                'description' => isset($data['description']) ? $data['description'] : $student->description,
                'timemodified' => now()->timestamp,
            ]);
            
            DB::commit();
    
            return [
                'status' => true,
                'code' => 200,
                'message' => 'Cập nhật sinh viên thành công',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'code' => 500,
                'message' => 'Lỗi khi cập nhật sinh viên: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Mã hóa mật khẩu người dùng.
     */
    private function hashInternalUserPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
