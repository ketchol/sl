<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 2/20/2017
 * Time: 4:27 PM
 */

namespace App\Listeners\UserManagement;


use App\Jobs\Log\UserActivity;

class PermissionControllerEventSubscriber
{

    public function onBeforeIndex($event)
    {
        $activity = "Visited Permissions Page";
        $this->dispatchUserActivityLog($activity);
    }

    public function onAfterIndex($event)
    {

    }

    public function onBeforeShow($event)
    {
        $permission = $event->permission;
    }

    public function onAfterShow($event)
    {
        $permission = $event->permission;
        $activity = "Loaded Permission {$permission->getKey()}";
        $this->dispatchUserActivityLog($activity);
    }

    public function onBeforeCreate($event)
    {

    }

    public function onAfterCreate($event)
    {

    }

    public function onBeforeStore($event)
    {

    }

    public function onAfterStore($event)
    {
        $permission = $event->permission;
        $activity = "Created Permission {$permission->getKey()}";
        $this->dispatchUserActivityLog($activity);
    }

    public function onBeforeEdit($event)
    {
        $permission = $event->permission;
    }

    public function onAfterEdit($event)
    {
        $permission = $event->permission;
        $activity = "Editing Permission {$permission->getKey()}";
        $this->dispatchUserActivityLog($activity);
    }

    public function onBeforeUpdate($event)
    {
        $permission = $event->permission;
    }

    public function onAfterUpdate($event)
    {
        $permission = $event->permission;
        $activity = "Updated Permission {$permission->getKey()}";
        $this->dispatchUserActivityLog($activity);
    }

    public function onBeforeDestroy($event)
    {
        $permission = $event->permission;
        $activity = "Deleting Permission {$permission->getKey()}";
        $this->dispatchUserActivityLog($activity);
    }

    public function onAfterDestroy($event)
    {
        $activity = "Deleted Permission";
        $this->dispatchUserActivityLog($activity);
    }

    protected function dispatchUserActivityLog($activity)
    {
        dispatch((new UserActivity(auth()->user(), $activity))->onQueue("log")->onConnection('sync'));
    }


    public function subscribe($events)
    {
        $events->listen(
            'App\Events\UserManagement\Permission\BeforeIndex',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onBeforeIndex'
        );
        $events->listen(
            'App\Events\UserManagement\Permission\AfterIndex',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onAfterIndex'
        );

        $events->listen(
            'App\Events\UserManagement\Permission\BeforeShow',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onBeforeShow'
        );
        $events->listen(
            'App\Events\UserManagement\Permission\AfterShow',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onAfterShow'
        );

        $events->listen(
            'App\Events\UserManagement\Permission\BeforeCreate',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onBeforeCreate'
        );
        $events->listen(
            'App\Events\UserManagement\Permission\AfterCreate',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onAfterCreate'
        );

        $events->listen(
            'App\Events\UserManagement\Permission\BeforeStore',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onBeforeStore'
        );
        $events->listen(
            'App\Events\UserManagement\Permission\AfterStore',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onAfterStore'
        );

        $events->listen(
            'App\Events\UserManagement\Permission\BeforeEdit',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onBeforeEdit'
        );
        $events->listen(
            'App\Events\UserManagement\Permission\AfterEdit',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onAfterEdit'
        );

        $events->listen(
            'App\Events\UserManagement\Permission\BeforeUpdate',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onBeforeUpdate'
        );
        $events->listen(
            'App\Events\UserManagement\Permission\AfterUpdate',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onAfterUpdate'
        );

        $events->listen(
            'App\Events\UserManagement\Permission\BeforeDestroy',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onBeforeDestroy'
        );
        $events->listen(
            'App\Events\UserManagement\Permission\AfterDestroy',
            'App\Listeners\UserManagement\PermissionControllerEventSubscriber@onAfterDestroy'
        );
    }
}