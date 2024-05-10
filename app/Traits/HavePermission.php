<?php

namespace App\Traits;


trait HavePermission {

    public function havePermission($permission){
        
        foreach ($this->roles as $role ) {
            
            foreach ($role->permissions as $perm) {
                
                if ($perm->name == $permission) {
                    return true;
                }   
            }

        }
        
        return false;
    }

}