<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
class UsuarioController extends Controller {
    public function index() { return view('usuarios.index',['usuarios'=>User::orderBy('name')->get()]); }
    public function store(Request $request) {
        $data=$request->validate(['name'=>['required','string','max:120'],'email'=>['required','email','max:190','unique:users,email'],'password'=>['required','confirmed',Password::min(8),'max:128'],'role'=>['required','in:admin,operador']]);
        User::create($data+['active'=>true]);
        return back()->with('success','Usuario creado. Ya puede ingresar.');
    }
    public function toggle(Request $request,User $usuario) {
        abort_if($usuario->id===$request->user()->id,422,'No podés desactivar tu propia cuenta.');
        $usuario->update(['active'=>!$usuario->active]);
        return back()->with('success','Estado del usuario actualizado.');
    }
}
