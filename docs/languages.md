# موديول Languages وبيانات الترجمات

تم التنفيذ 2026-10-27 داخل Laravel 12.70.0 وTabler 1.6.1، مع Astrotomic v11.17.1 المثبت بالفعل. لا حزم جديدة ولا صفحات إنشاء/تعديل/حذف للغات، ولا تنفيذ للفئات أو المنتجات.

## التشغيل والصلاحيات

صفحة المدير: /dashboard/languages، واسم المسار languages.index. PATCH languages.update يعدّل active أو default فقط، مع auth وLanguagePolicy على الصفحة وقراءة JSON والتعديل. الـ Policy تستخدم users.role === 'admin' الموجود، ولا تعطي صلاحية لمجرد تسجيل الدخول. التسجيل العام معطل بالفعل في routes/auth.php، وبقي معطلًا؛ لم يُنشأ نظام أدوار جديد.

أضيف رابط محمي في Sidebar وفي تنقل Breeze حتى يصل إليه المدير من الداشبورد الحالي. أُبقي مسار dashboard وقالب Breeze كما هما. أمثلة الداشبورد القديمة ليس لها routes حاليًا؛ لم تُعد ضمن المهمة.

أُجري على قاعدة MySQL المحلية **migration اللغات فقط**، ثم LanguagesSeeder مرتين؛ النتيجة 3 سجلات دون تكرار وأساسية en. migrations الفئات لم تُشغّل. عند نشر نسخة أخرى:

~~~powershell
php artisan migrate --path=database/migrations/2026_10_27_150000_create_languages_tables.php
php artisan db:seed --class=LanguagesSeeder
~~~

لا تستخدم DatabaseSeeder العام لإعادة تهيئة هذا الموديول: يحتوي حاليًا إدخال مستخدمين ثابتين لا يخص إعداد اللغات. أُضيف استدعاء LanguagesSeeder إليه للتهيئة الشاملة الجديدة فقط، دون تغيير بيانات مستخدميه أو إعادة كتابتهم.

## البيانات والقواعد

languages يحتوي name وcode فريدًا وdirection وactive وsort_order. البداية ar ثم en ثم fr، والإضافات بترتيب >= 4 (الافتراضي 100)، ثم code عند التعادل. لا واجهة لتغيير الترتيب. Seeder يستخدم firstOrCreate ولا يعيد ضبط تفعيل اللغات أو ترتيبها أو الأساسية المخصصة عند تكراره.

العربية والإنجليزية مفعّلتان، والفرنسية موجودة معطّلة. احتُفظ بـ en أساسية أولية لأن APP_LOCALE=en إعداد حالي صريح. Laravel fallback=en وAstrotomic fallback=en وuse_fallback=false بقيت كما وجدناها؛ الأساسية تحدد اللغة الافتراضية للواجهة، وليست تغييرًا تلقائيًا لسياسة fallback.

الأساسية ممثلة بمرجع واحد غير nullable في language_settings، وليس booleans متعددة. المفتاح الفريد المولّد singleton_key يفرض صف إعداد واحد، وFK يحمي المرجع. كل تعديل يقفل هذا الصف أولًا ثم اللغة داخل Transaction مع 3 محاولات عند تعارض المعاملة. لذلك تعيين أساسية جديدة يستبدل المرجع ذريًا. لا إلغاء للأساسية وحدها، ولا تعطيلها، ولا تعيين لغة معطلة كأساسية.

استخدم UpdateLanguage للتغييرات وليس query builder مباشرًا. لا توفر الواجهة حذفًا، وتعطيل اللغة لا يحذف ترجمات بياناتها.

## الجاهزية وتبديل اللغة

