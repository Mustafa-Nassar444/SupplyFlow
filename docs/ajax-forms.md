# إرسال الفورمات المشترك عبر AJAX

تاريخ الإضافة: 2026-10-25. يستخدم النظام Fetch وFormData دون مكتبة جديدة. الملف `public/js/ajax-forms.js` محمّل مرة واحدة من `partials/dashboard/scripts.blade.php` بعد تمرير `lang/ar/ajax.php` أو `lang/en/ajax.php` باستخدام `Js::from`. يمكن تحميله مرة أخرى بالخطأ دون تسجيل submit listener جديد، لكن لا تضف تحميلًا في الصفحات.

## التفعيل

```blade
<form class="js-ajax-form" action="{{ route('example.store') }}" method="POST"
      data-success-action="none">
    @csrf
    <label for="name">{{ __('ui.name') }}</label>
    <input id="name" name="name" required>
    <button type="submit" name="intent" value="save">{{ __('ajax.submit') }}</button>
</form>
```

المسار `example.store` توضيحي؛ استخدم named route موجودًا في الموديول المصرح بتنفيذه. يكفي إضافة الكلاس إلى create/edit؛ لا ملف JavaScript لكل صفحة. فورمات بلا الكلاس تبقى طبيعية. الاستماع delegated على document يستوعب الفورمات المضافة لاحقًا داخل Modal. لا inline onclick/onsubmit ولا form.submit؛ استخدم requestSubmit عند الإرسال برمجيًا للحفاظ على التحقق الطبيعي. يمكن إلغاء التنفيذ من before-submit.

مصدر الطلب هو action وmethod؛ البيانات FormData مع اسم وقيمة submitter المضغوط، والحقول/الأزرار disabled مستبعدة. method/action على الزر (`formmethod/formaction`) ليست إعدادات مدعومة؛ الإعداد من الفورم نفسه. يحتاج المتصفح Fetch وFormData وSubmitEvent.submitter وCustomEvent. يمنع إرسال البيانات خارج Origin التطبيق.

للتعديل ورفع الملفات استخدم **POST + method spoofing**، ولا تضبط Content-Type يدويًا:

```blade
<form class="js-ajax-form" action="{{ route('dashboard.examples.update') }}"
      method="POST" enctype="multipart/form-data" data-success-action="reset">
    @csrf
    @method('PATCH') {{-- أو PUT --}}
    <input name="name" value="Example" required>
    <input name="items[0][quantity]" type="number" value="2">
    <div data-ajax-error-for="items.0.quantity"></div>
    <input name="attachment" type="file">
    <button type="submit">{{ __('ajax.submit') }}</button>
</form>
```

يُرسل POST كما هو مع `_method` حتى يستطيع PHP/Laravel قراءة multipart والملفات. Cookies بـ same-origin؛ حقول @csrf محفوظة وmeta csrf-token يرسل كـ X-CSRF-TOKEN عند وجوده. يضاف Accept: application/json وX-Requested-With: XMLHttpRequest. التحقق والصلاحيات وCSRF تبقى مسؤولية السيرفر.

## الإعدادات المدعومة

| المكان | الإعداد | المعنى |
| --- | --- | --- |
| form | `class="js-ajax-form"` | تفعيل النظام |
| form | `data-success-action="none"` | الافتراضي: رسالة فقط والإبقاء على القيم |
| form | `data-success-action="reset"` | form.reset بعد النجاح، وتفريغ الملفات إلى الحالة الأصلية وإطلاق reset hook |
| form | `data-success-action="reload"` | إعادة تحميل الصفحة بعد النجاح |
| form | `data-success-action="redirect"` | الانتقال إلى redirect_url من نفس Origin فقط؛ غيابه أو رفضه يعني رسالة نجاح دون انتقال |
| داخل form | `data-ajax-messages` | حاوية اختيارية للتنبيهات/ملخص الأخطاء؛ الافتراضي بداية الفورم |
| داخل form | `data-ajax-error-for="items.0.quantity"` | مكان صريح لرسالة الحقل أو المجموعة، يقبل صيغة الأقواس أيضًا |
| الحقل | `data-ajax-error-target="container-id"` | ID حاوية داخل الفورم لرسالة عنصر مركب |
| الحقل الأصلي | `data-ajax-focus="visible-control-id"` | ID واجهة بديلة قابلة للتركيز داخل الفورم للحقول المخفية/المحسنة |

لا يحتوي المشروع حاليًا Select2 أو Tom Select؛ لا نثبتهما ضمن المهمة. إذا أضيفت إضافة لاحقًا، عيّن proxy صريحًا إلى input/button قابل للتركيز وحدثها عبر reset hook. مثال:

