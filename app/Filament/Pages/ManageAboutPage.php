<?php

namespace App\Filament\Pages;

use App\Settings\AboutPageSettings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use Slimani\MediaManager\Form\MediaPicker;
use UnitEnum;

class ManageAboutPage extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'صفحة عن المنتجع';

    protected static ?string $title = 'إدارة صفحة عن المنتجع';

    protected static ?int $navigationSort = 80;

    protected static string $settings = AboutPageSettings::class;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.content');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('واجهة الصفحة')
                    ->description('حافظ على النص الكامل للصفحة موجزًا. لن يقبل النظام أكثر من 500 كلمة.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('eyebrow')
                            ->label('التسمية أعلى العنوان')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('page_title')
                            ->label('عنوان الصفحة')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('hero_summary')
                            ->label('المقدمة المختصرة')
                            ->required()
                            ->rows(3)
                            ->maxLength(300)
                            ->columnSpanFull(),
                        TextInput::make('intro_heading')
                            ->label('عنوان النبذة')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('intro_body')
                            ->label('نبذة المنتجع')
                            ->required()
                            ->rows(6)
                            ->maxLength(1_500)
                            ->columnSpanFull(),
                    ]),
                Section::make('الأرقام البارزة')
                    ->schema([
                        Repeater::make('highlights')
                            ->label('الأرقام')
                            ->schema([
                                TextInput::make('value')
                                    ->label('القيمة')
                                    ->required()
                                    ->maxLength(30),
                                TextInput::make('label')
                                    ->label('الوصف')
                                    ->required()
                                    ->maxLength(80),
                            ])
                            ->columns(2)
                            ->minItems(1)
                            ->maxItems(4)
                            ->reorderable()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('إضافة رقم')
                            ->columnSpanFull(),
                    ]),
                Section::make('المرافق')
                    ->schema([
                        TextInput::make('facilities_heading')
                            ->label('عنوان القسم')
                            ->required()
                            ->maxLength(120),
                        Repeater::make('facilities')
                            ->label('مرافق المخيم')
                            ->schema([
                                TextInput::make('title')
                                    ->label('اسم المرفق')
                                    ->required()
                                    ->maxLength(100),
                                Textarea::make('description')
                                    ->label('الوصف')
                                    ->required()
                                    ->rows(3)
                                    ->maxLength(320),
                            ])
                            ->minItems(1)
                            ->maxItems(10)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel('إضافة مرفق')
                            ->columnSpanFull(),
                    ]),
                Section::make('مزايا بيرحاء')
                    ->schema([
                        TextInput::make('advantages_heading')
                            ->label('عنوان القسم')
                            ->required()
                            ->maxLength(120),
                        Repeater::make('advantages')
                            ->label('المزايا')
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان الميزة')
                                    ->required()
                                    ->maxLength(100),
                                Textarea::make('description')
                                    ->label('الوصف')
                                    ->required()
                                    ->rows(3)
                                    ->maxLength(320),
                            ])
                            ->minItems(1)
                            ->maxItems(8)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel('إضافة ميزة')
                            ->columnSpanFull(),
                    ]),
                Section::make('صور المخيم')
                    ->description('استخدم صورًا حقيقية من المخيم. إذا لم تُحدد صور، تبقى الصفحة سليمة ولا تعرض صورًا بديلة غير واقعية.')
                    ->columns(2)
                    ->schema([
                        MediaPicker::make('hero_image_id')
                            ->label('الصورة الرئيسية')
                            ->acceptedFileTypes(['image/*'])
                            ->directory('site/about')
                            ->image(),
                        MediaPicker::make('gallery_image_ids')
                            ->label('صور المعرض')
                            ->acceptedFileTypes(['image/*'])
                            ->directory('site/about/gallery')
                            ->image()
                            ->multiple()
                            ->maxFiles(8)
                            ->reorderable(),
                        TextInput::make('gallery_heading')
                            ->label('عنوان المعرض')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('gallery_intro')
                            ->label('مقدمة المعرض')
                            ->required()
                            ->rows(3)
                            ->maxLength(300),
                    ]),
                Section::make('محركات البحث')
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta_title')
                            ->label(__('admin.fields.meta_title'))
                            ->required()
                            ->maxLength(60),
                        Textarea::make('meta_description')
                            ->label(__('admin.fields.meta_description'))
                            ->required()
                            ->rows(3)
                            ->maxLength(160),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->pageWordCount($data) > 500) {
            throw ValidationException::withMessages([
                'data.intro_body' => 'يجب ألا يتجاوز مجموع محتوى الصفحة 500 كلمة.',
            ]);
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function pageWordCount(array $data): int
    {
        $content = [
            $data['eyebrow'] ?? '',
            $data['page_title'] ?? '',
            $data['hero_summary'] ?? '',
            $data['intro_heading'] ?? '',
            $data['intro_body'] ?? '',
            $data['facilities_heading'] ?? '',
            $data['advantages_heading'] ?? '',
            $data['gallery_heading'] ?? '',
            $data['gallery_intro'] ?? '',
        ];

        foreach (['highlights', 'facilities', 'advantages'] as $group) {
            foreach ($data[$group] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $content = [...$content, ...array_filter($item, is_string(...))];
            }
        }

        return count(preg_split('/\s+/u', trim(implode(' ', $content)), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }
}
