# wp-lab — بناء واختبار WordPress خارج السيرفر

مختبر خفيف يبني ويشغّل ويختبر مواقع WordPress **بدون استهلاك موارد VPS** (952MB RAM).
كل شيء يعمل في: متصفحك · جهازك · GitHub (Actions + Codespaces) — بتكلفة €0.

## لماذا؟
- سيرفر Hermes مخصص للتداول والأتمتة — لا نُثقله بـ PHP/MySQL لمواقع كاملة.
- WordPress Playground يشغّل WordPress كاملاً (PHP WASM + SQLite) بلا Docker/MySQL/Apache.
- نفس البيئة تعمل في ثلاث أماكن: المتصفح، جهازك، CI — نتيجة واحدة موثوقة.

## ثلاث طرق للتشغيل

### 1) المتصفح — صفر تثبيت
- افتح https://playground.wordpress.net
- اسحب أي ZIP (ثيم/بلوجن) وأفلته في الصفحة → يتركب فوراً
- أو افتح هذا الرابط لتجربة ثيم هذا المستودع مباشرة في متصفحك:

  https://playground.wordpress.net/?blueprint-url=https://cdn.jsdelivr.net/gh/shzakx-ai/wp-lab@main/blueprint-browser.json

- "Download" في القائمة يحفظ الموقع كامل كـ ZIP (snapshot) للتجريب لاحقاً.

### 2) جهازك المحلي — أمر واحد (يتطلب Node 20.18+)
```bash
# تشغيل يومي: يكتشف المشروع تلقائياً، يحفظ الموقع، ويفتح المتصفح
cd wp-content/themes/lab-theme
npx @wp-playground/cli@latest start
```

أو تشغيل كامل مع Blueprint ومَنت القالب (كما يفعل CI):
```bash
bash scripts/dev.sh            # المنفذ الافتراضي 9400
```

### 3) GitHub Codespaces — جهاز سحابي مجاني (2 core / 8GB)
- من صفحة المستودع: Code → Codespaces → Create codespace
- المجاني: 120 ساعة-نواة/شهر (60 ساعة على جهاز نواتين) + 15GB تخزين
- هذا المستودع يجهّز الـ Codespace تلقائياً (devcontainer): الشبكة + المنفذ 9400 + تشغيل السيرفر

### تطبيق سطح المكتب (اختياري)
WordPress Studio (مجاني، Mac/Win/Linux): https://developer.wordpress.com/studio/ — مواقع محلية بضغطة، WP-CLI مدمج، وتصدير ZIP.

## خط الأنابيب CI (GitHub Actions)
يعمل عند: كل push على main · كل PR · يدوياً · يومياً 03:00 UTC (فحص مع أحدث WordPress).

1. **php -l** لكل ملفات PHP في المستودع
2. **حارس أمان**: يمنع دوال PHP الخطرة (eval/base64_decode/shell_exec/passthru/proc_open/popen)
3. **إقلاع WordPress كامل** عبر Playground CLI: PHP WASM + SQLite + مَنت القالب + تنفيذ blueprint.json
4. **فحوص Smoke عبر HTTP** (وليس curl شكلي):
   - ينتظر الجاهزية الحقيقية (سطر `Ready!` في السجل) — المنفذ يُربط قبل انتهاء الإقلاع فيردّ 302/502 أثناءه
   - يفحص بكوكيز (cookie jar): بلجن auto-login الرسمي يردّ `302 + Set-Cookie` على كل طلب لا يحمل كوكي `playground_auto_login_already_happened` — أي فحص بلا cookie jar يدور في حلقة لا نهائية (curl exit 47)
   - الرئيسية HTTP 200
   - علامة القالب `wp-lab-smoke-marker` ظاهرة → القالب فعلاً هو الذي يرندر
   - اسم الموقع من الـ blueprint ظاهر → الخطوات نُفذت فعلاً
5. **مخرجات (Artifacts)**: `lab-theme.zip` جاهز للتركيب + `home.png` لقطة بصرية + `playground.log`

## حلقة التطوير المستمرة (الموديل)
```
النموذج يولّد/يعدّل الكود  →  push (فرع/PR)
        ↑                        ↓
  brain + سجل النتائج  ←  CI بوّابة: FAIL = لا دمج
        ↑                        ↓
   تجربة بصرية (لقطة/Snapshot)  ←  Artifacts ترجع للتجريب
```
- **بوّابة صلبة**: لا شيء يُدمج إذا فشل CI.
- **معرفة رسمية للنموذج** — مهارات WordPress الرسمية للوكلاء (16 مهارة: Gutenberg، ثيمات، إضافات، Performance، Playground):
```bash
npx skills add WordPress/agent-skills --skill wp-playground blueprint wp-plugin-development wp-block-development wp-performance
```
- مصادر المسار الكامل: https://github.com/WordPress/agent-skills

## دروس تشغيلية (Playground CLI v3) — مُختبرة فعلياً في CI
- **لا تستخدم خطوة `wp-cli` في CI**: تنزّل `wp-cli.phar` من الشبكة وقت التنفيذ ويفشل التنزيل على عدّاءات GitHub (`ResourceDownloadError`) فيتوقف الإقلاع. البديل: خطوة `runPHP` مع دوال WordPress مباشرة (`wp_insert_post` ...).
- **انتظر `Ready!` لا المنفذ**: الـ CLI يبدأ الاستماع على المنفذ بعد ~15 ثانية بينما الإقلاع وخطوات الـ blueprint لا تزال جارية — أي فحص سطحي يمرّ كذباً.
- **افحص دائماً بـ cookie jar** (`curl -c jar -b jar`) لأن auto-login يعيد التوجيه حتى تُحفظ الكوكيز.
- **مجلد كاش الـ CLI**: `~/.wordpress-playground` — نُخزّنه بين تشغيلات CI لتسريع الإقلاع.

## البنية
```
wp-lab/
├── blueprint.json            # وصف البيئة (CI + محلي): تفعيل القالب + تحقّق + صفحة اختبار
├── blueprint-browser.json    # نسخة المتصفح: تثبيت القالب من المستودع مباشرة
├── scripts/dev.sh            # تشغيل محلي/Codespaces بأمر واحد
├── .github/workflows/wp-ci.yml
└── wp-content/themes/lab-theme/   # قالب البداية (كل موقع جديد = مجلد هنا أو مستودع منفصل)
```

## الأدوات الرسمية (2026)
- Playground CLI v3.x: `start` · `server` · `run-blueprint` · `build-snapshot`
- لا يتطلب: Docker/MySQL/Apache — فقط Node 20.18+
- ترقية WordPress/PHP اختبارية: `--wp=6.8 --php=8.4`
