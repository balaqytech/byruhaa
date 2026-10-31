# دليل تكامل UChat — Events وCustomers وStore

تحديث: 29 أكتوبر 2026. هذا المرجع يصف عقد الكود الجديد. نشر staging والتحقق الحي منه خطوة مستقلة.

## 1. بدء الربط

Base path: `/api/v1/integrations/uchat`. استخدموا host المعتمد لبيئة التشغيل.

```http
Authorization: Bearer <UCHAT_API_TOKEN>
X-WhatsApp-Phone: +96891234567
Accept: application/json
Content-Type: application/json
Accept-Language: ar
```

رقم `X-WhatsApp-Phone` هو هوية صاحب المحادثة الموثوقة من UChat. يطبّعه التطبيق عبر `CustomerIdentityResolver`؛ الرقم المحلي `91234567` يصبح `+96891234567`. لا ترسلوا `customer_id` أو`phone_number` لاختيار حساب آخر. الـHeader لا يغيّر حالة `phone_verified_at` ولا يُعد OTP verification.

يتيح Store طلبًا كـGuest إن لم يوجد Customer بهذا الرقم. تحتاج Account وFamily Members وBookings وEvent Interests وWallet إلى حساب؛ غيابه يعيد `409 customer_account_required`. يبدأ إنشاء الحساب من `POST /account` بالرقم نفسه.

المبالغ في UChat Responses وWebhooks أعداد صحيحة بوحدة بيسة، مع `currency`: المبلغ `15000` يعادل `15.000 OMR`. التواريخ الكاملة ISO 8601، و`birth_date` و`due_date` بصيغة `YYYY-MM-DD`.

نجاح القراءة والتعديل يعيد `200`، والإنشاء `201`، وحذف Family Member المسموح `204`، وطلب مراجعة حذف الحساب `202`. القوائم تستخدم `data` وpagination metadata؛ اقرأوا `HTTP status` قبل الوصول إلى `data`.

## 2. خريطة المسارات

كل المسارات في الجدول نسبية إلى Base path أعلاه.

| Method | Path | الغرض |
| --- | --- | --- |
| GET / POST / PATCH | `/account` | عرض حساب صاحب الهاتف، إنشاؤه، أوتعديل Profile |
| GET / POST | `/account/deletion-requests` | عرض طلبات الحذف أوتقديم طلب مراجعة |
| GET / POST | `/family-members` | قائمة Family Members وإضافة عضو |
| GET / PATCH / DELETE | `/family-members/{id}` | عرض عضو تابع للحساب أوتعديله أوحذفه |
| GET | `/events` | Events المنشورة؛ يدعم `enrollment_status` و`per_page` |
| GET | `/events/{slug}` | تفاصيل Event وخطط الدفع والأسعار |
| GET / PUT / DELETE | `/event-interests` | قراءة Event Interest أوتسجيله أوالانسحاب منه |
| GET / POST | `/bookings` | قائمة Bookings الخاصة بالحساب أوإنشاء Booking |
| GET | `/bookings/{reference}` | تفاصيل Booking المملوك للحساب |
| POST | `/bookings/{reference}/payments` | بدء Checkout لـBooking أوInstallment |
| GET | `/payments` و`/payments/{reference}` | قراءة Booking Payments فقط |
| GET / POST | `/minor-profiles` | قائمة حسابات القُصّر أوفتح حساب لعضو تابع للحساب |
| POST | `/minor-profiles/{id}/activation-link` | إصدار Activation link |
| POST | `/minor-profiles/{id}/avatar` | صورة عبر `avatar_url` أو`avatar_base64` أوmultipart `avatar` |
| POST | `/minor-profiles/{id}/verification/send` و`/verification/verify` | عمليات Verification الحالية عند الحاجة |
| GET | `/minor-profiles/{id}/wallet/status` | حالة Wallet وصلاحية الإنفاق |
| POST | `/minor-profiles/{id}/wallet-spending/enable` و`/disable` | تمكين الإنفاق أوإيقافه |
| POST | `/minor-profiles/{id}/wallet/activate` و`/suspend` | تفعيل Wallet أوتعليقها |
| GET | `/wallet` و`/wallet/movements` | رصيد Wallet وحركاتها؛ أرسلوا `minor_profile_id` |
| POST | `/wallet/top-ups` | بدء شحن Wallet مع `Idempotency-Key` |
| GET | `/wallet/top-ups/{reference}` | حالة Top-up؛ أرسلوا `minor_profile_id` |
| GET | `/store/catalog` و`/store/products/{slug}` | Catalog وProduct المنشوران |
| GET | `/store/cart` | Cart صاحب الهاتف وserver quote |
| POST | `/store/cart/items` | إضافة `sku` و`quantity` و`note` الاختياري |
| PATCH / DELETE | `/store/cart/items/{sku}` | تعديل عنصر أوحذفه |
| POST | `/store/orders` | إنشاء Order؛ يحتاج `Idempotency-Key` |
| GET | `/store/orders` و`/store/orders/{reference}` | Order history والتفاصيل الخاصة بصاحب الهاتف |
| POST | `/store/orders/{reference}/payment` | بدء أوربط Checkout صالح للـOrder |