```html
<input type="hidden" name="customer_id" data-ajax-focus="customer-search"
       data-ajax-error-target="customer-error">
<input id="customer-search" aria-describedby="customer-help">
<span id="customer-help">...</span>
<div id="customer-error"></div>
```

تُطابق أسماء الحقول بمقارنة السلاسل بعد تحويل bracket notation إلى dots، دون إدخال الاسم في CSS selector. `tags[]` يدعم خطأ tags وأخطاء tags.0 وغيرها. للمجموعات checkbox/radio حاوية مشتركة مثل `data-ajax-error-for="tags"`؛ تظهر رسالة واحدة للمجموعة. الأخطاء التي لا تقابل حقلًا ظاهرًا أو proxy تظهر في ملخص داخل نفس الفورم. تنقل البؤرة إلى حقل ظاهر قابل للتركيز، أو الملخص عند الحاجة.

يضيف النظام is-invalid وaria-invalid وروابط aria-describedby دون حذف الروابط السابقة؛ عند الإرسال التالي يزيل فقط feedback الذي أنشأه وروابطه ويعيد الحالات السابقة. لا يحذف @error أو رسائل Blade الثابتة. لذلك يُفضل للفورم AJAX الاعتماد على feedback الديناميكي، وإبقاء @error فقط إذا كان fallback HTML مطلوبًا. النصوص من السيرفر تضاف بـ textContent؛ لا يُنفذ HTML.

## اتفاق السيرفر

نجاح 200/201 وأي 2xx مع JSON object يحتوي `message` نصية غير فارغة، و`redirect_url` اختيارية:

```json
{"message":"Saved successfully.","redirect_url":"/dashboard/examples/form"}
```

يُقبل 204 كنجاح بالرسالة العامة المترجمة دون قراءة body. أي 2xx أخرى بلا الاتفاق، أو HTML، أو JSON غير صالح، أو HTTP redirect متبوع تلقائيًا (مثل login) تعالج كاستجابة غير متوقعة، ولا تصفر الفورم. لا تعتمد Controllers على redirect HTML لإبلاغ AJAX بنجاح الحفظ.

مثال Controller بعد عملية الحفظ المصرح بها:

```php
if ($request->expectsJson()) {
    return response()->json([
        'message' => __('ajax.success'),
        'redirect_url' => route('dashboard.examples.form'),
    ]);
}
return to_route('dashboard.examples.form')->with('success', __('ajax.success'));
```

DashboardExampleController الحالي مثال **تحقق فقط**؛ لا ينشئ سجلات أو يخزن ملفات. Form Requests تعيد 422 القياسية `errors` عند طلب JSON. لا تُرجع رسائل استثناءات خام في message؛ حتى النص الآمن من XSS قد يحتوي معلومات غير ملائمة للنشر.

401/403/419/429 لها رسائل مترجمة مخصصة، وبقية أخطاء HTTP رسالة عامة، وفشل الشبكة رسالة اتصال. لا تعرض HTML أو stack traces أو رسائل السيرفر الخام في هذه الحالات، ولا تعيد الحفظ تلقائيًا. تعاد الأزرار لحالاتها ومحتواها الأصليين في finally، بما فيها زر كان disabled من البداية؛ القفل منفصل لكل فورم.

## الأحداث والتخصيص

كل الأحداث bubbles: true وdetail.form هو الفورم. القائمة:

- `ajax-form:before-submit`: قابل للإلغاء؛ detail.formData وsubmitter. يمكن إضافة بيانات إلى FormData أو preventDefault لمنع الإرسال.
- `ajax-form:success`: response وdata المفككة وsubmitter، بعد عرض النجاح/reset وقبل الانتقال.
- `ajax-form:validation-error`: response وdata وerrors وsubmitter.
- `ajax-form:error`: response إن وجدت، data وkind (`http` أو `protocol` أو `network`) وsubmitter. لا إعادة محاولة تلقائية.
- `ajax-form:complete`: بعد استعادة الأزرار وفك القفل؛ response وdata وoutcome (`success` أو `validation-error` أو `error` أو `cancelled`).
- `ajax-form:reset`: hook إضافي بعد form.reset لتحديث المكونات المحسنة. native reset يعيد default values وليس دائمًا قيمًا فارغة.

مثال في سكربت الموديول عند الحاجة (ليس داخل الملف المشترك):

```js
document.addEventListener('ajax-form:success', (event) => {
    if (!event.target.matches('#customer-form')) return;
    const modal = event.target.closest('.modal');
    if (modal) window.tabler.Modal.getOrCreateInstance(modal).hide();
    document.dispatchEvent(new CustomEvent('customers:refresh'));
});
document.addEventListener('ajax-form:reset', (event) => {
    // استدعِ API الإضافة المعتمدة لتحديث الواجهة بعد إعادة القيم الأصلية.
});
```

