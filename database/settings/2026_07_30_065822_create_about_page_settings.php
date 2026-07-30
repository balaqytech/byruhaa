<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('about-page.eyebrow', 'عن المنتجع');
        $this->migrator->add('about-page.page_title', 'مخيم بيرحاء إبراء');
        $this->migrator->add('about-page.hero_summary', 'مساحة تربوية وسياحية تجمع الإقامة والضيافة والتعلّم والنشاط في مكان واحد.');
        $this->migrator->add('about-page.intro_heading', 'مكانٌ يتّسع للتجربة كاملة');
        $this->migrator->add('about-page.intro_body', 'يقع مخيم بيرحاء إبراء في ولاية إبراء بمحافظة شمال الشرقية، ويمتد على مساحة تزيد على 10,000 متر مربع. صُمم المكان ليستقبل الفتيان وأسرهم والبرامج المجتمعية في بيئة هادئة وآمنة، وليجمع عناصر الرحلة من التعلّم والحركة إلى الطعام والإقامة والراحة، بروح ضيافة عُمانية أصيلة.');
        $this->migrator->add('about-page.highlights', [
            ['value' => '+10,000', 'label' => 'متر مربع'],
            ['value' => '120', 'label' => 'شخصًا في القاعة'],
            ['value' => '11', 'label' => 'غرفة إقامة'],
        ]);
        $this->migrator->add('about-page.facilities_heading', 'مرافق تخدم اليوم من أوله إلى آخره');
        $this->migrator->add('about-page.facilities', [
            ['title' => 'قاعة متعددة الاستخدام', 'description' => 'قاعة تتسع لنحو 120 شخصًا، مناسبة للبرامج واللقاءات والورش.'],
            ['title' => 'مطعم أباريق', 'description' => 'مطعم ومطبخ يقدمان الضيافة والوجبات ضمن تجربة المكان.'],
            ['title' => 'إقامة مريحة', 'description' => 'إحدى عشرة غرفة إقامة تساعد على تنفيذ المخيمات والبرامج الممتدة.'],
            ['title' => 'قهوة بيرحاء', 'description' => 'فناء هادئ للقهوة واللقاء، يخدم زوار المخيم والمشاركين.'],
            ['title' => 'حركة وترفيه', 'description' => 'ملعب متعدد الأغراض، ومسابح، وميدان أيرسوفت بإشراف، وتجربة سينما 7D.'],
            ['title' => 'مساحات خضراء', 'description' => 'حديقة تقارب مساحتها 5,000 متر مربع، تتكامل معها مزرعة جنة الطيبات على مساحة تقارب 6,000 متر مربع.'],
        ]);
        $this->migrator->add('about-page.advantages_heading', 'ما الذي يميّز بيرحاء؟');
        $this->migrator->add('about-page.advantages', [
            ['title' => 'تجربة متكاملة', 'description' => 'العبادة والعلم والعمل واللعب والنوم الصحي تجتمع في برنامج واحد ومكان واحد.'],
            ['title' => 'بيئة تربوية آمنة', 'description' => 'مساحة مناسبة للفتيان بصحبة الآباء والمربين والمعلمين، مع مراعاة الخصوصية والإشراف.'],
            ['title' => 'موقع سهل الوصول', 'description' => 'في ولاية إبراء، على مسافة تقارب ساعتين من مسقط، وعند بوابة وجهات الشرقية.'],
            ['title' => 'ضيافة عُمانية', 'description' => 'تفاصيل المكان وخدمته تنطلق من الكرم والهدوء والبساطة التي يعرفها الزائر في عُمان.'],
        ]);
        $this->migrator->add('about-page.hero_image_id', null);
        $this->migrator->add('about-page.gallery_image_ids', []);
        $this->migrator->add('about-page.gallery_heading', 'من داخل بيرحاء');
        $this->migrator->add('about-page.gallery_intro', 'لقطات حقيقية من مرافق المخيم ومساحاته، تُضاف من لوحة الإدارة.');
        $this->migrator->add('about-page.meta_title', 'عن مخيم بيرحاء إبراء');
        $this->migrator->add('about-page.meta_description', 'تعرّف إلى مخيم بيرحاء إبراء، مرافقه الممتدة على أكثر من 10,000 متر مربع، ومزاياه التربوية والسياحية.');
    }
};
