<section dir="rtl" lang="ar">
    <h2>الشروط والأحكام الخاصة بفعالية {{ $event->name }}</h2>
    <div>{!! $event->contract_terms_html ?: '<p>لا توجد شروط إضافية لهذه الفعالية.</p>' !!}</div>
</section>
