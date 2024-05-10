<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UsertPolicy
{
    use HandlesAuthorization;

    /**
     * Puede ver el lista de divisas
     *
     * @param User $userAuth
     * @return \Illuminate\Http\Response
     */
    public function index(User $user) {

        // return $userAuthAuth->role == "ADMIN";
        return in_array($user->role, [$user->role == "SUPERADMIN","ADMIN", "DOCTOR"]);
        

    }

    public function viewAny(User $user)
    {
       
       if($user->can('list_staff')){
        return false;
       }
       return false;
    }

    /**
     * Puede ver la información
     *
     * @param User $userAuth
     * @return \Illuminate\Http\Response
     */
    public function userShow(User $userAuth, User $user) {

        // return in_array($userAuth->role, [$userAuth->role == "ADMIN"]);
        return in_array($user->role, [$user->role == "SUPERADMIN","ADMIN","DOCTOR"]);

    }


    /**
     * Puede crear una nueva
     *
     * @param User $user
     * @return \Illuminate\Http\Response
     */
    public function userUpdate(User $userAuth, User $user) {

        // return in_array($userAuth->role, [$userAuth->role == "SUPERADMIN"]);
        return in_array($user->role, [$user->role == "SUPERADMIN","ADMIN","LM"]);

    }

    /**
     * Puede borrar
     *
     * @param User $userAuth
     * @return @return \Illuminate\Http\Response
     */
    public function userDestroy(User $userAuth, User $user) {

        // return in_array($userAuth->role, [$userAuth->role == "SUPERADMIN"]);
        return in_array($user->role, [$user->role == "SUPERADMIN","ADMIN", "LM"]);

    }

    /**
     * Puede ver regsistros borrados
     *
     * @param User $userAuth
     * @return \Illuminate\Http\Response
     */
    public function indexDeletes(User $userAuth) {

        return $userAuth->role == "SUPERADMIN";

    }

    /**
     * Puede ver el registro borrado
     *
     * @param User $userAuth
     * @return \Illuminate\Http\Response
     */
    public function userDeleteShow(User $userAuth, User $user) {

        return in_array($userAuth->role, [$userAuth->role == "SUPERADMIN","ADMIN", "LM"]);

    }

    /**
     * Puede restaurar el resgistro
     *
     * @param User $userAuth
     * @return \Illuminate\Http\Response
     */
    public function userDeleteRestore(User $userAuth, User $user) {

        return in_array($userAuth->role, [$userAuth->role == "SUPERADMIN","ADMIN", "LM"]);

    }

    /**
     * Puede borrar el registro del sistema
     *
     * @param User $userAuth
     * @return \Illuminate\Http\Response
     */
    public function userDeleteForce(User $userAuth, User $user) {

        return in_array($userAuth->role, [$userAuth->role == "SUPERADMIN","ADMIN", "LM"]);

    }
}
