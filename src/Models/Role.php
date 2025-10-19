<?php

namespace IronFlow\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'ironflow_roles';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'ironflow_permission_role');
    }

    public function users()
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', \App\Models\User::class),
            'ironflow_role_user'
        );
    }
}