أي ID أوreference تابع لعميل آخر يعيد `404`. `minor_profile_id` يحدد حسابًا تابعًا لصاحب الهاتف، ولا يتجاوز التحقق من الملكية.

## 3. Account وFamily Members

### إنشاء Account

```http
POST /account
```

```json
{
  "name": "Mona Said",
  "email": "mona@example.com",
  "civil_id": "12345678",
  "address": "House 12",
  "wilaya": "Muscat",
  "area": "Qurum",
  "password": "<customer-chosen-password>",
  "password_confirmation": "<customer-chosen-password>"
}
```

`name` وpassword مطلوبان. Email وحقول Profile الإضافية اختيارية عند الإنشاء، لكن إتمام Profile مطلوب قبل الحجز وبعض العمليات. الـResponse لا يحتوي password.

```json
{
  "data": {
    "id": 101,
    "name": "Mona Said",
    "phone_number": "+96891234567",
    "email": "mona@example.com",
    "profile_complete": true,
    "missing_required_profile_fields": [],
    "phone_verified_at": null
  }
}
```

هذا مقتطف صالح من Response؛ تعاد أيضًا حقول Profile وتواريخ السجل. `GET /account` يضيف capabilities الحالية لحسابات القُصّر وWallet. استخدموا `PATCH /account` لإرسال الحقول المراد تعديلها فقط. تغيير الهاتف وpassword يتم عبر الموقع؛ حقول Verification لا تُكتب من UChat. الحساب الموجود عند الإنشاء يعيد `409 customer_already_exists`.

### إضافة Family Member

```json
{
  "name": "Salim Said",
  "birth_date": "2014-01-01",
  "school_name": "Muscat School",
  "grade": "6",
  "relationship_to_customer": "Son"
}
```

أرسلوا هذا الـpayload إلى `POST /family-members`. يعيد Response العضو تحت `data`، بما فيه `id` و`customer_id` وحالة Minor Profile عند وجوده. حذف عضو له Minor Profile يعيد `409 minor_account_lifecycle_required` حتى تُعالج دورة حياته.

### طلب مراجعة حذف الحساب

```http
POST /account/deletion-requests
```

```json
{
  "reason": "يرجى مراجعة طلب حذف حسابي."
}
```

```json
{
  "data": {
    "id": 12,
    "status": "pending",
    "reason": "يرجى مراجعة طلب حذف حسابي.",
    "requested_at": "2026-10-29T12:00:00.000000Z",
    "reviewed_at": null
  }
}
```

النتيجة `202`. تكرار الطلب مع وجود `pending` يعيد الطلب نفسه. الحالات اللاحقة `rejected` أو`closed`؛ الأخيرة تعني إغلاق المتابعة اليدوية، ولا تؤكد حذف البيانات. يبقى الحساب فعالًا أثناء المتابعة.

## 4. Events وEvent Interests

اقرأوا `GET /events?enrollment_status=booking_open&per_page=15`، ثم `GET /events/{slug}`. القيم الممكنة لـ`enrollment_status`: `coming_soon` و`interest_open` و`booking_open` و`booking_closed`.

