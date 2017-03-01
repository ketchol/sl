<?php
namespace App\Repositories\UserManagement;

use App\Contracts\Repositories\UserManagement\UserContract;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 19/02/2017
 * Time: 10:15 PM
 */
class UserRepository implements UserContract
{
    var $user;
    var $request;

    public function __construct(User $user, Request $request)
    {
        $this->user = $user;

        $this->request = $request;
    }

    /**
     * Load all users and filter them
     * @param $data
     * @return mixed
     */
    public function filterAll(Array $data = [])
    {
        $length = array_get($data, 'per_page', 25);
        $orderByColumn = array_get($data, 'orderBy', 'id');
        $orderByDirection = array_get($data, 'direction', 'asc');
        $builder = $this->user;
        $builder = $builder->orderBy($orderByColumn, $orderByDirection);
        if (array_has($data, 'key') && !empty(array_get($data, 'key'))) {
            $key = array_get($data, 'key');
            $builder->where('id', 'LIKE', "%{$key}%");
            $builder->orWhere('first_name', 'LIKE', "%{$key}%");
            $builder->orWhere('last_name', 'LIKE', "%{$key}%");
            $builder->orWhere('email', 'LIKE', "%{$key}%");
            $builder->orWhere('created_at', 'LIKE', "%{$key}%");
            $builder->orWhere('updated_at', 'LIKE', "%{$key}%");
        }
        $users = $builder->paginate($length);
        if ($users->count() == 0) {
            $page = 1;
            $this->request->merge(compact(['page']));
            $users = $builder->paginate($length);
        }
        return $users;
    }

    /**
     * @return mixed
     */
    public function all()
    {
        return $this->user->all();
    }

    /**
     * Get user by ID
     * @param $user_id
     * @param bool $throw
     * @return User
     */
    public function get($user_id, $throw = true)
    {
        if ($throw) {
            return $this->user->findOrFail($user_id);
        } else {
            return $this->user->find($user_id);
        }
    }

    /**
     * Create new user
     * @param array $data
     * @return mixed
     */
    public function store(Array $data)
    {
        $user = new $this->user;
        $user->first_name = array_get($data, 'first_name');
        $user->last_name = array_get($data, 'last_name');
        $user->email = array_get($data, 'email');
        $user->password = bcrypt(array_get($data, 'password', 'secret'));
        $user->save();
        return $user;
    }

    /**
     * Update existing user
     * @param User $user
     * @param array $data
     * @return mixed
     */
    public function update(User $user, Array $data)
    {
        $data = array_except($data, ['email']);
        if (array_has($data, 'password') && !empty(array_get($data, 'password'))) {
            array_set($data, 'password', bcrypt(array_get($data, 'password')));
        }

        $user->update($data);
        return $user;
    }

    /**
     * update user meta info
     * @param User $user
     * @param array $data
     * @return mixed
     */
    public function updateMetas(User $user, Array $data)
    {
        $metas = $user->metas->update($data);
        return $metas;
    }

    /**
     * Remove an existing user
     * @param User $user
     * @return mixed
     */
    public function destroy(User $user)
    {
        $user->delete();
    }

    /**
     * Update roles of a user
     * @param User $user
     * @param array $roles
     * @return mixed|void
     */
    public function updateRoles(User $user, Array $roles)
    {
        $user->detachRoles();
        $user->attachRoles($roles);
    }
}