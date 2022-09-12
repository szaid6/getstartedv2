<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Role;
use App\Models\Testimonial;
use App\Models\User;
use Barryvdh\DomPDF\PDF;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Pdf as WriterPdf;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class AdminController extends Controller
{
    public function indexAdmin()
    {
        $endusers = User::where('deleteId', '0')->where('role', '5')->orWhere('role', '6')->orWhere('role', '7')->count();
        return view('admin.dashboard', compact('endusers'));
    }

    public function indexHompage()
    {
        return view('homepage.home');
    }

    public function showRegister()
    {
        return view('register');
    }

    public function Register(Request $request)
    {
        $user = new User();
        $user->name = $request->username;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = Hash::make($request->password);
        $user->save();
        return redirect('/login');
    }

    public function showforget(Request $request)
    {
        return view('forgetpassword');
    }

    public function forgetpassword(Request $request)
    {
        $user = User::where('phone', $request->phone)->count();

        if ($user == 0) {
            return response()->json([
                'status' => 201,
                'message' => 'No User with this number',
            ]);
        }
        // Session()->flash('alert-success', "Otp Sent!!");
        // return redirect()->back();

        return response()->json([
            'status' => 200,

        ]);
    }

    public function changepassword(Request $request)
    {

        $user = User::where('phone', $request->phone)->first();
        $user->password = Hash::make($request->pass);
        $user->update();
        return response()->json([
            'status' => 200,
            'message' => 'Password Changed Successfully',
        ]);
    }

    public function login()
    {
        if (Auth::check()) {
            return redirect('dashboard');
        } else {

            return view('login');
            Session()->flash('alert-success', "Please Login in First");
        }
        return view('login');
    }

    public function checkUser(Request $request)
    {
        // return $request;
        $phone = $request->post('login');
        $email = $request->post('login');

        $result = User::where(['phone' => $phone])
            ->orWhere(['email' => $email])
            ->first();

        if ($result) {
            if ($result->status == 1 && $result->deleteId == 0) {
                if (Hash::check($request->post('password'), $result->password)) {
                    Auth::login($result);
                    // return redirect('dashboard');
                    return response()->json([
                        'status' => 200,
                        'message' => 'Logged In succesfully',
                    ]);
                } else {
                    Session()->flash('alert-danger', 'Incorrect Password');
                    // return redirect()->back();
                    return response()->json([
                        'status' => 201,
                        'message' => 'Incorrect Password',
                    ]);
                }
            } else if ($result->status != 1) {
                return response()->json([
                    'status' => 204,
                    'message' => 'User Not active',
                ]);
            } else if ($result->deleteId == 1) {
                return response()->json([
                    'status' => 205,
                    'message' => 'User Deleted',
                ]);
            }
        } else {

            return response()->json([
                'status' => 202,
                'message' => 'Invalid Details',
            ]);
        }
    }

    public function checkPhone(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();
        if ($user) {
            return response()->json([
                'status' => 201,
                'message' => 'User Found',
            ]);
        }
    }

    public function checkPass(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();

        if (Hash::check($request->oldpass, $user->password)) {
            return response()->json([
                'status' => 201,
                'message' => 'User Found',
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'User not Found',
            ]);
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }

    // Office User Controller

    public function indexUser()
    {
        $user = User::where('deleteId', '0')->whereIn('role', [1])->with('rolee')->get();
        $roles = Role::where('deleteId', '0')->whereIn('id', [1])->get();
        return view('admin.user', compact('user', 'roles'));
    }

    public function checkOfficeUserEmail(Request $request)
    {
        $data = User::where('email', $request->email)->where('deleteId', 0)->first();
        if ($data) {
            return response()->json([
                'status' => 201,
                'message' => 'Email Already Exist',
            ]);
        }
    }

    public function checkOfficeUserPhone(Request $request)
    {
        $data = User::where('phone', $request->phone)->where('deleteId', 0)->first();
        if ($data) {
            return response()->json([
                'status' => 201,
                'message' => 'Phone Already Exist',
            ]);
        }
    }

    public function saveUser(Request $request)
    {

        $user = new User;

        $uploadpath = 'media/images/users';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = $image->getClientOriginalName();
            $final_name = time() . $image_name;
            $image->move($uploadpath, $final_name);
            chmod('media/images/users/' . $final_name, 0777);
            $image_path = "media/images/users/" . $final_name;
        } else {
            $image_path = "";
        }

        $user->profileImage = $image_path;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = Hash::make($request->password);
        $user->role = $request->role;
        $user->status = $request->status;
        $user->save();
        Session()->flash('alert-success', "User Added Succesfully");
        return redirect()->back();
    }

    public function exportToCSV()
    {
        $data = User::all();
        $handle = fopen('export.csv', 'w');
        // fputcsv($handle, array('id', 'name', 'email', 'phone', 'role', 'status', 'created_at', 'updated_at'));
        User::chunk(100, function ($users) use ($handle) {
            foreach ($users as $row) {
                fputcsv($handle, $row->toArray(), ';');
            }
        });
        fclose($handle);
        $headers = array(
            'Content-Type' => 'text/csv',
        );
        return response()->download('export.csv', 'export.csv', $headers);
    }

    public function ExportOfficeUserExcel($OfficeUser_data)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4000M');
        try {
            $spreadSheet = new Spreadsheet();
            $spreadSheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);
            $spreadSheet->getActiveSheet()->fromArray($OfficeUser_data);
            $Excel_writer = new Xls($spreadSheet);
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="OfficeUsers_ExportedData.xls"');
            header('Cache-Control: max-age=0');
            ob_end_clean();
            $Excel_writer->save('php://output');
            exit();
        } catch (Exception $e) {
            return;
        }
    }

    function exportOfficeUserData()
    {
        $data = User::orderBy('id', 'DESC')->get();
        $data_array[] = array("Id", "Name", "Phone", "Email", "Role", "Status", "Created At", "Updated At");
        foreach ($data as $data_item) {
            if ($data_item->status == 1) {
                $status = 'Active';
            } else {
                $status = 'Inactive';
            }
            $data_array[] = array(
                'Id' => $data_item->id,
                'Name' => $data_item->name,
                'Phone' => $data_item->phone,
                'Email' => $data_item->email,
                'Role' => $data_item->rolee->name,
                'Status' => $status,
                'Created At' => $data_item->created_at,
                'Updated At' => $data_item->updated_at,
            );
        }
        $this->ExportOfficeUserExcel($data_array);
    }

    public function saveUserExcel(Request $request)
    {
        $this->validate($request, [
            'excel' => 'required|mimes:xls,xlsx'
        ]);
        try {
            $file = $request->file('excel');
            $filename = $file->getClientOriginalName();
            $uploadpath = 'storage/ExcelFiles/User/';
            $filepath = 'storage/ExcelFiles/User/' . $filename;
            $file->move($uploadpath, $filename);

            chmod('storage/ExcelFiles/User/' . $filename, 0777);
            $xls_file = $filepath;
            $reader = new Xlsx();
            $spreadsheet = $reader->load($xls_file);
            $loadedSheetName = $spreadsheet->getSheetNames();

            $writer = new Csv($spreadsheet);
            $sheetName = $loadedSheetName[0];
            foreach ($loadedSheetName as $sheetIndex => $loadedSheetName) {
                $writer->setSheetIndex($sheetIndex);
                $writer->save($loadedSheetName . '.csv');
            }
            $inf = $sheetName . '.csv';
            $fileD = fopen($inf, "r");
            $column = fgetcsv($fileD);
            while (!feof($fileD)) {
                $rowData[] = fgetcsv($fileD);
            }
            $skip_lov = array();
            $counter = 0;
            $failed = 0;
            foreach ($rowData as $value) {

                if (empty($value)) {
                    $counter--;
                } else {
                    $fieldData = new User();  //name of modal
                    $fieldData->name = $value[0];  //name of database feild = colm no in xls
                    $fieldData->dob = $value[1];
                    $fieldData->address = $value[2];
                    $fieldData->phone = $value[3];
                    $fieldData->email = $value[4];
                    $fieldData->password = Hash::make($value[5]);
                    $fieldData->aadhar = $value[6];
                    $fieldData->esicNumber = $value[7];
                    $fieldData->pfNumber = $value[8];
                    $fieldData->role = $value[9];
                    $fieldData->notes = $value[10];
                    $fieldData->siteId = $value[11];
                    $fieldData->dependents = $value[12];
                    $fieldData->skillLevel = $value[13];
                    $fieldData->maxTicketsPerDay = $value[14];
                    $fieldData->save();
                }
                $counter++;
            }
            Session()->flash('alert-success', "File Uploaded Succesfully");
            return redirect()->back();
        } catch (\Exception $e) {
            Session()->flash('alert-danger', 'error:' . $e);
            return redirect()->back();
        }
    }

    public function deleteUser(Request $request)
    {
        $model = User::find($request->hiddenId);
        $model->deleteId = 1;
        $model->save();
        Session()->flash('message', 'User Deleted');
        Session()->flash('alert-success', "User Deleted Succesfully");
        return redirect()->back();
    }

    public function editUser(Request $request)
    {
        $model = User::find($request->hiddenId);

        $uploadpath = 'media/images/users';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = $image->getClientOriginalName();
            $final_name = time() . $image_name;
            $image->move($uploadpath, $final_name);
            chmod('media/images/users/' . $final_name, 0777);
            $image_path = "media/images/users/" . $final_name;
        } else {
            $image_path = User::where('id', $request->hiddenId)->first();
            $image_path = $image_path['image'];
        }

        $model->profileImage = $image_path;
        $model->name = $request->name;
        $model->email = $request->email;
        $model->phone = $request->phone;
        $model->role = $request->role;
        $model->status = $request->status;
        $model->update();
        Session()->flash('alert-success', "User Updated Succesfully");

        return redirect()->back();
    }

    // Enduser controller

    public function indexEnduser()
    {
        $enduser = User::where('deleteId', '0')->whereIn('role', ['2'])->get();
        // return $user;
        $roles = Role::where('deleteId', '0')->whereIn('id', ['2'])->get();
        return view('admin.enduser', compact('enduser', 'roles'));
    }

    public function enduserStatus(Request $request)
    {
        $model = User::find($request->user_id);
        $model->status = $request->status;
        $model->save();

        return response()->json([
            'status' => 200,
            'message' => 'Status Changed Successfully',
        ]);
    }

    public function checkEndUserEmail(Request $request)
    {
        $data = User::where('email', $request->email)->where('deleteId', 0)->first();
        if ($data) {
            return response()->json([
                'status' => 201,
                'message' => 'Email Already Exist',
            ]);
        }
    }

    public function checkEndUserPhone(Request $request)
    {
        $data = User::where('phone', $request->phone)->where('deleteId', 0)->first();
        if ($data) {
            return response()->json([
                'status' => 201,
                'message' => 'Phone Already Exist',
            ]);
        }
    }

    public function saveEnduser(Request $request)
    {
        $request->validate([
            'phone' => 'unique:users',
        ]);
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        // Mail::->to($user->email)->send(new TestEmail());

        $user->phone = $request->phone;
        $user->password = Hash::make($request->password);
        $user->role = $request->role;
        $user->status = $request->status;
        $user->save();
        Session()->flash('alert-success', "User Added Succesfully");
        return redirect()->back();
    }

    public function deleteEnduser(Request $request)
    {
        $model = User::find($request->hiddenId);


        $model->deleteId = 1;
        $model->save();
        Session()->flash('message', 'User Deleted');
        Session()->flash('alert-success', "User Deleted Succesfully");
        return redirect()->back();
    }

    public function editEnduser(Request $request)
    {
        $model = User::find($request->hiddenId);

        $model->name = $request->name;
        $model->email = $request->email;
        $model->phone = $request->phone;
        $model->age = $request->age;
        $model->role = $request->role;
        $model->status = $request->status;
        $model->update();
        Session()->flash('alert-success', "User Updated Succesfully");

        return redirect()->back();
    }

    public function filterEndUser(Request $request)
    {
        $roles = Role::where('deleteId', '0')->where('status', 'active')->where('id', '5')->orWhere('id', '6')->orWhere('id', '7')->get();
        $model = User::query();
        $formid = $request['id'];
        $formname = $request['name'];
        $formemail = $request['email'];
        $formphone = $request['phone'];
        $formrole = $request['role'];
        $formstatus = $request['status'];

        $user = User::where('deleteId', '0')->where('role', '5')->orWhere('role', '6')->orWhere('role', '7')->when($formid, function ($model) use ($formid) {
            return $model->where('id', $formid);
        })->when($formname, function ($model) use ($formname) {
            return $model->where('name', $formname);
        })->when($formemail, function ($model) use ($formemail) {
            return $model->where('email', $formemail);
        })->when($formphone, function ($model) use ($formphone) {
            return $model->where('phone', $formphone);
        })->when($formrole, function ($model) use ($formrole) {
            return $model->where('role', $formrole);
        })->when($formstatus, function ($model) use ($formstatus) {
            return $model->where('status', $formstatus);
        })->get();


        return view('admin.enduser', compact('user', 'roles'));
    }

    // Reset Pass Controller

    public function resetPassIndex()
    {
        return view('resetPass');
    }

    public function resetPass(Request $request)
    {

        $model = User::where(['phone' => $request->phone])->first();

        if (Hash::check($request->oldpass, $model->password)) {
            if ($request->newpass == $request->confirmpass) {
                $model->password = Hash::make($request->newpass);
                $model->update();
                Auth::logout();
                return redirect('/login');
            }
        }
    }


    // Role Controller

    public function indexRole()
    {
        $roles = Role::where('deleteId', '0')->get();

        return view('admin.role', compact('roles'));
    }

    public function storeRole(Request $request)
    {
        $role = new Role;
        $role->name = $request->name;
        $role->status = $request->status;
        $role->save();

        return redirect()->back();;
    }

    public function editRole(Request $request)
    {
        $role = Role::find($request->hiddenId);
        $role->name = $request->name;
        $role->status = $request->status;
        $role->update();
        Session()->flash('alert-success', "Role Updated Succesfully");
        return redirect()->back();;
    }

    public function deleteRole(Request $request)
    {
        $model = Role::find($request->hiddenId);

        $model->deleteId = 1;
        $model->save();
        Session()->flash('alert-success', "Role Updated Succesfully");
        return redirect()->back();;
    }


    // Blog Controller

    public function indexBlog()
    {
        $blogs = Blog::where('deleteId', '0')->get();
        return view('admin.blog', compact('blogs'));
    }

    public function createBlog()
    {
        // return Auth::User()->name;
        return view('admin.addBlog');
    }

    public function storeBlog(Request $request)
    {
        $blog = new Blog;
        $blog->title = $request->title;
        $blog->subtitle = $request->subtitle;
        $blog->tags = $request->tags;

        $uploadpath = 'media/images/blog';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = $image->getClientOriginalName();
            $final_name = time() . $image_name;
            $image->move($uploadpath, $final_name);
            chmod('media/images/blog/' . $final_name, 0777);
            $image_path = "media/images/blog/" . $final_name;
        } else {
            $image_path = Blog::where('id', $request->hiddenId)->first();
            $image_path = $image_path['image'];
        }

        $blog->coverImage = $image_path;
        $blog->creator = Auth::User()->name;
        $blog->writer = $request->writer;
        $blog->status = $request->status;
        $blog->description1 = $request->desc1;
        $blog->description2 = $request->desc2;
        $blog->save();
        Session()->flash('alert-success', "Blog Added Succesfully");
        return redirect('blog');
    }

    public function showBlogEdit($id)
    {
        $blogss = blog::where('deleteId', '0')->where('id', $id)->get();
        return view('admin.editBlog', compact('blogss'));
    }

    public function editBlog(Request $request)
    {
        $blog = Blog::find($request->hiddenId);
        $blog->title = $request->title;
        $blog->subtitle = $request->subtitle;
        $blog->tags = $request->tags;
        $uploadpath = 'media/images/blog';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = $image->getClientOriginalName();
            $final_name = time() . $image_name;
            $image->move($uploadpath, $final_name);
            chmod('media/images/blog/' . $final_name, 0777);
            $image_path = "media/images/blog/" . $final_name;
        } else {
            $image_path = Blog::where('id', $request->hiddenId)->first();
            $image_path = $image_path['image'];
        }

        $blog->coverImage = $image_path;
        $blog->creator = Auth::User()->name;
        $blog->writer = $request->writer;
        $blog->status = $request->status;
        $blog->description1 = $request->desc1;
        $blog->description2 = $request->desc2;
        $blog->update();
        Session()->flash('alert-success', "Blog Updated Succesfully");
        return redirect('blog');
    }

    public function deleteBlog(Request $request)
    {
        $model = Blog::find($request->hiddenId);

        $model->deleteId = 1;
        $model->save();
        Session()->flash('alert-success', "Blog Deleted Succesfully");
        return redirect()->back();
    }

    public function blogStatus(Request $request)
    {
        $model = Blog::find($request->user_id);
        $model->status = $request->status;
        $model->save();

        return response()->json([
            'status' => 200,
            'message' => 'Status Changed Successfully',
        ]);
    }

    // testimonial controller

    public function indexTestimonial()
    {
        $testimonials = Testimonial::where('deleteId', 0)->get();
        return view('admin.testimonial', compact('testimonials'));
    }

    public function storeTestimonial(Request $request)
    {
        $uploadpath = 'media/images/testimonials';
        $testimonials = new Testimonial();

        $testimonials->type = $request->type;
        $testimonials->name = $request->name;
        $testimonials->comment = $request->description;


        if ($request->hasFile('media')) {
            $image = $request->file('media');
            $image_name = $image->getClientOriginalName();
            $final_name = time() . $image_name;
            $image->move($uploadpath, $final_name);

            chmod('media/images/testimonials/' . $final_name, 0777);
            $image_path = "media/images/testimonials/" . $final_name;
        }
        $testimonials->media = $image_path;
        $testimonials->status = $request->status;

        $testimonials->save();
        Session()->flash('alert-success', "Testimonial Added Succesfully");

        return redirect()->back();
    }

    public function editTestimonial(Request $request)
    {
        $uploadpath = 'media/images/testimonials';
        $testimonials = Testimonial::find($request->hiddenId);
        $testimonials->type = $request->types;
        $testimonials->name = $request->name;
        $testimonials->comment = $request->description;

        if ($request->hasFile('media')) {
            $image = $request->file('media');
            $image_name = $image->getClientOriginalName();
            $final_name = time() . $image_name;
            $image->move($uploadpath, $final_name);
            chmod('media/images/testimonials/' . $final_name, 0777);
            $image_path = "media/images/testimonials/" . $final_name;
        } else {
            $image_path = Testimonial::where('id', $request->hiddenId)->first();
            $image_path = $image_path['media'];
        }
        $testimonials->media = $image_path;
        $testimonials->status = $request->status;

        $testimonials->update();
        Session()->flash('alert-success', "Testimonial Updated Succesfully");

        return redirect()->back();
    }

    public function deleteTestimonial(Request $request)
    {
        $model = Testimonial::find($request->hiddenId);
        $model->deleteId = 1;
        $model->save();
        Session()->flash('alert-success', "Testimonial Deleted Succesfully");
        return redirect()->back();
    }

    public function testimonialStatus(Request $request)
    {
        $model = Testimonial::find($request->user_id);
        $model->status = $request->status;
        $model->save();

        return response()->json([
            'status' => 200,
            'message' => 'Status Changed Successfully',
        ]);
    }
}
