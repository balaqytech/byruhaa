<?php

namespace App\Filament\Pages;

use App\Settings\ContactPageSettings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageContactPage extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'صفحة تواصل معنا';

    protected static ?string $title = 'إدارة صفحة تواصل معنا';

    protected static ?int $navigationSort = 81;

    protected static string $settings = ContactPageSettings::class;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.content');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('واجهة الصفحة')
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
                        Textarea::make('intro')
                            ->label('مقدمة الصفحة')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
                Section::make('المساعد الذكي')
                    ->description('يوجّه هذا القسم الزائر إلى مساعد بيرحاء عبر واتساب أو أي رابط بديل تختاره.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('assistant_title')
                            ->label('العنوان')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('assistant_button_label')
                            ->label('نص الزر')
                            ->required()
                            ->maxLength(80),
                        Textarea::make('assistant_description')
                            ->label('الوصف')
                            ->required()
                            ->rows(3)
                            ->maxLength(400)
                            ->columnSpanFull(),
                        TextInput::make('assistant_url')
                            ->label('رابط المساعد')
                            ->required()
                            ->rules(['url:http,https'])
                            ->maxLength(2_000)
                            ->columnSpanFull(),
                    ]),
                Section::make('بيانات التواصل والزيارة')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->required()
                            ->tel()
                            ->maxLength(40),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('location')
                            ->label('العنوان')
                            ->required()
                            ->rows(3)
                            ->maxLength(350),
                        Textarea::make('visiting_hours')
                            ->label('مواعيد أو تعليمات الزيارة')
                            ->required()
                            ->rows(3)
                            ->maxLength(350),
                        TextInput::make('map_url')
                            ->label('رابط الخريطة')
                            ->rules(['nullable', 'url:http,https'])
                            ->maxLength(2_000)
                            ->columnSpanFull(),
                    ]),
                Section::make('حسابات التواصل الاجتماعي')
                    ->schema([
                        TextInput::make('social_heading')
                            ->label('عنوان القسم')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('social_intro')
                            ->label('مقدمة القسم')
                            ->required()
                            ->rows(3)
                            ->maxLength(300),
                        Repeater::make('social_links')
                            ->label('الحسابات')
                            ->schema([
                                Select::make('platform')
                                    ->label('المنصة')
                                    ->options([
                                        'instagram' => 'إنستغرام',
                                        'x' => 'إكس',
                                        'facebook' => 'فيسبوك',
                                        'youtube' => 'يوتيوب',
                                        'tiktok' => 'تيك توك',
                                        'snapchat' => 'سناب شات',
                                        'linkedin' => 'لينكدإن',
                                    ])
                                    ->required(),
                                TextInput::make('url')
                                    ->label('الرابط')
                                    ->required()
                                    ->rules(['url:http,https'])
                                    ->maxLength(2_000),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->maxItems(10)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => match ($state['platform'] ?? null) {
                                'instagram' => 'إنستغرام',
                                'x' => 'إكس',
                                'facebook' => 'فيسبوك',
                                'youtube' => 'يوتيوب',
                                'tiktok' => 'تيك توك',
                                'snapchat' => 'سناب شات',
                                'linkedin' => 'لينكدإن',
                                default => null,
                            })
                            ->addActionLabel('إضافة حساب')
                            ->columnSpanFull(),
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
}
