<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 2/2/2017
 * Time: 12:19 PM
 */

namespace App\Repositories\Subscription;


use App\Contracts\Repositories\Subscription\ProductContract;
use App\Contracts\Repositories\Subscription\ProductFamilyContract;
use App\Contracts\Repositories\Subscription\SubscriptionContract;
use App\Exceptions\Subscription\ProductNotFoundException;
use App\Exceptions\Subscription\ProductSignUpPageNotFoundException;
use App\Exceptions\Subscription\SubscriptionNotFoundException;
use App\Models\User;
use IvanCLI\Chargify\Chargify;

class ProductRepository implements ProductContract
{
    var $productFamilyRepo;
    var $subscriptionRepo;

    public function __construct(ProductFamilyContract $productFamilyContract, SubscriptionContract $subscriptionContract)
    {
        $this->productFamilyRepo = $productFamilyContract;
        $this->subscriptionRepo = $subscriptionContract;
    }

    /**
     * Load all product within a product family
     *
     * @param $product_family_id
     * @param bool $throw
     * @return mixed
     * @throws ProductNotFoundException
     */
    public function getProductsByProductFamilyID($product_family_id, $throw = false)
    {
        $products = Chargify::product()->allByProductFamily($product_family_id);
        if ($throw && empty($products)) {
            throw new ProductNotFoundException();
        }
        return $products;
    }

    /**
     * Load product by product ID
     *
     * @param $product_id
     * @param bool $throw
     * @return mixed
     * @throws ProductNotFoundException
     */
    public function getProductByProductId($product_id, $throw = false)
    {
        $product = Chargify::product()->get($product_id);
        if ($throw && is_null($product)) {
            throw new ProductNotFoundException();
        }
        return $product;
    }

    /**
     * Retrieve sign up page link of a product
     *
     * @param $product_id
     * @param User $user
     * @param string $coupon_code
     * @return mixed
     * @throws ProductSignUpPageNotFoundException
     * @throws SubscriptionNotFoundException
     */
    public function generateSignUpPageLink($product_id, User $user, $coupon_code = '')
    {
        $product = $this->getProductByProductId($product_id);
        $signUpPage = count($product->public_signup_pages) > 0 ? array_first($product->public_signup_pages)->url : null;
        if (is_null($signUpPage)) {
            throw new ProductSignUpPageNotFoundException();
        }

        $userSubscription = $user->subscription;
        if (is_null($userSubscription)) {
            throw new SubscriptionNotFoundException();
        }

        $userSubscription->setToken($this->subscriptionRepo->generateToken());

        $reference = array(
            'user_id' => $user->getKey(),
            'verification_code' => $userSubscription->token
        );

        $encryptedReference = rawurlencode(json_encode($reference));
        return $signUpPage . "?reference=$encryptedReference&first_name={$user->first_name}&last_name={$user->last_name}&email={$user->email}&coupon_code={$coupon_code}";
    }
}