```json
{
  "data": {
    "id": 42,
    "slug": "winter-camp",
    "name": "Winter Camp",
    "status": "published",
    "enrollment_status": "booking_open",
    "can_book": true,
    "can_express_interest": false,
    "remaining_seats": 12,
    "price_baisa": 15000,
    "currency": "OMR"
  }
}
```

التفاصيل تشمل `price_tiers` و`available_discounts` و`payment_plans` الفعّالة. تحققوا من `can_book` أو`can_express_interest`؛ لا تعتمدوا على السعر الظاهر وحده لحساب Checkout.

لتسجيل الاهتمام، أرسلوا موافقة التواصل الصريحة إلى `PUT /event-interests`:

```json
{
  "event_slug": "winter-camp",
  "contact_consent": true,
  "preferred_contact_channel": "whatsapp",
  "source_reference": "uchat-conversation-123"
}
```

الإنشاء يعيد `201`، والتكرار لنفس Customer وEvent يعيد `200` ونفس Interest. اقرأوه أوانسحبوا منه باستخدام `GET /event-interests?event_slug=winter-camp` أو`DELETE` بالـquery نفسه. لا ترسلوا phone أوemail في payload؛ الهوية من Header.

## 5. Bookings وPayments

### إنشاء Booking آمن من تكرار الطلب

```http
POST /bookings
Idempotency-Key: uchat-booking-conversation-123-attempt-1
```

```json
{
  "event_id": 42,
  "family_member_ids": [201, 202],
  "coupon_code": "CAMP10"
}
```

```json
{
  "data": {
    "id": 301,
    "reference": "BRH-BKG-EXAMPLE",
    "event_id": 42,
    "state": "pending_review",
    "family_member_count": 2,
    "unit_price_baisa": 15000,
    "subtotal_baisa": 30000,
    "discount_amount_baisa": 3000,
    "total_baisa": 27000,
    "currency": "OMR",
    "cancellation": null
  }
}
```

احتفظوا بالمفتاح نفسه عند timeout أوretry لنفس العملية. تكراره مع البيانات نفسها يعيد `201` ونفس Booking بحالته الحالية؛ لا يحجز مقاعد إضافية ولا يستهلك Coupon جديدًا. اختلاف Customer أوالفعالية أوالمشاركين أوCoupon بنفس المفتاح يعيد `409 idempotency_conflict`. ترتيب `family_member_ids` لا يغيّر هوية العملية. استخدموا مفتاحًا جديدًا لعملية جديدة فقط.

قائمة `GET /bookings` تدعم `event_id` و`updated_since` و`page` و`per_page` حتى 100. استخدموا `reference` في `GET /bookings/{reference}`. يوضح `cancellation`، عند وجوده، `id` و`status` و`reason` لإلغاء الفعالية المرتبطة.

### بدء Payment

```http
POST /bookings/BRH-BKG-EXAMPLE/payments
```

```json
{
  "payment_plan_id": 9
}
```

يختار التطبيق Full payment عند غياب Plan. بعد إنشاء Schedule يمكن تحديد `booking_installment_id` بدل اختيار Plan من جديد. يجب أن يتبع Installment الحجز نفسه، وأن يُدفع بالترتيب المعتمد، وبعد استيفاء شروط Booking وContracts.

```json
{
  "data": {
    "reference": "BRH-PAY-EXAMPLE",
    "amount_baisa": 13500,
    "currency": "OMR",
    "state": "pending",
    "checkout_url": "https://<payment-provider>/<checkout>"
  }
}
```

إنشاء Checkout ليس إثباتًا للدفع. انتظروا `payment.paid` أوتحققوا من `GET /payments/{reference}`. توجد `GET /payments` لقائمة Booking Payments الخاصة بالحساب. كتابة `state=paid` أوإنشاء Payment يدوي أوحذفه عبر UChat غير متاحة؛ تعيد الطرق غير المسجلة `405`.

## 6. Store وMinor Profiles وWallet

### Store order

أضيفوا أولًا عنصرًا عبر `POST /store/cart/items`:

