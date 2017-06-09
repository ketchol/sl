<?php

namespace App\Jobs;

use App\Contracts\Repositories\Alert\HistoricalAlertContract;
use App\Mail\Alert\AdvancedCategoryMyPrice;
use App\Mail\Alert\AdvancedCategoryPriceChange;
use App\Mail\Alert\AdvancedProductCustom;
use App\Mail\Alert\AdvancedProductMyPrice;
use App\Mail\Alert\AdvancedProductPriceChange;
use App\Mail\Alert\BasicMyPrice;
use App\Mail\Alert\BasicPriceChange;
use App\Models\Alert as AlertModel;

use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class Alert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $alert;
    protected $lastActiveAt = null;
    protected $alertCreatedAt = null;
    protected $historicalAlertRepo;
    protected $user;

    protected $emailData;

    /**
     * Create a new job instance.
     *
     * @param AlertModel $alert
     */
    public function __construct(AlertModel $alert)
    {
        $this->alert = $alert;
        $this->user = $alert->user;
        if (!is_null($this->alert->last_active_at)) {
            $this->lastActiveAt = Carbon::parse($this->alert->last_active_at);
        }
        if (!is_null($this->alert->created_at)) {
            $this->alertCreatedAt = Carbon::parse($this->alert->created_at);
        }
        $this->historicalAlertRepo = app(HistoricalAlertContract::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->_validateSubscription($this->alert)) {
            return;
        }

        switch ($this->alert->alert_type) {
            case 'basic':
                $this->processBasicAlert();
                break;
            case 'advanced':
                $this->processAdvancedAlert();
                break;

        }

        /*TODO if it's within crawling frequency, ignore*/

        /*TODO if it's outside crawling frequency, trigger alert email*/
    }

    protected function processBasicAlert()
    {
        switch ($this->alert->comp_type) {
            case 'my_price':
                $this->_processBasicMyPrice();
                break;
            case 'price_change':
                $this->_processBasicPriceChange();
                break;
        }
    }

    protected function processAdvancedAlert()
    {
        switch ($this->alert->alertable_type) {
            case 'product':
                switch ($this->alert->comp_type) {
                    case 'my_price':
                        $this->_processAdvancedProductMyPrice();
                        break;
                    case 'price_change':
                        $this->_processAdvancedProductPriceChange();
                        break;
                    case 'custom':
                        $this->_processAdvancedProductCustom();
                }
                break;
            case 'category':
                switch ($this->alert->comp_type) {
                    case 'my_price':
                        $this->_processAdvancedCategoryMyPrice();
                        break;
                    case 'price_change':
                        $this->_processAdvancedCategoryPriceChange();
                        break;
                }
                break;
        }
    }

    /**
     * validate advanced alert - product my price is beaten
     * @return bool
     */
    private function _processAdvancedProductMyPrice()
    {
        $product = $this->alert->alertable;

        $userDomains = $this->user->domains->pluck('alias', 'domain')->all();

        if (is_null($product)) {
            return false;
        }
        $sites = $product->sites;

        $mySites = $sites->filter(function ($site) {
            return $this->_isMySite($site);
        });
        $notMySites = $sites->filter(function ($site) {
            return !$this->_isMySite($site);
        });

        if ($mySites->count() == 0 || $notMySites->count() == 0) {
            return false;
        }

        $beatenBySites = collect();
        foreach ($mySites as $mySite) {

            $mySitePriceHasChanged = $this->_siteHasPriceChange($mySite);

            $mySiteItem = $mySite->item;

            if (is_null($mySiteItem)) {
                continue;
            }
            $lastChangedAt = null;
            if (!is_null($mySiteItem->lastChangedAt)) {
                $lastChangedAt = Carbon::parse($mySiteItem->lastChangedAt);
            }
            foreach ($notMySites as $notMySite) {
                if ($this->_siteHasPriceChange($notMySite) || $mySitePriceHasChanged) {

                    $notMySiteItem = $notMySite->item;

                    if (is_null($notMySiteItem)) {
                        continue;
                    }

                    $notMySiteLastChangedAt = null;
                    if (!is_null($notMySiteItem->lastChangedAt)) {
                        $notMySiteLastChangedAt = Carbon::parse($notMySiteItem->lastChangedAt);
                    }

                    $comparedDateTime = !is_null($this->lastActiveAt) ? $this->lastActiveAt : $this->alertCreatedAt;

                    /* either my site or not my site has changed price */
                    if ((!is_null($lastChangedAt) && $lastChangedAt > $comparedDateTime) || (!is_null($notMySiteLastChangedAt) && $notMySiteLastChangedAt > $comparedDateTime)) {
                        /* both my site and not my site have recent prices */
                        if (!is_null($mySiteItem->recentPrice) && !is_null($notMySiteItem->recentPrice)) {
                            if (floatval($notMySiteItem->recentPrice) < floatval($mySiteItem->recentPrice)) {

                                $siteDomain = domain($notMySite->siteUrl);

                                if (!is_null($notMySite->item) && !is_null($notMySite->item->sellerUsername)) {
                                    $notMySite->setAttribute('displayName', "eBay: {$notMySite->item->sellerUsername}");
                                } elseif (array_has($userDomains, $siteDomain) && !is_null(array_get($userDomains, $siteDomain))) {
                                    $notMySite->setAttribute('displayName', array_get($userDomains, $siteDomain));
                                } else {
                                    $notMySite->setAttribute('displayName', $notMySite->url->domainFullPath);
                                }

                                $beatenBySites->push($notMySite);
                            }
                        }
                    }
                }
            }
        }
        if ($beatenBySites->count() > 0) {
            $this->alert->setLastActiveAt();

            $email = $this->user->email;

            $this->historicalAlertRepo->store($this->alert, compact(['email']));

            /* TODO dispatch mail job with beatenBySites */
            Mail::to($email)
                ->send(new AdvancedProductMyPrice($this->user, $product));
        }

        return true;
    }

    /**
     * validate advanced alert - product price change alert
     * @return bool
     */
    private function _processAdvancedProductPriceChange()
    {
        $product = $this->alert->alertable;

        $userDomains = $this->user->domains->pluck('alias', 'domain')->all();

        if (is_null($product)) {
            return false;
        }

        $sites = $product->sites;

        $alertSites = collect();
        foreach ($sites as $site) {
            if ($this->_siteHasPriceChange($site)) {

                $siteDomain = domain($site->siteUrl);

                if (!is_null($site->item) && !is_null($site->item->sellerUsername)) {
                    $site->setAttribute('displayName', "eBay: {$site->item->sellerUsername}");
                } elseif (array_has($userDomains, $siteDomain) && !is_null(array_get($userDomains, $siteDomain))) {
                    $site->setAttribute('displayName', array_get($userDomains, $siteDomain));
                } else {
                    $site->setAttribute('displayName', $site->url->domainFullPath);
                }

                $alertSites->push($site);
            }
        }
        if ($alertSites->count() > 0) {
            $this->alert->setLastActiveAt();

            $email = $this->user->email;

            $this->historicalAlertRepo->store($this->alert, compact(['email']));

            /* TODO dispatch mail job with $alertSites */
            Mail::to($email)
                ->send(new AdvancedProductPriceChange($this->user, $product, $alertSites));
        }

        return true;
    }

    /**
     * validate advanced alert - product custom validation alert
     * @return bool
     */
    private function _processAdvancedProductCustom()
    {
        $product = $this->alert->alertable;

        $userDomains = $this->user->domains->pluck('alias', 'domain')->all();

        if (is_null($product)) {
            return false;
        }
        $sites = $product->sites;

        $alertSites = collect();
        foreach ($sites as $site) {

            if ($this->_siteHasPriceChange($site)) {

                $siteItem = $site->item;

                if (is_null($siteItem)) {
                    continue;
                }

                $siteLastChangedAt = null;
                if (!is_null($siteItem->lastChangedAt)) {
                    $siteLastChangedAt = Carbon::parse($siteItem->lastChangedAt);
                }

                $comparedDateTime = !is_null($this->lastActiveAt) ? $this->lastActiveAt : $this->alertCreatedAt;

                if (!is_null($siteLastChangedAt) && $siteLastChangedAt > $comparedDateTime) {
                    if (!is_null($siteItem->recentPrice)) {

                        $comparedResult = null;
                        $comparedPrice = $this->alert->comp_price;
                        switch ($this->alert->comp_operator) {
                            case '<':
                                $comparedResult = floatval($siteItem->recentPrice) < $comparedPrice;
                                break;
                            case '<=':
                                $comparedResult = floatval($siteItem->recentPrice) <= $comparedPrice;
                                break;
                            case '>':
                                $comparedResult = floatval($siteItem->recentPrice) > $comparedPrice;
                                break;
                            case '>=':
                                $comparedResult = floatval($siteItem->recentPrice) >= $comparedPrice;
                                break;
                            case '=':
                                $comparedResult = floatval($siteItem->recentPrice) == $comparedPrice;
                                break;
                        }
                        if ($comparedResult) {

                            $siteDomain = domain($site->siteUrl);

                            if (!is_null($site->item) && !is_null($site->item->sellerUsername)) {
                                $site->setAttribute('displayName', "eBay: {$site->item->sellerUsername}");
                            } elseif (array_has($userDomains, $siteDomain) && !is_null(array_get($userDomains, $siteDomain))) {
                                $site->setAttribute('displayName', array_get($userDomains, $siteDomain));
                            } else {
                                $site->setAttribute('displayName', $site->url->domainFullPath);
                            }

                            $alertSites->push($site);
                        }
                    }
                }
            }
        }
        if ($alertSites->count() > 0) {
            $this->alert->setLastActiveAt();

            $email = $this->user->email;

            $this->historicalAlertRepo->store($this->alert, compact(['email']));

            /* TODO dispatch mail job with beatenBySites */
            Mail::to($email)
                ->send(new AdvancedProductCustom($this->user, $this->alert, $alertSites));
        }

        return true;
    }

    /**
     * validate advanced alert - category my price beaten alert
     * @return bool
     */
    private function _processAdvancedCategoryMyPrice()
    {
        $category = $this->alert->alertable()->with('products', 'products.sites', 'products.sites.item')->first();

        if (is_null($category)) {
            return false;
        }

        $products = $category->products;

        $userDomains = $this->user->domains->pluck('alias', 'domain')->all();

        $alertProducts = collect();
        foreach ($products as $product) {
            $sites = $product->sites;
            $mySites = $sites->filter(function ($site) {
                return $this->_isMySite($site);
            });
            $notMySites = $sites->filter(function ($site) {
                return !$this->_isMySite($site);
            });
            if ($mySites->count() == 0 || $notMySites->count() == 0) {
                continue;
            }
            $beatenBySites = collect();
            foreach ($mySites as $mySite) {

                $mySitePriceHasChanged = $this->_siteHasPriceChange($mySite);

                $mySiteItem = $mySite->item;

                if (is_null($mySiteItem)) {
                    continue;
                }
                $lastChangedAt = null;
                if (!is_null($mySiteItem->lastChangedAt)) {
                    $lastChangedAt = Carbon::parse($mySiteItem->lastChangedAt);
                }
                foreach ($notMySites as $notMySite) {

                    if ($this->_siteHasPriceChange($notMySite) || $mySitePriceHasChanged) {
                        $notMySiteItem = $notMySite->item;

                        if (is_null($notMySiteItem)) {
                            continue;
                        }

                        $notMySiteLastChangedAt = null;
                        if (!is_null($notMySiteItem->lastChangedAt)) {
                            $notMySiteLastChangedAt = Carbon::parse($notMySiteItem->lastChangedAt);
                        }

                        $comparedDateTime = !is_null($this->lastActiveAt) ? $this->lastActiveAt : $this->alertCreatedAt;

                        /* either my site or not my site has changed price */
                        if ((!is_null($lastChangedAt) && $lastChangedAt > $comparedDateTime) || (!is_null($notMySiteLastChangedAt) && $notMySiteLastChangedAt > $comparedDateTime)) {
                            /* both my site and not my site have recent prices */
                            if (!is_null($mySiteItem->recentPrice) && !is_null($notMySiteItem->recentPrice)) {
                                if (floatval($notMySiteItem->recentPrice) < floatval($mySiteItem->recentPrice)) {

                                    $siteDomain = domain($notMySite->siteUrl);

                                    if (!is_null($notMySite->item) && !is_null($notMySite->item->sellerUsername)) {
                                        $notMySite->setAttribute('displayName', "eBay: {$notMySite->item->sellerUsername}");
                                    } elseif (array_has($userDomains, $siteDomain) && !is_null(array_get($userDomains, $siteDomain))) {
                                        $notMySite->setAttribute('displayName', array_get($userDomains, $siteDomain));
                                    } else {
                                        $notMySite->setAttribute('displayName', $notMySite->url->domainFullPath);
                                    }

                                    $beatenBySites->push($notMySite);
                                }
                            }
                        }
                    }
                }
            }
            if ($beatenBySites->count() > 0) {
                $alertProducts->push($product);
            }
        }

        if ($alertProducts->count() > 0) {
            $this->alert->setLastActiveAt();

            $email = $this->user->email;

            $this->historicalAlertRepo->store($this->alert, compact(['email']));

            /* TODO dispatch mail job with $alertProducts */
            Mail::to($email)
                ->send(new AdvancedCategoryMyPrice($this->user, $category, $alertProducts));
        }

        return true;
    }

    /**
     * validate advanced alert - category price change alert
     * @return bool
     */
    private function _processAdvancedCategoryPriceChange()
    {
        $category = $this->alert->alertable;

        $userDomains = $this->user->domains->pluck('alias', 'domain')->all();

        if (is_null($category)) {
            return false;
        }

        $sites = $category->sites;

        $alertSites = collect();
        foreach ($sites as $site) {
            if ($this->_siteHasPriceChange($site)) {
                $siteDomain = domain($site->siteUrl);

                if (!is_null($site->item) && !is_null($site->item->sellerUsername)) {
                    $site->setAttribute('displayName', "eBay: {$site->item->sellerUsername}");
                } elseif (array_has($userDomains, $siteDomain) && !is_null(array_get($userDomains, $siteDomain))) {
                    $site->setAttribute('displayName', array_get($userDomains, $siteDomain));
                } else {
                    $site->setAttribute('displayName', $site->url->domainFullPath);
                }
                $alertSites->push($site);
            }
        }
        if ($alertSites->count() > 0) {
            $this->alert->setLastActiveAt();

            $email = $this->user->email;

            $this->historicalAlertRepo->store($this->alert, compact(['email']));

            /* TODO dispatch mail job with $alertSites */
            Mail::to($email)
                ->send(new AdvancedCategoryPriceChange($this->user, $category, $alertSites));
        }

        return true;
    }

    /**
     * validate basic alert - my price beaten alert
     * @return bool
     */
    private function _processBasicMyPrice()
    {
        $products = $this->user->products()->with('sites.item.metas')->get();

        $userDomains = $this->user->domains->pluck('alias', 'domain')->all();
        $alertProducts = collect();
        foreach ($products as $product) {
            dump(round(microtime(true) * 1000));
            $sites = $product->sites;

            $mySites = $sites->filter(function ($site) {
                return $this->_isMySite($site);
            });
            if ($mySites->count() == 0) {
                continue;
            }

            $notMySites = $sites->diff($mySites);
            if ($notMySites->count() == 0) {
                continue;
            }

            dump(round(microtime(true) * 1000));

            $mySites = $mySites->each(function ($mySite) {
                if (!is_null($mySite->item) && !is_null($mySite->item->lastChangedAt)) {
                    $mySite->setAttribute('price_last_changed_at', Carbon::parse($mySite->item->lastChangedAt));
                } else {
                    $mySite->setAttribute('price_last_changed_at', null);
                }
            });

            $beatenBySites = $notMySites->filter(function ($notMySite) use ($mySites) {
                $notMySiteItem = $notMySite->item;
                if (is_null($notMySiteItem)) {
                    return false;
                }
                $notMySiteLastChangedAt = null;
                if (!is_null($notMySiteItem->lastChangedAt)) {
                    $notMySiteLastChangedAt = Carbon::parse($notMySiteItem->lastChangedAt);
                }

                $comparedDateTime = !is_null($this->lastActiveAt) ? $this->lastActiveAt : $this->alertCreatedAt;

                foreach ($mySites as $mySite) {
                    if ((!is_null($mySite->price_last_changed_at) && $mySite->price_last_changed_at > $comparedDateTime) || (!is_null($notMySiteLastChangedAt) && $notMySiteLastChangedAt > $comparedDateTime)) {
                        if (!is_null($mySite->item->recentPrice) && !is_null($notMySiteItem->recentPrice)) {
                            if (floatval($notMySiteItem->recentPrice) < floatval($mySite->item->recentPrice)) {
                                return true;
                            }
                        }
                    }
                }
                return false;
            });

            $beatenBySites = $beatenBySites->each(function ($beatenBySite) use ($userDomains) {
                $siteDomain = domain($beatenBySite->siteUrl);
                if (!is_null($beatenBySite->item) && !is_null($beatenBySite->item->sellerUsername)) {
                    $beatenBySite->setAttribute('displayName', "eBay: {$beatenBySite->item->sellerUsername}");
                } elseif (array_has($userDomains, $siteDomain) && !is_null(array_get($userDomains, $siteDomain))) {
                    $beatenBySite->setAttribute('displayName', array_get($userDomains, $siteDomain));
                } else {
                    $beatenBySite->setAttribute('displayName', $beatenBySite->url->domainFullPath);
                }
            });


//            foreach ($mySites as $mySite) {
//                $mySiteItem = $mySite->item;
//
//                if (is_null($mySiteItem)) {
//                    continue;
//                }
//                $lastChangedAt = null;
//                if (!is_null($mySiteItem->lastChangedAt)) {
//                    $lastChangedAt = Carbon::parse($mySiteItem->lastChangedAt);
//                }
//                foreach ($notMySites as $notMySite) {
//                    $notMySiteItem = $notMySite->item;
//
//                    if (is_null($notMySiteItem)) {
//                        continue;
//                    }
//
//                    $notMySiteLastChangedAt = null;
//                    if (!is_null($notMySiteItem->lastChangedAt)) {
//                        $notMySiteLastChangedAt = Carbon::parse($notMySiteItem->lastChangedAt);
//                    }
//
//                    $comparedDateTime = !is_null($this->lastActiveAt) ? $this->lastActiveAt : $this->alertCreatedAt;
//
//                    /* either my site or not my site has changed price */
//                    if ((!is_null($lastChangedAt) && $lastChangedAt > $comparedDateTime) || (!is_null($notMySiteLastChangedAt) && $notMySiteLastChangedAt > $comparedDateTime)) {
//                        /* both my site and not my site have recent prices */
//                        if (!is_null($mySiteItem->recentPrice) && !is_null($notMySiteItem->recentPrice)) {
//                            if (floatval($notMySiteItem->recentPrice) < floatval($mySiteItem->recentPrice)) {
//                                $beatenBySites->push($notMySite);
//                            }
//                        }
//                    }
//                }
//            }
            if ($beatenBySites->count() > 0) {
                $alertProducts->push($product);
            }
            dd(round(microtime(true) * 1000));
        }

        if ($alertProducts->count() > 0) {
            $this->alert->setLastActiveAt();

            $email = $this->user->email;

            $this->historicalAlertRepo->store($this->alert, compact(['email']));

            /* TODO dispatch mail job with $alertProducts */
            Mail::to($email)
                ->send(new BasicMyPrice($this->user, $alertProducts));
        }

        return true;
    }

    /**
     * validate basic alert - price change alert
     * @return bool
     */
    private function _processBasicPriceChange()
    {
        $sites = $this->user->sites()->with('item', 'item.metas');

        $userDomains = $this->user->domains->pluck('alias', 'domain')->all();

        $alertSites = collect();
        foreach ($sites as $site) {
            if ($this->_siteHasPriceChange($site)) {

                $siteDomain = domain($site->siteUrl);

                if (!is_null($site->item) && !is_null($site->item->sellerUsername)) {
                    $site->setAttribute('displayName', "eBay: {$site->item->sellerUsername}");
                } elseif (array_has($userDomains, $siteDomain) && !is_null(array_get($userDomains, $siteDomain))) {
                    $site->setAttribute('displayName', array_get($userDomains, $siteDomain));
                } else {
                    $site->setAttribute('displayName', $site->url->domainFullPath);
                }

                $alertSites->push($site);
            }
        }
        if ($alertSites->count() > 0) {
            $this->alert->setLastActiveAt();

            $email = $this->user->email;

            $this->historicalAlertRepo->store($this->alert, compact(['email']));

            /* TODO dispatch mail job with $alertSites */
            Mail::to($email)
                ->send(new BasicPriceChange($this->user, $alertSites));
        }

        return true;
    }

    /**
     * check if provided site is my site
     * @param Site $site
     * @return bool
     */
    private function _isMySite(Site $site)
    {
        $ebaySellerUsername = $this->user->metas->ebay_username;
        $companyUrl = $this->user->metas->company_url;

        /*ebay username checking*/
        if (!is_null($site->item) && !is_null($site->item->sellerUsername)) {
            if ($site->item->sellerUsername == $ebaySellerUsername) {
                return true;
            }
        }

        /*company url checking*/
        if (!is_null($companyUrl)) {
            $urlSegments = parse_url($companyUrl);
            $companyDomain = array_get($urlSegments, 'host', '');

            if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $companyDomain, $regs)) {
                $companyDomain = array_get($regs, 'domain');
            } else {
                return false;
            }

            $siteDomainSegments = parse_url($site->url->domainFullPath);
            $siteDomain = array_get($siteDomainSegments, 'host', '');

            if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $siteDomain, $regs)) {
                $siteDomain = array_get($regs, 'domain');
            } else {
                return false;
            }
            return $companyDomain == $siteDomain;
        }

        return false;
    }

    /**
     * check if provided site has recently changed price after alert last active/created
     * @param Site $site
     * @return bool
     */
    private function _siteHasPriceChange(Site $site)
    {
        $item = $site->item;
        if (!is_null($item)) {
            if (!is_null($item->lastChangedAt)) {
                $lastChangedAt = Carbon::parse($item->lastChangedAt);
                if (!is_null($this->lastActiveAt)) {
                    if ($lastChangedAt > $this->lastActiveAt) {
                        return true;
                    }
                } else {
                    if ($lastChangedAt > $this->alertCreatedAt) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function _validateSubscription(AlertModel $alert)
    {
        $user = $alert->user;
        if (!is_null($user->subscription)) {
            return $user->subscription->isValid;
        }
        return true;
    }
}
