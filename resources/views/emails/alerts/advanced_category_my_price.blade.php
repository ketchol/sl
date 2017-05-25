@component('mail::alert_message')

# Hi {{$user->first_name}},

The price for the category *{{$category->category_name}}* are found to have beaten your prices.


@if(isset($products))
|Category   |Product    |
|-----------|-----------|
@foreach($products as $product)
|{{$product->category->category_name}}|{{$product->product_name}}
@endforeach
@endif

You can also view this information through your Products page:

@component('mail::button', ['url' => route('product.index')])
VIEW MY PRODUCTS
@endcomponent


@endcomponent