```json
{
  "sku": "COFFEE-250",
  "quantity": 2,
  "note": "No sugar"
}
```

ثم `POST /store/orders` مع `Idempotency-Key`:

```json
{
  "customer_name": "Mona Said",
  "pickup_type": "immediate",
  "payment_method": "thawani"
}
```

يعيد Response `data.reference` و`data.status` وitem snapshots و`total_baisa` و`pricing_tier` و`payment.checkout_url` عند وجود Checkout. الأسعار والخصومات وVAT والمخزون تُحسب على الخادم. تكرار المفتاح لا ينشئ Order آخر. للطلب من Wallet أرسلوا `minor_profile_id` المملوك للحساب و`payment_method: "wallet"`، واستخدموا Cart الحساب الفرعي نفسه.

### Minor Profile

```json
{
  "family_member_id": 201,
  "consent_accepted": true,
  "notifications_consent_accepted": true
}
```

أرسلوا إلى `POST /minor-profiles` بعد عرض نصوص الموافقات التي يعيدها `GET /account`. يعيد Response بيانات الحساب و`activation_url` و`expires_at` عند إصدار رابط جديد. بيانات الموافقات الإضافية المطلوبة في الإصدار الحالي موثقة في Postman collection. يمكن إضافة مصدر صورة واحد: `avatar_url` أو`avatar_base64` أوmultipart `avatar`.

### Wallet

اقرأوا `GET /wallet?minor_profile_id=7` للحصول على الرصيد والـavailable/reserved balances. استخدموا `/wallet/movements?minor_profile_id=7&page=1&per_page=20` للسجل، و`GET /wallet/top-ups/{reference}?minor_profile_id=7` لحالة الشحن.

طلب الشحن إلى `POST /wallet/top-ups` يحتاج `Idempotency-Key` ويستقبل `amount_omr` كـdecimal string بدقة حتى 3 منازل، بين `0.100` و`100000`، و`minor_profile_id`:

```json
{
  "minor_profile_id": 7,
  "amount_omr": "5.000"
}
```

يعيد `top_up_reference` و`payment_reference` و`amount_baisa` و`status` و`checkout_url` و`expires_at`. لا يُعد الرصيد مضافًا قبل اكتمال عملية الدفع والترحيل إلى Wallet.

## 7. Webhooks الموحدة

جميع الإشعارات تذهب إلى `UCHAT_WEBHOOK_URL` باستخدام `Authorization: Bearer <UCHAT_WEBHOOK_BEARER_TOKEN>` و`Signature`، وهي مستقلة عن inbound API token. أي تهيئة جزئية تعطل الإرسال وتسجل تحذيرًا؛ staging وproduction يتطلبان HTTPS.

```json
{
  "schema_version": 1,
  "delivery_id": 123,
  "event": "booking.cancelled",
  "occurred_at": "2026-10-29T12:00:00.000000Z",
  "customer_phone": "+96891234567",
  "data": {
    "booking": {
      "id": 301,
      "reference": "BRH-BKG-EXAMPLE",
      "status": "cancelled",
      "reason": "إلغاء الفعالية",
      "cancellation_id": 17
    }
  }
}
```

المثال مقتطف صالح من الـsnapshot؛ تحمل Webhooks أيضًا بيانات Event وCustomer وParticipants وPricing بحسب نوعها. المبالغ في `data.pricing` و`data.payment` و`data.refund` تستخدم `_baisa` كأعداد صحيحة.

