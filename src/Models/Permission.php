<?php

namespace IronFlow\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'ironflow_permissions';

    protected $fillable = [
        'key',
        'module',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'ironflow_permission_role');
    }

    public function users()
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', \App\Models\User::class),
            'ironflow_permission_user'
        );
    }
}