استخدم اسم namespace الفعلي الذي تصدره الحزمة عند استدعاء Modal؛ حزمة Tabler المحلية UMD تصدر `tabler.Modal`. لا eval أو function names نصية في data attributes. تحديث الجدول يخص الموديول وليس النظام المشترك.

لـ reload/redirect تحفظ رسالة النجاح مرة واحدة في sessionStorage لمدة دقيقة، مرتبطة بمسار وquery الوجهة، وتعرض في `[data-ajax-flash]` بالقالب الجديد بعد الانتقال؛ تبقى رسائل Laravel Session الحالية كما هي. لو الوجهة تستخدم layout آخر بلا الحاوية/السكربت أو منع المتصفح sessionStorage، لا يُضمن عرض الرسالة هناك؛ استخدم server flash ورسالة Blade في تلك الوجهة. لا يُخزن payload الفورم أو الملفات في المتصفح لهذه الآلية.

## الأمثلة والاختبارات والحدود

صفحة `/dashboard/examples/form` تعرض Create باستخدام الكلاس المشترك مع fallback redirect عند عدم طلب JSON، ومثال Edit بـ PATCH، وكمية متداخلة تبدأ بـ 0 لإظهار 422 ثم تقبل 1–100، وملف اختياري حتى 1 MB. لا بيانات محفوظة. ملف dashboard-example.js القديم غير محمل؛ الإرسال تديره الآلية المشتركة فقط.

الفحوصات:

```powershell
php artisan test
node --check public/js/ajax-forms.js
```

اختبار متصفح فعلي في `scripts/test-ajax-forms.mjs` يحتاج تثبيت Playwright **متوفرًا بالفعل** وChrome، وخادم معاينة منفصل، ولا يثبت Dependencies. `PLAYWRIGHT_MODULE_DIR` مسار node_modules الذي يحتوي playwright؛ `AJAX_TEST_URL` افتراضيًا http://127.0.0.1:8015. استخدم فقط نسخة المشروع المحلية ببيانات عرض. شغّل:

```powershell
node scripts/test-ajax-forms.mjs
```

اختبارات المتصفح تستخدم Laravel فعليًا للـ create/upload، واعتراض استجابات داخل صفحة الاختبار فقط لمحاكاة الشبكة والحالات الأخرى، ولا تضيف routes أخطاء إلى التطبيق. تم الاختبار بالعربية والإنجليزية، بما فيه Modal مضاف ديناميكيًا وفورم طبيعي بلا الكلاس. لا تثبت اختبارات هذا الملف صلاحيات أو أقفال قواعد البيانات ولا تكمل مراجعة T03 البصرية السابقة تلقائيًا.

منع النقر المكرر حماية واجهة فقط. عمليات الحفظ الفعلية تحتاج صلاحيات ومعاملات وقيود Idempotency وتزامن على السيرفر وفق المتطلبات، خصوصًا عند فشل الشبكة بعد تنفيذ السيرفر للعملية. لا retry تلقائي، ولا جدول بيانات أو CSRF أو session authentication جديد ضمن هذه المهمة.

نتيجة 2026-10-25: 12 اختبار Laravel و89 تحققًا ناجحًا، و10 سيناريوهات Chrome ناجحة دون uncaught page errors. نجحت Blade cache وPint والمسارات وsyntax وفحص 48 مرجع أصول محلي. استُخدم Session driver=file لعملية خادم الاختبار فقط دون تعديل .env أو تشغيل migrations؛ اختبارات Laravel نفسها تستخدم إعداد phpunit.xml. إضافات Select2/Tom Select غير مثبتة ولم تُختبر فعليًا؛ اختُبر hook/واجهة بديلة صريحة بدل تثبيتها. لم تُراجع جميع صفحات ومقاسات T03 البصرية ضمن هذه المهمة.

تحديث 2026-10-27: مسارات أمثلة dashboard.examples.* غير موجودة في routes/web.php الحالي رغم بقاء ملفات العرض؛ لم تُعد ضمن موديول Languages. لذلك اختبارات أمثلة AJAX القديمة تحتاج مواءمة مع قرار المشروع بشأن هذه الصفحات. ملف AJAX المشترك لم يتغير بمهمة اللغات؛ التبديلات لها منسق صغير مستقل موثق في languages.md.

تحديث 2026-10-28: reset الطبيعي للفورم المفعّل يمسح فقط أخطاء ورسائل النظام المشترك إن لم يوجد طلب جارٍ؛ هذا يتيح إعادة استخدام نموذج تأكيد الحذف دون رسائل لسجل سابق. [نموذج الحذف المشترك](dashboard-actions.md) يستخدم نفس بروتوكول AJAX دون إضافة listeners لكل موديول.
