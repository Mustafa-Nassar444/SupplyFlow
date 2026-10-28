# أساس واجهة SupplyFlow

التجهيز يخص T03 فقط: قالب Blade وصفحات عرض دون Models أو migrations أو Authentication أو عمليات أعمال. صفحة `/` القديمة محفوظة، والصفحات الجديدة عامة حاليًا؛ تطبق صلاحيات السيرفر عند تنفيذ T02 وقبل إضافة بيانات فعلية.

## المصدر والأصول

Tabler **1.6.1** من `tablerViews/tabler--tabler-core-1.6.1` والأرشيف الأصلي محفوظ. الأرشيف مصدر فقط. `scripts/build-tabler.mjs` ينسخ المصدر إلى مجلد عمل متجاهل ثم يشغّل سكربت نسخته `.build/build-css.ts` مع RTL والتصغير والبنر، وVite عبر إعداد النسخة `core/.build/vite.config.mts`. لا تعديلات على المصدر الأصلي.

نشر الملفات المطلوبة فقط في `public/vendor/tabler`: CSS LTR وRTL وخرائطهما وJavaScript UMD وخريطته وتراخيص Tabler وBootstrap وPopper والخطوط. Bootstrap وPopper مضمنان في الحزمة؛ لا تحميل منفصل لهما. لا CDN أو jQuery أو DataTables. إضافات Tabler الاختيارية لا تُنسخ كحزم منفصلة.

الخطوط محلية Inter وNoto Sans Arabic بوزني 400 و600 من حزم Fontsource. أيقونات SVG مختارة من `@tabler/icons` تُنسخ كـ partials محلية؛ الإصدار الدقيق **3.49.0** وترخيصه محفوظ. تستخدم الأيقونات `currentColor` وليست خطًا خارجيًا. بقيت حزم Vite/Tailwind القديمة وصفحة welcome كما هي؛ لا تحمل على قالب الداشبورد.

حزم البناء المعزولة وإصداراتها الدقيقة في `.tools/tabler-build/package.json` وملف lock الخاص به؛ لا حزم PHP جديدة ولا تعديل لاعتماديات التطبيق. تحديث هذه الحزم قرار منفصل. `public/css/dashboard.css` يضبط الخط فقط وقيم LTR وشكل جدول المثال على الموبايل دون إعادة تصميم Tabler.

## التشغيل والبناء

الأصول المنشورة موجودة بالفعل، لذا لا تحتاج Vite لمشاهدة صفحات الداشبورد:

```powershell
php artisan serve
```

الجلسات في البيئة الحالية تستخدم database؛ يلزم توفر اتصال MySQL وجداول الجلسات القائمة. لم تنفذ migrations أو تُغيّر `.env` في T03. لم يُختبر اتصال قاعدة البيانات هنا؛ T01 ما زال مفتوحًا. يمكن لمعاينة محلية مؤقتة فقط تشغيل جلسات ملفات دون تغيير `.env`:

```powershell
$env:SESSION_DRIVER = 'file'
php artisan serve
```

هذه قيمة لعملية الطرفية وليست قرار تغيير إعداد المشروع. افتح `/dashboard` أو `/dashboard/examples` أو `/dashboard/examples/form`. لا توجد حسابات دخول أو سجلات فعلية.

لإعادة إنتاج الأصول، بعد توفر أدوات البناء:

```powershell
npm ci --prefix .tools/tabler-build
npm run build:tabler
```

العملية الثانية نجحت في هذه المهمة؛ ملفات lock تثبت الإصدارات. أدوات البناء تحتاج Node متوافقًا مع قيود الحزم (نجح البناء على Node 22.14.0). لا حاجة لـ `npm run build` الخاص بـ Vite/Tailwind القديم لهذه الصفحات.

## التنظيم وإضافة صفحة

- `layouts/dashboard.blade.php`: الهيكل و`title` و`content` وstacks باسم `styles` و`scripts`.
- `partials/dashboard`: head وsidebar وnavbar وfooter وscripts وأيقونات SVG.
- `components/dashboard`: anonymous components: page-header وbreadcrumb وalert وempty-state، بخصائص بسيطة ونص escaped.
- `dashboard/index.blade.php`: إحصاءات عرض وتنبيه وحالة فراغ.
- `dashboard/examples/index.blade.php`: جدول ثابت، بحث وفلاتر معطلة بوضوح، وتفاصيل داخل Modal تجريبي؛ يتحول الجدول لكروت على الموبايل.
- `dashboard/examples/form.blade.php`: Create وEdit تجريبيان إلى DashboardExampleController مع Form Requests دون حفظ بيانات، باستخدام النظام المشترك `public/js/ajax-forms.js` المحمل من scripts partial. راجع [دليل AJAX](ajax-forms.md) للإعدادات والأحداث والتعامل مع الملفات؛ سكربت dashboard-example.js القديم لم يعد محملًا.
- `lang/ar/ui.php` و`lang/en/ui.php`: النصوص المشتركة؛ أضف نفس المفاتيح في اللغتين واستدعها بـ `__('ui.key')`.