LanguageReadiness يفحص قائمة config/languages.php المركزية: الاتجاه والخط، CSS Tabler للاتجاه، CSS الخط بوزني 400/600، وتوفر ملفات lang/{code}/*.php ومفاتيح مجموعات الواجهة الحالية المقابلة للإنجليزية وقيمها غير الفارغة. هذا فحص بنيوي ضروري وليس إثباتًا لجودة الترجمة أو اكتمال كل صفحات Breeze أو محتوى السجلات.

لتجهيز لغة جديدة:
1. أضف سجلها من Seeder/كود تهيئة، inactive وبترتيب مناسب.
2. جهّز مجموعات واجهة Laravel الحالية كاملة بالترجمة الصحيحة، مع مفاتيح languages وajax وui، وأي مجموعات تضيفها لاحقًا.
3. راجع اتجاهها وخطها وأصوله المحلية ثم سجّلها في config/languages.php؛ راجع الواجهة بصريًا.
4. انشر الملفات والإعدادات، ثم فعّلها من Languages. لا تضف ترجمة فرنسية تجريبية وتسميها مكتملة.
5. جهّز ترجمات بيانات الموديولات بصورة مستقلة. قد تبقى بعض السجلات بلا محتوى بهذه اللغة رغم جاهزية الواجهة.

SetLocale يستخدم اللغات active والجاهزة؛ Session غير صالحة أو معطلة تعود إلى الأساسية في الطلب التالي. اتجاه HTML وCSS من الإعداد المحفوظ. مبدّل اللغة يحفظ الصفحة والفلاتر مع قائمة مسارات رجوع مسموحة في LocaleController؛ أضف المسارات الجديدة إليها عند تنفيذ موديول جديد. لم يُغيّر fallback.

قبل تشغيل migration/seed توجد قراءة توافقية ar/en للإبقاء على الصفحات الحالية؛ إدارة اللغات نفسها ترجع 503 حتى التهيئة. تعطل اتصال قاعدة البيانات بعد النشر ليس سببًا لتجاهل إعداداتها.

## AJAX والتبديلات

تستخدم الصفحة public/js/languages.js صغيرًا خاصًا بحالة التبديلات، لأن js-ajax-form المشترك يختص بإرسال الفورمات وأزرار submit، ولا يدير checkbox optimistic state أو مزامنة صفوف الإعدادات. الملف المشترك لم يتغير.

الطلبات POST FormData مع _method=PATCH وCSRF وAccept JSON. أثناء الطلب تقفل التبديلات لتجنب سباقات داخل الصفحة. النجاح يعيد snapshot محدثة لكل الصفوف وقائمة اللغات الجاهزة؛ يُحدّث الأساسية السابقة ومبدّل اللغة فورًا دون reload. في الرفض المؤكد تُجلب الحالة من GET index بصيغة JSON قبل إعادة فتح التحكم، وتعرض رسالة validation آمنة بـ textContent.

عند فشل الشبكة/استجابة غير قابلة للتحقق أو فشل جلب الحالة لا يدعي النظام أن الحالة السابقة مؤكدة: يبقي التبديلات مقفلة ويطلب تحديث الصفحة. لا إعادة إرسال تلقائية للحفظ. النصوص في lang/ar/languages.php وlang/en/languages.php. Toggle هو checkbox أصلي قابل للوحة المفاتيح، مع label وحالة نصية لا تعتمد على اللون.

## مراجعة الـ Trait وAstrotomic

الأصل app/Http/Traits/TranslationTrait.php كان يعلن App\Traits بدل App\Http\Traits، ويستدعي Languages دون import أو Model موجود، ويجلب اللغات كل مرة. لم توجد استعمالات فعلية له في Controllers أو Models. أُصلح مكانه وnamespace مع shim للتوافق في app/Traits/TranslationTrait.php؛ بقي storeTranslates($model, $request, $fields) وسلوك عدم save التلقائي.

الطرق المتاحة:
- activeLanguages(): Collection مرتبة من اللغات المفعلة والجاهزة.
- activeLanguageCodes(): array من الأكواد بنفس الترتيب.
- translationRules(['name' => ['required','string','max:255']]): قواعد للحقول المسطحة name_ar وname_en.
- assertActiveTranslationInputs($request, ['name']): يرفض name_fr وأي suffix غير نشط/غير معروف.
- storeTranslates(): يرفض المدخلات غير النشطة قبل تعديل أي ترجمة، ثم يستخدم translateOrNew للغات المسموحة فقط؛ الحفظ مسؤولية الموديول.

LanguageCatalog scoped لكل طلب يشارك نتيجة Query واحدة للغات بين Middleware والـ Trait وAstrotomic. تعديل Language عبر Eloquent (بما فيه sort_order) يمسح نسخة الطلب، وUpdateLanguage يمسحها بعد commit. لا cache دائم يحتاج TTL؛ الطلب التالي يقرأ الحالة الجديدة. عمليات DB bulk المباشرة تتجاوز events؛ لا تستخدمها لتغيير الإعدادات أثناء الطلب دون forget صريح. لا تعدّل Collection الكتالوج المشتركة يدويًا.

Astrotomic يستخدم Locales helper للتعرف على مفاتيح اللغات في fill وللغة الحالية وبعض قراءات fallback؛ إصدارنا يربط App\Services\Languages\ActiveLocales إلى Locales وإلى translatable.locales binding بشكل scoped. load/all/has/get ترجع الكتالوج النشط، وتحدّث config('translatable.locales') عند القراءة. current locale يبقى لغة Laravel، وبقية إعدادات الحزمة كما هي. لا تعدّل vendor، ولا تعتمد على قائمة en/fr/es التجريبية الافتراضية للحزمة. استدعاء translateOrNew صراحةً يستطيع إنشاء ترجمة لأي كود؛ الحزمة ليست حاجز تحقق ضد اللغات المعطلة، لذا يلزم guard الـ Trait وForm Request وعدم تمرير مدخلات خام إليها.

**التمييز:** جدول Languages يحدد ما يمكن اختياره وإظهاره في حقول الترجمات، وLaravel lang files تترجم نصوص الواجهة. Astrotomic يدير بيانات موديلك في جدول translations بعلاقة locale. وجود لغة في الجدول لا يخلق ملفات واجهة أو يترجم أي سجل.

وجدت Category وCategoryTranslation وCategoryController كـ scaffolding غير موصول بمسارات: Category يستورد Dimsav القديم ولا يطبّق trait Astrotomic فعليًا. لم أنفذ موديول Categories أو أشغّل migrations الخاصة به. عند بدء مهمته يلزم تصحيح إعداد موديله إلى namespace Astrotomic الفعلي، وتهيئة fillable/قيود جدول ترجماته وفق متطلباته، وليس الاكتفاء بالاستيراد القديم.

## الاستخدام في أول موديول مترجم

مثال توضيحي فقط، لا ينشئ موديولًا:

~~~php
use App\Http\Traits\TranslationTrait;

// في Form Request الخاص بالموديول:
use TranslationTrait;

protected function prepareForValidation(): void
{
    $this->assertActiveTranslationInputs($this, ['name']);
}

public function rules(): array
{
    return $this->translationRules([
        'name' => ['required', 'string', 'max:255'],
    ]);
}
~~~

من Controller يستخدم نفس Trait:
~~~php
return view('module.form', ['languages' => $this->activeLanguages()]);
~~~

~~~blade
@foreach($languages as $language)
    <label for="name_{{ $language->code }}">{{ $language->name }}</label>
    <input id="name_{{ $language->code }}" name="name_{{ $language->code }}"
           dir="{{ $language->direction }}"
           value="{{ old('name_'.$language->code, $model?->translate($language->code)?->name) }}">
@endforeach
~~~

الموديل الفعلي يطبّق Astrotomic\Translatable\Contracts\Translatable ويستخدم Astrotomic\Translatable\Translatable ويحدد translatedAttributes وTranslation model المسموح حقولها. لا تمرر $request->all() إلى fill/save؛ استخدم Form Request السابق والحقول المحددة:

~~~php
DB::transaction(function () use ($model, $request) {
    $this->storeTranslates($model, $request, ['name']);
    $model->save();
});
~~~

المثال يعرض المدخلات النشطة فقط، ويتحقق منها قبل الحفظ، ويحافظ على الترجمات القديمة للغات المعطلة دون حذف. الـ Trait لا يحل محل Policies أو Form Requests أو معاملات الموديول.

## التحقق والحدود

- LanguagesTest: 10 اختبارات و61 تحققًا ناجحًا للترتيب وSeeder والقواعد والصلاحيات وSession والـ Trait وAstrotomic والـ cache وقيد صف إعداد واحد.
- Languages مع Authentication وProfile: 19 اختبارًا و90 تحققًا ناجحًا.
- MySQL 8.4.3: scripts/test-languages-concurrency.php --create-isolated نجح في 6 سباقات عبر 12 اتصالًا مستقلًا؛ يستخدم قاعدة ذات اسم عشوائي supplyflow_languages_test_* ويتحقق منه ويزيل قاعدة اختباره فقط. لا migrations على قاعدة العمل في هذا الاختبار.
- Chrome/Playwright: scripts/test-languages-ui.mjs اختبر AJAX نجاحًا ورفضًا وشبكة مجهولة، الأساسية السابقة والفرنسية وSession والمبدّل وRTL وقائمة الموبايل واحتواء عرض 390px؛ بلا أخطاء JS غير معالجة. فُحصت صور desktop/mobile، دون تثبيت حزم.
- نجحت Pint على الملفات الجديدة والمتأثرة، وتجميع Blade وsyntax وnamed routes.
- الفحص الكامل: 45 اختبارًا؛ 6 errors و6 failures تخص اختبارات أمثلة Dashboard/AJAX لمسارات محذوفة من المشروع قبل المهمة، واختبارات التسجيل العام المعطل. لم نعد هذه المسارات أو نغيّر اختبارات أخرى لإخفاء الفشل.

بيئة PHP الحالية لا تفعل PDO SQLite تلقائيًا؛ فعّلت الإضافة لعملية الاختبار فقط. داخل العزل ظهر أيضًا تعارض بين is_readable/is_writable وقدرة PHP الفعلية على القراءة، فاستخدمت bootstrap PHP المسبق ونسخة PHPUnit config مؤقتة بلا bootstrap مكرر، دون تغيير phpunit.xml. يمكن تشغيل الاختبار طبيعيًا خارج هذه المشكلة:
~~~powershell
php -d extension=pdo_sqlite vendor/bin/phpunit --filter LanguagesTest
php scripts/test-languages-concurrency.php --create-isolated
~~~

اختبار Chrome يتطلب خادمًا **بقاعدة اختبار مستقلة** مهيأة بهذه اللغات ومستخدم admin تجريبي، وPlaywright موجودًا وChrome. لا تشغله على بيانات العمل. PLAYWRIGHT_MODULE_DIR يشير لمجلد node_modules المتوفر؛ LANGUAGES_TEST_URL افتراضيًا http://127.0.0.1:8016. حساب الاختبار خاص بالـ fixture فقط ولا يُنشأ في قاعدة العمل.

تحديث 2026-10-28: فورم إنشاء الفئة أصبح يستخدم TranslationTrait::activeLanguages عبر CategoryController::create ويعرض name_ar وname_en بحسب اللغات النشطة فعليًا. هذا تعديل واجهة فقط؛ store الحالية فارغة ولا يزال ربط موديل الفئة بـ Astrotomic من العمل المتبقي، فلا يعد الحفظ متعدد اللغات منفذًا. حقول الفورم جاهزة لـ translationRules وstoreTranslates عند طلب تنفيذ الحفظ.

تحديث لاحق 2026-10-28: تم الآن تنفيذ حفظ الفئات مع CategoryRequest وموديلات Astrotomic وقفل إعدادات اللغات والتحقق من الأكواد النشطة؛ الملاحظة السابقة عن store الفارغة لم تعد تصف الحالة الحالية. راجع [دليل حفظ الفئات](categories.md).
