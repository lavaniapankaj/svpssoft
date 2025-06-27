<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin\SessionMaster;
use App\Models\LoginLog;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
    public function username()
    {
        return 'username';
    }
    // protected function authenticated(Request $request, $user)
    // {

    //     if ($request->loginPath == "fee-admin/login") {
    //         if ($user->role_id === 2 || $user->role_id === 1) {
    //             return redirect()->route('fee.dashboard');
    //         }
    //     } elseif ($request->loginPath == "student-admin/login") {
    //         if ($user->role_id === 3 || $user->role_id === 1) {
    //             return redirect()->route('student.dashboard');
    //         }
    //     } elseif ($request->loginPath == "marks-admin/login") {
    //         if ($user->role_id === 4 || $user->role_id === 1) {
    //             return redirect()->route('marks.dashboard');
    //         }
    //     } elseif ($request->loginPath == "inventory-admin/login") {
    //         if ($user->role_id === 5 || $user->role_id === 1) {
    //             return redirect()->route('inventory.dashboard');
    //         }
    //     } elseif ($request->loginPath == "admin/login") {
    //         if ($user->role_id === 1) {

    //             return redirect()->route('admin.dashboard');
    //         }
    //     } else {
    //         return redirect()->route('index.page');
    //     }


    //     // return redirect()->route('home');
    // }

    // public function login(Request $request)
    // {
    //     $input = $request->all();

    //     // Validate the request
    //     $request->validate([
    //         'username' => 'required',
    //         'password' => 'required',
    //     ]);

    //     $remember = !empty($input['remember']) && $input['remember'] === 'on';

    //     // Check whether it's email or name
    //     $fieldType = 'name';

    //     if (Auth::attempt([$fieldType => $input['username'], 'password' => $input['password']], $remember)) {
    //         $request->session()->regenerate();

    //         // Log successful login

    //         LoginLog::create([
    //             'ip_address' => $request->ip(),
    //             'browser' => $request->header('User-Agent'),
    //             'id_type' => $fieldType,
    //             'panel' => $request->loginPath == 'admin/login' ? 'Admin' : ($request->loginPath == 'inventory-admin/login' ? 'Inventory' : ($request->loginPath == 'marks-admin/login' ? 'Marks' : ($request->loginPath == 'student-admin/login' ? 'Student' : ($request->logiPath == 'fee-admin/login' ? 'Fee' : '')))),
    //             'email' => $input['username'],
    //             'user_id' => Auth::id(),
    //             'date' => Carbon::now(),
    //             'success' => 1,
    //             'password_attempt' => null,
    //         ]);


    //         return $this->authenticated($request, Auth::user());
    //     } else {
    //         // Log failed login

    //         LoginLog::create([
    //             'ip_address' => $request->ip(),
    //             'browser' => $request->header('User-Agent'),
    //             'id_type' => $fieldType,
    //             'panel' => $request->loginPath == 'admin/login' ? 'Admin' : ($request->loginPath == 'inventory-admin/login' ? 'Inventory' : ($request->loginPath == 'marks-admin/login' ? 'Marks' : ($request->loginPath == 'student-admin/login' ? 'Student' : ($request->logiPath == 'fee-admin/login' ? 'Fee' : '')))),
    //             'email' => $input['username'],
    //             'user_id' => null,
    //             'date' => Carbon::now(),
    //             'success' => 0,
    //             'password_attempt' => $input['password'],
    //         ]);


    //         return redirect()->route($request->loginPath == 'admin/login' ? 'admin.login' : ($request->loginPath == 'inventory-admin/login' ? 'inventory.login' : ($request->loginPath == 'marks-admin/login' ? 'marks.login' : ($request->loginPath == 'student-admin/login' ? 'student.login' : ($request->logiPath == 'fee-admin/login' ? 'fee.login' : '/')))))->withErrors(['username' => 'Incorrect user name or password.']);
    //     }
    // }



    // public function login(Request $request)
    // {
    //     $input = $request->all();

    //     // Validate the request
    //     $request->validate([
    //         'username' => 'required',
    //         'password' => 'required',
    //     ]);

    //     $remember = !empty($input['remember']) && $input['remember'] === 'on';

    //     // Determine the field type (email or name)
    //     $fieldType = 'name';

    //     // Check if the username exists in the database
    //     $user  = User::where($fieldType, $input['username'])->first();


    //     if ($user && Hash::check($input['password'], $user->password)) {
    //         // Attempt login
    //         if (Auth::attempt([$fieldType => $input['username'], 'password' => $input['password']], $remember)) {
    //             $request->session()->regenerate();

    //             // Log successful login
    //             LoginLog::create([
    //                 'ip_address' => $request->ip(),
    //                 'browser' => $request->header('User-Agent'),
    //                 'id_type' => $fieldType,
    //                 'panel' => $request->loginPath == 'admin/login' ? 'Admin' : ($request->loginPath == 'inventory-admin/login' ? 'Inventory' : ($request->loginPath == 'marks-admin/login' ? 'Marks' : ($request->loginPath == 'student-admin/login' ? 'Student' : ($request->logiPath == 'fee-admin/login' ? 'Fee' : '')))),
    //                 'email' => $input['username'],
    //                 'user_id' => Auth::id(),
    //                 'date' => Carbon::now(),
    //                 'success' => 1,
    //                 'password_attempt' => null,
    //             ]);

    //             return $this->authenticated($request, Auth::user());
    //         } else {
    //             // Log failed login due to incorrect password
    //             LoginLog::create([
    //                 'ip_address' => $request->ip(),
    //                 'browser' => $request->header('User-Agent'),
    //                 'id_type' => $fieldType,
    //                 'panel' => $request->loginPath == 'admin/login' ? 'Admin' : ($request->loginPath == 'inventory-admin/login' ? 'Inventory' : ($request->loginPath == 'marks-admin/login' ? 'Marks' : ($request->loginPath == 'student-admin/login' ? 'Student' : ($request->logiPath == 'fee-admin/login' ? 'Fee' : '')))),
    //                 'email' => $input['username'],
    //                 'user_id' => null,
    //                 'date' => Carbon::now(),
    //                 'success' => 0,
    //                 'password_attempt' => $input['password'],
    //             ]);

    //             return redirect()
    //                 ->route($request->loginPath == 'admin/login' ? 'admin.login' : ($request->loginPath == 'inventory-admin/login' ? 'inventory.login' : ($request->loginPath == 'marks-admin/login' ? 'marks.login' : ($request->loginPath == 'student-admin/login' ? 'student.login' : ($request->logiPath == 'fee-admin/login' ? 'fee.login' : 'index.page'))))) // Adjust route as needed
    //                 ->withErrors(['username' => 'Incorrect user name or password.']);
    //         }
    //     } else {
    //         // Log failed login due to non-existent username
    //         LoginLog::create([
    //             'ip_address' => $request->ip(),
    //             'browser' => $request->header('User-Agent'),
    //             'id_type' => $fieldType,
    //             'panel' => $request->loginPath == 'admin/login' ? 'Admin' : ($request->loginPath == 'inventory-admin/login' ? 'Inventory' : ($request->loginPath == 'marks-admin/login' ? 'Marks' : ($request->loginPath == 'student-admin/login' ? 'Student' : ($request->logiPath == 'fee-admin/login' ? 'Fee' : '')))),
    //             'email' => $input['username'],
    //             'user_id' => null,
    //             'date' => Carbon::now(),
    //             'success' => 0,
    //             'password_attempt' => $input['password'],
    //         ]);

    //         return redirect()
    //             ->route($request->loginPath == 'admin/login' ? 'admin.login' : ($request->loginPath == 'inventory-admin/login' ? 'inventory.login' : ($request->loginPath == 'marks-admin/login' ? 'marks.login' : ($request->loginPath == 'student-admin/login' ? 'student.login' : ($request->logiPath == 'fee-admin/login' ? 'fee.login' : 'index.page'))))) // Adjust route as needed
    //             ->withErrors(['username' => 'Incorrect user name or password.']);
    //     }
    // }






    public function login(Request $request)
    {
        $input = $request->all();

        // Validate the request
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $remember = !empty($input['remember']) && $input['remember'] === 'on';
        $fieldType = 'name';

        if (Auth::attempt([$fieldType => $input['username'], 'password' => $input['password']], $remember)) {
            $request->session()->regenerate();

            // Log successful login
            LoginLog::create([
                'ip_address' => $request->ip(),
                'browser' => $request->header('User-Agent'),
                'id_type' => $fieldType,
                'panel' => $this->getPanelName($request->loginPath),
                'user_name' => $input['username'],
                'user_id' => Auth::id(),
                'date' => Carbon::now(),
                'success' => 1,
                'password_attempt' => null,
            ]);

            return $this->authenticated($request, Auth::user());
        } else {
            // Log failed login
            LoginLog::create([
                'ip_address' => $request->ip(),
                'browser' => $request->header('User-Agent'),
                'id_type' => $fieldType,
                'panel' => $this->getPanelName($request->loginPath),
                'user_name' => $input['username'],
                'user_id' => null,
                'date' => Carbon::now(),
                'success' => 0,
                'password_attempt' => $input['password'],
            ]);

            return redirect()->route($this->getLoginRoute($request->loginPath))
                ->withErrors(['username' => 'Incorrect user name or password.']);
        }
    }

    protected function authenticated(Request $request, $user)
    {
        $routeMap = [
            'fee-admin/login' => [
                'roles' => [1, 2],
                'route' => 'fee.dashboard'
            ],
            'student-admin/login' => [
                'roles' => [1, 3],
                'route' => 'student.dashboard'
            ],
            'marks-admin/login' => [
                'roles' => [1, 4],
                'route' => 'marks.dashboard'
            ],
            'inventory-admin/login' => [
                'roles' => [1, 5],
                'route' => 'inventory.dashboard'
            ],
            'admin/login' => [
                'roles' => [1],
                'route' => 'admin.dashboard'
            ]
        ];

        if (isset($routeMap[$request->loginPath])) {
            if (in_array($user->role_id, $routeMap[$request->loginPath]['roles'])) {
                return redirect()->route($routeMap[$request->loginPath]['route']);
            } else {
                // User doesn't have permission for this dashboard
                Auth::logout();
                // return response()->view('errors.403', [], 403);
                return abort(403,"You can't access this page");;
            }
        }

        return redirect()->route('index.page');
    }

    private function getPanelName($loginPath)
    {
        $panels = [
            'admin/login' => 'Admin',
            'inventory-admin/login' => 'Inventory',
            'marks-admin/login' => 'Marks',
            'student-admin/login' => 'Student',
            'fee-admin/login' => 'Fee'
        ];

        return $panels[$loginPath] ?? '';
    }

    private function getLoginRoute($loginPath)
    {
        $routes = [
            'admin/login' => 'admin.login',
            'inventory-admin/login' => 'inventory.login',
            'marks-admin/login' => 'marks.login',
            'student-admin/login' => 'student.login',
            'fee-admin/login' => 'fee.login'
        ];

        return $routes[$loginPath] ?? 'index.page';
    }
}