مثال صفحة جديدة عند طلب تنفيذها:

```blade
@extends('layouts.dashboard')
@section('title', __('ui.list'))
@section('content')
    <x-dashboard.page-header :title="__('ui.list')" />
    {{-- محتوى الصفحة --}}
@endsection
@push('scripts')
    <script src="{{ asset('js/my-page.js') }}" defer></script>
@endpush
```

يمكن إضافة CSS خاص عبر `@push('styles')` مع `<link>` محلي. لا تعِد تحميل Tabler أو Bootstrap داخل الصفحات. اضبط APP_NAME في إعداد البيئة إلى اسم التطبيق المطلوب؛ عنوان المتصفح والعلامة يستخدمان `config('app.name')` دون تعديل البيئة تلقائيًا.

أضف named route ثم رابطًا في sidebar؛ استخدم `request()->routeIs('module.*')` للمجموعة و`routeIs('module.index')` للرابط. المجموعة الحالية `dashboard.examples.*` مثال قابل للتوسعة. مكان إضافة `@can` موضح بتعليق Blade، لكن إخفاء الرابط لا يغني عن Policies وصلاحيات السيرفر. لا تضف روابط لموديولات غير منفذة.

لبدء أول موديول لاحقًا: اقرأ متطلباته واعتمادياته، أضف مساراته وصلاحياته وController وForm Request عند الحاجة، ثم صفحة تمتد من القالب ونصوصها باللغتين ورابطها الحقيقي فقط. لا Business Logic داخل layout، ولا تعتبر الصفحات التجريبية أساسًا لحفظ بيانات فعلية.

## اللغة والتحقق

SetLocale يعمل بعد بدء Session ويقبل ar/en فقط؛ القيمة غير الصحيحة تعود للغة الإعداد إن كانت مدعومة وإلا en. زر اللغة يرسل POST مع CSRF ويحفظ الاختيار، ويعيد إلى صفحة مسموحة من صفحات المعاينة مع query string؛ لا يقبل redirect خارجيًا. عند إضافة صفحة لاحقًا حدّث قائمة المسارات المسموحة في مسار `dashboard.locale` واختبارها، أو استبدلها بتنظيم مسارات موسع ضمن المهمة الجديدة. حفظ اللغة للمستخدم في قاعدة البيانات مؤجل إلى T02.

HTML يضبط lang وdir ويحمّل CSS المناسب وحده. البريد وSKU والمراجع تستخدم `<bdi dir="ltr">` أو `dir="ltr"` داخل الحقول. أسماء اللغات معروضة بلغاتها الأصلية دائمًا.

التحقق المنفذ: بناء Tabler المحلي، فحص جميع مسارات الداشبورد وتجميع Blade، اختبارات الصفحات واللغة والفلاتر ومنع redirect الخارجي والتحقق التجريبي والرسائل، Pint، وفحص syntax للـ JavaScript وروابط أصول CSS المحلية.

لم يتوفر متصفح متصل لأداة التحكم؛ فشل فتح متصفح المعاينة برسالة `Browser is not available: iab`. لذلك لم تُثبت بصريًا مقاسات 1440/768/390 أو عمل Sidebar/Dropdown/Modal أو خلو Console وقت التشغيل. هذه مراجعة متبقية قبل اعتبار T03 Done؛ الحالة In progress في tasks.md. بقية الموديولات لم تبدأ.

نتيجة الجولة النهائية: 7 اختبارات و57 تحققًا ناجحًا، Pint ناجح، وتجميع Blade ناجح؛ فحص الأصول تحقق من 48 مرجع CSS محلي بلا ملفات مفقودة أو عناوين خارجية. فحص syntax لحزمة Tabler وJavaScript المثال ناجح. هذه النتائج لا تعوض اختبار JavaScript في متصفح فعلي.

تحديث 2026-10-27: مبدّل اللغة الآن من LanguageCatalog للغات active والجاهزة، ويدعم اتجاهها وخطها من الإعداد المركزي؛ قائمة ar/en الثابتة استُبدلت بموديول [Languages](languages.md). أبقينا dashboard الحالي من Breeze، وأضفنا رابط الإعدادات للمدير فقط. أزيل navbar-expand-lg من Navbar العلوي غير القابل للطي، لأن Tabler 1.6.1 كان يخفي Sidebar عند وجود شريط أفقي موسع؛ هذه معالجة لازمة لإظهار رابط الموديول، مع تحقق Chrome من القائمة على الكمبيوتر والموبايل. بقية مراجعة T03 الشاملة لم تُغلق تلقائيًا.

تحديث 2026-10-28: تم اعتماد dashboard.index المبني بـ Tabler كداشبورد واحدة على /dashboard مع توجيه / إليها. نموذج الحذف المشترك مشمول مرة واحدة في layout. راجع [دليل الإجراءات](dashboard-actions.md) بدل الاعتماد على الملاحظات التاريخية عن إبقاء داشبورد Breeze.
