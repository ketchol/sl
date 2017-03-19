<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 3/15/2017
 * Time: 5:17 PM
 */

namespace App\Listeners\UrlManagement;


use App\Jobs\Log\UserActivity;

class ItemControllerEventSubscriber
{


    public function onBeforeIndex($event)
    {

    }

    public function onAfterIndex($event)
    {
        $activity = "Visited Items Page";
        dispatch((new UserActivity(auth()->user(), $activity))->onQueue("log"));
    }

    public function onBeforeShow($event)
    {
        $item = $event->item;
    }

    public function onAfterShow($event)
    {
        $item = $event->item;
        $activity = "Loaded Item {$item->getKey()}";
        dispatch((new UserActivity(auth()->user(), $activity))->onQueue("log"));
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
        $item = $event->item;
        $activity = "Created Item {$item->getKey()}";
        dispatch((new UserActivity(auth()->user(), $activity))->onQueue("log"));
    }

    public function onBeforeEdit($event)
    {
        $item = $event->item;
    }

    public function onAfterEdit($event)
    {
        $item = $event->item;
    }

    public function onBeforeUpdate($event)
    {
        $item = $event->item;
    }

    public function onAfterUpdate($event)
    {
        $item = $event->item;
        $activity = "Updated Item {$item->getKey()}";
        dispatch((new UserActivity(auth()->user(), $activity))->onQueue("log"));
    }

    public function onBeforeDestroy($event)
    {
        $item = $event->item;
        $activity = "Deleting Item {$item->getKey()}";
        dispatch((new UserActivity(auth()->user(), $activity))->onQueue("log"));
    }

    public function onAfterDestroy($event)
    {
        $activity = "Deleted Item";
        dispatch((new UserActivity(auth()->user(), $activity))->onQueue("log"));
    }

    public function subscribe($events)
    {
        $events->listen(
            'App\Events\UrlManagement\Item\BeforeIndex',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onBeforeIndex'
        );
        $events->listen(
            'App\Events\UrlManagement\Item\AfterIndex',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onAfterIndex'
        );

        $events->listen(
            'App\Events\UrlManagement\Item\BeforeShow',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onBeforeShow'
        );
        $events->listen(
            'App\Events\UrlManagement\Item\AfterShow',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onAfterShow'
        );

        $events->listen(
            'App\Events\UrlManagement\Item\BeforeCreate',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onBeforeCreate'
        );
        $events->listen(
            'App\Events\UrlManagement\Item\AfterCreate',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onAfterCreate'
        );

        $events->listen(
            'App\Events\UrlManagement\Item\BeforeStore',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onBeforeStore'
        );
        $events->listen(
            'App\Events\UrlManagement\Item\AfterStore',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onAfterStore'
        );

        $events->listen(
            'App\Events\UrlManagement\Item\BeforeEdit',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onBeforeEdit'
        );
        $events->listen(
            'App\Events\UrlManagement\Item\AfterEdit',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onAfterEdit'
        );

        $events->listen(
            'App\Events\UrlManagement\Item\BeforeUpdate',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onBeforeUpdate'
        );
        $events->listen(
            'App\Events\UrlManagement\Item\AfterUpdate',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onAfterUpdate'
        );

        $events->listen(
            'App\Events\UrlManagement\Item\BeforeDestroy',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onBeforeDestroy'
        );
        $events->listen(
            'App\Events\UrlManagement\Item\AfterDestroy',
            'App\Listeners\UrlManagement\UrlControllerEventSubscriber@onAfterDestroy'
        );
    }
}