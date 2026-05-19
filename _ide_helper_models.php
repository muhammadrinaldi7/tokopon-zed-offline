<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereUpdatedAt($value)
 */
	class Branch extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereUpdatedAt($value)
 */
	class Brand extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $brand_id
 * @property int|null $buyback_tier_id
 * @property string $model_name
 * @property string|null $ram
 * @property string $storage
 * @property numeric $base_price
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $second_product_variant_id
 * @property-read \App\Models\Brand $brand
 * @property-read \App\Models\SecondProductVariant|null $secondProductVariant
 * @property-read \App\Models\BuybackTier|null $tier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereBasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereBuybackTierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereRam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereSecondProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereStorage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackDevice whereUpdatedAt($value)
 */
	class BuybackDevice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property numeric|null $min_price
 * @property numeric|null $max_price
 * @property array<array-key, mixed>|null $rules
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BuybackDevice> $devices
 * @property-read int|null $devices_count
 * @property-read string $price_range_label
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackTier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackTier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackTier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackTier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackTier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackTier whereMaxPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackTier whereMinPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackTier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackTier whereRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuybackTier whereUpdatedAt($value)
 */
	class BuybackTier extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $total_price
 * @property-read int $total_qty
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CartItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cart query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cart whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cart whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cart whereUserId($value)
 */
	class Cart extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cart_id
 * @property int $product_variant_id
 * @property int $qty
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Cart $cart
 * @property-read float $subtotal
 * @property-read \App\Models\ProductVariant $productVariant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereCartId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereUpdatedAt($value)
 */
	class CartItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 */
	class Category extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Message|null $latestMessage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereUserId($value)
 */
	class Conversation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $conversation_id
 * @property-read \App\Models\Conversation $conversation
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereUserId($value)
 */
	class Message extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $order_number
 * @property numeric $total_amount
 * @property numeric $shipping_cost
 * @property numeric $discount_amount
 * @property numeric $grand_total
 * @property string $order_status
 * @property array<array-key, mixed> $shipping_address_snapshot
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\OrderShipping|null $shipping
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereShippingAddressSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereShippingCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUserId($value)
 */
	class Order extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $order_id
 * @property int $product_variant_id
 * @property int $qty
 * @property numeric $price_at_checkout
 * @property numeric $subtotal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\ProductReview|null $review
 * @property-read \App\Models\ProductVariant $variant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem wherePriceAtCheckout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereUpdatedAt($value)
 */
	class OrderItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $order_id
 * @property string $xendit_external_id
 * @property string|null $xendit_invoice_url
 * @property string|null $payment_method
 * @property numeric $amount
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property array<array-key, mixed>|null $payment_payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaymentPayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereXenditExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereXenditInvoiceUrl($value)
 */
	class OrderPayment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $order_id
 * @property string|null $biteship_order_id
 * @property string|null $courier_company
 * @property string|null $courier_type
 * @property string|null $tracking_number
 * @property numeric $shipping_cost
 * @property string $status
 * @property array<array-key, mixed>|null $shipping_payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereBiteshipOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereCourierCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereCourierType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereShippingCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereShippingPayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereTrackingNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderShipping whereUpdatedAt($value)
 */
	class OrderShipping extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $category_id
 * @property int|null $brand_id
 * @property string|null $description
 * @property array<array-key, mixed>|null $specifications
 * @property numeric|null $starting_price
 * @property int $total_stock
 * @property string|null $thumbnail_image
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $has_active_accurate
 * @property-read \App\Models\Brand|null $brand
 * @property-read \App\Models\Category $category
 * @property-read mixed $average_rating
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductReview> $reviews
 * @property-read int|null $reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductVariant> $variants
 * @property-read int|null $variants_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product availableForCustomer()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereHasActiveAccurate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSpecifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStartingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereThumbnailImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereTotalStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 */
	class Product extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $accurate_id
 * @property string $database_source
 * @property string|null $item_no
 * @property string|null $name
 * @property numeric $base_price
 * @property int $stock
 * @property array<array-key, mixed>|null $raw_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductVariant> $productVariants
 * @property-read int|null $product_variants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecondProductVariant> $secondProductVariants
 * @property-read int|null $second_product_variants_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate whereAccurateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate whereBasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate whereDatabaseSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate whereItemNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate whereRawData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAccurate whereUpdatedAt($value)
 */
	class ProductAccurate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductVariant> $variants
 * @property-read int|null $variants_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductErzap newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductErzap newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductErzap query()
 */
	class ProductErzap extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property int $order_item_id
 * @property int $rating
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OrderItem $orderItem
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview whereOrderItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReview whereUserId($value)
 */
	class ProductReview extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $product_id
 * @property string|null $sku
 * @property string $condition
 * @property string|null $ram
 * @property string|null $storage
 * @property string|null $color
 * @property numeric $price
 * @property int $stock
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $product_accurate_id
 * @property-read \App\Models\ProductAccurate|null $accurateData
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereProductAccurateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereRam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereStorage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereUpdatedAt($value)
 */
	class ProductVariant extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $category_id
 * @property int|null $brand_id
 * @property string|null $description
 * @property numeric|null $starting_price
 * @property int $total_stock
 * @property string|null $thumbnail_image
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $has_active_accurate
 * @property-read \App\Models\Brand|null $brand
 * @property-read \App\Models\Category|null $category
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecondProductVariant> $variants
 * @property-read int|null $variants_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct availableForCustomer()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereHasActiveAccurate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereStartingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereThumbnailImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereTotalStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProduct whereUpdatedAt($value)
 */
	class SecondProduct extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $second_product_id
 * @property int|null $sell_phone_id
 * @property int|null $product_accurate_id
 * @property string|null $sku
 * @property string|null $condition_desc
 * @property string|null $ram
 * @property string|null $storage
 * @property string|null $color
 * @property numeric $buy_price
 * @property numeric $price
 * @property int $stock
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductAccurate|null $accurateData
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\SecondProduct $secondProduct
 * @property-read \App\Models\SellPhone|null $sellPhone
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereBuyPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereConditionDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereProductAccurateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereRam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereSecondProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereSellPhoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereStorage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecondProductVariant whereUpdatedAt($value)
 */
	class SecondProductVariant extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $phone_brand
 * @property string $phone_model
 * @property string|null $phone_ram
 * @property string|null $phone_storage
 * @property string|null $minus_desc
 * @property numeric|null $appraised_value
 * @property string $status
 * @property string|null $customer_shipping_receipt
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property string|null $bank_account_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $buyback_device_id
 * @property int|null $handled_by
 * @property-read \App\Models\BuybackDevice|null $buybackDevice
 * @property-read \App\Models\User|null $handledBy
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductVariant> $productVariants
 * @property-read int|null $product_variants_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereAppraisedValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereBankAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereBankAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereBuybackDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereCustomerShippingReceipt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereHandledBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereMinusDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone wherePhoneBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone wherePhoneModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone wherePhoneRam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone wherePhoneStorage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SellPhone whereUserId($value)
 */
	class SellPhone extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
 */
	class Setting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $target_product_id
 * @property string $old_phone_brand
 * @property string $old_phone_model
 * @property string|null $old_phone_ram
 * @property string|null $old_phone_storage
 * @property string|null $old_phone_minus_desc
 * @property numeric|null $appraised_value
 * @property string $status
 * @property string|null $customer_shipping_receipt
 * @property int|null $order_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $buyback_device_id
 * @property-read \App\Models\BuybackDevice|null $buybackDevice
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\Order|null $order
 * @property-read \App\Models\Product $targetProduct
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TradeInUnitOption> $unitOptions
 * @property-read int|null $unit_options_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereAppraisedValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereBuybackDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereCustomerShippingReceipt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereOldPhoneBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereOldPhoneMinusDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereOldPhoneModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereOldPhoneRam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereOldPhoneStorage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereTargetProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeIn whereUserId($value)
 */
	class TradeIn extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $trade_in_id
 * @property int $product_variant_id
 * @property bool $is_selected
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TradeIn $tradeIn
 * @property-read \App\Models\ProductVariant $variant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeInUnitOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeInUnitOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeInUnitOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeInUnitOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeInUnitOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeInUnitOption whereIsSelected($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeInUnitOption whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeInUnitOption whereTradeInId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TradeInUnitOption whereUpdatedAt($value)
 */
	class TradeInUnitOption extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $provider
 * @property string|null $provider_id
 * @property string|null $avatar
 * @property string|null $identity
 * @property string|null $npwp
 * @property int|null $accurate_customer_id
 * @property int|null $accurate_vendor_id
 * @property string|null $accurate_customer_no
 * @property string|null $accurate_vendor_no
 * @property int|null $branch_id
 * @property int|null $warehouse_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserAddress> $addresses
 * @property-read int|null $addresses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserBankAccount> $bankAccounts
 * @property-read int|null $bank_accounts_count
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\Cart|null $cart
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 * @property-read int|null $conversations_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \App\Models\UserProfile|null $profile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SellPhone> $sellPhones
 * @property-read int|null $sell_phones_count
 * @property-read \App\Models\Warehouse|null $warehouse
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, ?string $guard = null, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccurateCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccurateCustomerNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccurateVendorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccurateVendorNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIdentity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNpwp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, ?string $guard = null)
 */
	class User extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $label_address
 * @property string $recipient_name
 * @property string $phone_number
 * @property string $full_address
 * @property string|null $province_id
 * @property string|null $city_id
 * @property string|null $district_id
 * @property string|null $postal_code
 * @property int $is_primary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereDistrictId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereLabelAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereProvinceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereRecipientName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAddress whereUserId($value)
 */
	class UserAddress extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $bank_name
 * @property string $account_number
 * @property string $account_name
 * @property int $is_primary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount whereAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBankAccount whereUserId($value)
 */
	class UserBankAccount extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property string|null $phone_number
 * @property string|null $birth_date
 * @property string|null $gender
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereUserId($value)
 */
	class UserProfile extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warehouse_id
 * @property string $name
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereWarehouseId($value)
 */
	class Warehouse extends \Eloquent {}
}

