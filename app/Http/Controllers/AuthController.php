<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
class AuthController extends Controller {
    public function login() {
        return User::exists() ? view('auth.login') : redirect()->route('setup');
    }
    public function authenticate(Request $request) {
        $data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        if(!Auth::attempt($data+['active'=>true],false)) { return back()->withErrors(['email'=>'El correo o la contraseña no son correctos.'])->onlyInput('email'); }
        $request->session()->regenerate();
        return redirect()->intended(route('grilla'));
    }
    public function setup() {
        return User::exists() ? redirect()->route('login') : view('auth.setup');
    }
    public function initialize(Request $request) {
        abort_if(User::exists(),403,'La configuración inicial ya fue completada.');
        $data=$request->validate(['name'=>['required','string','max:120'],'email'=>['required','email','max:190','unique:users,email'],'password'=>['required','confirmed',Password::min(8),'max:128']]);
        try {
            $user=DB::transaction(function() use($data): User {
                DB::table('application_setup')->insert(['id'=>1]);
                return User::create($data+['role'=>'admin','active'=>true]);
            });
        } catch(UniqueConstraintViolationException) { abort(403,'La configuración inicial ya fue completada.'); }
        Auth::login($user); $request->session()->regenerate();
        return redirect()->route('grilla')->with('success','Cuenta creada. Cargá primero los técnicos y móviles desde Catálogos.');
    }
    public function logout(Request $request) {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
