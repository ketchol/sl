<?php

namespace App\Http\Controllers\UrlManagement;

use App\Contracts\Repositories\UrlManagement\UrlContract;
use App\Events\UrlManagement\Url\AfterAssign;
use App\Events\UrlManagement\Url\AfterQueue;
use App\Events\UrlManagement\Url\BeforeAssign;
use App\Events\UrlManagement\Url\BeforeQueue;
use App\Http\Controllers\Controller;
use App\Models\Url;
use App\Events\UrlManagement\Url\BeforeIndex;
use App\Events\UrlManagement\Url\AfterIndex;
use App\Events\UrlManagement\Url\BeforeCreate;
use App\Events\UrlManagement\Url\AfterCreate;
use App\Events\UrlManagement\Url\BeforeStore;
use App\Events\UrlManagement\Url\AfterStore;
use App\Events\UrlManagement\Url\BeforeShow;
use App\Events\UrlManagement\Url\AfterShow;
use App\Events\UrlManagement\Url\BeforeEdit;
use App\Events\UrlManagement\Url\AfterEdit;
use App\Events\UrlManagement\Url\BeforeUpdate;
use App\Events\UrlManagement\Url\AfterUpdate;
use App\Events\UrlManagement\Url\BeforeDestroy;
use App\Events\UrlManagement\Url\AfterDestroy;
use App\Jobs\Crawl as CrawlJob;
use App\Services\UrlManagement\UrlService;
use Illuminate\Http\Request;

class UrlController extends Controller
{
    protected $request;
    protected $urlService;

    public function __construct(Request $request, UrlService $urlService)
    {
        $this->request = $request;
        $this->urlService = $urlService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        event(new BeforeIndex());

        $urls = $this->urlService->load($this->request->all());
        $status = true;

        event(new AfterIndex());

        if ($this->request->ajax()) {
            return compact(['status', 'urls']);
        } else {
            return view('app.url_management.url.index');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        event(new BeforeCreate());

        event(new AfterCreate());

        return view('app.url_management.url.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        event(new BeforeStore());

        $url = $this->urlService->store($this->request->all());
        $status = true;

        event(new AfterStore());

        return compact(['status', 'url']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Url $url
     * @return \Illuminate\Http\Response
     */
    public function show(Url $url)
    {
        event(new BeforeShow($url));

        $status = true;

        event(new AfterShow($url));

        return compact(['status', 'url']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Url $url
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function edit(Url $url)
    {
        event(new BeforeEdit($url));

        $status = true;

        event(new AfterEdit($url));

        return view('app.url_management.url.edit')->with(compact(['url']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\Url $url
     * @return \Illuminate\Http\Response
     */
    public function update(Url $url)
    {
        event(new BeforeUpdate($url));
        event(new AfterUpdate($url));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Url $url
     * @return \Illuminate\Http\Response
     */
    public function destroy(Url $url)
    {
        event(new BeforeDestroy($url));

        $status = $this->urlService->destroy($url);

        event(new AfterDestroy());

        return compact(['status']);
    }

    /**
     * Push URL to queue
     * @param Url $url
     * @return array
     */
    public function queue(Url $url)
    {
        event(new BeforeQueue($url));

        $this->urlService->queue($url);
        $status = true;

        event(new AfterQueue($url));

        return compact(['status']);
    }


    public function assign(Url $url)
    {
        event(new BeforeAssign($url));

        $this->urlService->assign($url);
        $status = true;

        event(new AfterAssign($url));

        return compact(['status']);
    }
}
