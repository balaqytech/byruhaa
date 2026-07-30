<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('contact-page.eyebrow', 'تواصل معنا');
        $this->migrator->add('contact-page.page_title', 'نحن قريبون منك');
        $this->migrator->add('contact-page.intro', 'للاستفسار عن الفعاليات أو الزيارة أو مرافق المخيم، اختر وسيلة التواصل الأنسب لك.');
        $this->migrator->add('contact-page.assistant_title', 'ابدأ مع مساعد بيرحاء الذكي');
        $this->migrator->add('contact-page.assistant_description', 'يجيبك عبر واتساب عن البرامج والحجوزات والموقع، ويوجهك إلى الخطوة المناسبة في أي وقت.');
        $this->migrator->add('contact-page.assistant_button_label', 'تحدث مع المساعد الذكي');
        $this->migrator->add('contact-page.assistant_url', 'https://wa.me/96874155123?text='.rawurlencode('أرغب بالتحدث مع المساعد الذكي'));
        $this->migrator->add('contact-page.phone', '+968 7415 5123');
        $this->migrator->add('contact-page.email', null);
        $this->migrator->add('contact-page.location', 'مخيم بيرحاء إبراء، ولاية إبراء، محافظة شمال الشرقية، سلطنة عُمان');
        $this->migrator->add('contact-page.visiting_hours', 'الزيارة بموعد مسبق');
        $this->migrator->add('contact-page.map_url', null);
        $this->migrator->add('contact-page.social_heading', 'تابع بيرحاء');
        $this->migrator->add('contact-page.social_intro', 'أضف حسابات بيرحاء الرسمية من لوحة الإدارة لتظهر هنا مباشرة.');
        $this->migrator->add('contact-page.social_links', []);
        $this->migrator->add('contact-page.meta_title', 'تواصل مع بيرحاء');
        $this->migrator->add('contact-page.meta_description', 'تواصل مع مخيم بيرحاء إبراء عبر المساعد الذكي أو الهاتف، واحصل على معلومات الزيارة والبرامج.');
    }
};