| Event | بيانات `data` الأساسية |
| --- | --- |
| `customer.registered` | `customer` وبيانات Profile، دون password |
| `interest.created` | `interest` و`event` و`customer` |
| `booking.created` / `booking.approved` | `booking` و`event` و`customer` و`participants` و`pricing` |
| `booking.cancelled` | Booking snapshot وسبب الإلغاء و`cancellation_id` عند إلغاء Event |
| `booking.contracts_signed` | Booking snapshot و`contracts` summary |
| `payment.paid` | `payment` وBooking payment status وInstallments عند الحاجة |
| `payment.refunded` | `refund` و`payment` وBooking أوWallet Top-up المرتبط |
| `event.cancelled` | `cancellation` و`event`؛ `customer_phone` يساوي `null` |
| `event.cancellation.completed` | نتيجة إكمال معالجة إلغاء Event ومجاميع Refund |
| `event.cancellation.needs_attention` | معالجة إلغاء تحتاج متابعة الإدارة |
| `store.order.{status}` | `order` وitems و`links`؛ بما فيها confirmed وaccepted وpreparing وready_for_pickup وcompleted وcancelled وexpired وrejected وrefund_pending وrefunded |
| `wallet.top_up` / `wallet.purchase` | Wallet وMovement ومراجع العملية |
| `wallet.withdrawal.{status}` | بيانات Withdrawal ومراجعها وحالتها |
| `store.refund.{status}` | بيانات طلب Refund الخاص بالـStore |
| `customer.phone.verification_code` / `minor.guardian.verification_code` | OTP و`expires_at`؛ بيانات حساسة لا تُسجل في automation logs |

إلغاء Event يبدأ بـ`event.cancelled` ثم معالجة غير متزامنة لكل Booking فعّال. يصل `booking.cancelled` لكل Booking تغيّرت حالته فعليًا، ومعه رقم العميل. لا توجد قائمة هواتف مجمعة في `event.cancelled`. قد تصل الإشعارات بترتيب مختلف؛ آخر حالة يمكن قراءتها من API.

تحققوا من `Signature` باستخدام HMAC SHA-256 للـJSON raw body والـsigning secret. خوارزمية الإرسال هي Spatie DefaultSigner؛ لا تعيدوا بناء body قبل التحقق. احتفظوا بـ`delivery_id` كمفتاح deduplication. قد تعاد الرسالة بالمفتاح نفسه عند retry؛ أعيدوا `2xx` بعد قبولها بشكل دائم، ونفذوا معالجة المساعد عبر Queue داخلي.

الإرسال `at least once`، وليس ضمانًا لوصول رسالة شبكية واحدة فقط. تُعاد المحاولة لـconnection failures و`429` و`5xx`. لا تعاد أخطاء HTTP الدائمة تلقائيًا. تتوفر أوامر الإدارة:

```bash
php artisan uchat:webhooks:dispatch-pending
php artisan uchat:webhooks:retry 123 124
```

استعادة `pending` مجدولة كل دقيقة. تبقى `delivery_id` والـsnapshot ثابتتين في retry. لا تعيد أداة الاستعادة إرسال OTP منتهية الصلاحية.

## 8. الأخطاء والانتقال

```json
{
  "code": "validation_failed",
  "message": "The request data is invalid.",
  "errors": {
    "family_member_ids": ["The selected family member is invalid."]
  }
}
```

| Status | Code / المعنى |
| --- | --- |
| 401 | `uchat_unauthorized`؛ Bearer مفقود أوغير صحيح |
| 503 | `uchat_not_configured` أو`payment_unavailable` |
| 422 | `validation_failed`؛ افحصوا `errors` و`reasons` عند وجودها |
| 409 | `idempotency_conflict` أو`customer_account_required` أو`customer_already_exists` أو`minor_account_lifecycle_required` |
| 404 | السجل غير موجود أوغير مملوك لصاحب الهاتف |
| 429 | تجاوز Rate limit؛ افحصوا `Retry-After` |

تم إلغاء مسارات `/api/v1/customers/**` و`/api/v1/integrations/assistant/**`. عمليات Account وMinor Profiles وWallet لم تعد تحت `/uchat/store`. لا توجد aliases أوredirects للمسارات القديمة. تبقى `/api/v1/events` العامة و`/api/webhooks/thawani` مستقلة؛ يستخدم UChat مسارات Events الموجودة داخل المدخل الموحد.

عند النشر يجب تحديث workflow URLs وHeaders وMoney parsers وWebhook receiver معًا. يحتفظ مسؤول التطبيق بقيمة API token وStore owner secret الحالية عند نقل أسماء الإعدادات. لا تتعاملوا مع هذا المستند كإثبات أن staging نُشر أوأن credentials فُحصت عليه.
