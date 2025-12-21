<?php

namespace App\Filament\Resources\Stories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('عنوان')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('عنوان داستان را وارد کنید'),
                TextInput::make('author')
                    ->label('نویسنده')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('نام نویسنده را وارد کنید'),
                Textarea::make('description')
                    ->label('توضیحات')
                    ->rows(3)
                    ->placeholder('توضیحات داستان را وارد کنید'),
                Select::make('category_id')
                    ->label('دسته‌بندی')
                    ->options(Category::all()->pluck('title', 'id'))
                    ->required()
                    ->placeholder('دسته‌بندی را انتخاب کنید'),
                FileUpload::make('image_file_name')
                    ->label('تصویر')
                    ->image()
                    ->disk('public')
                    ->directory('stories/images')
                    ->visibility('public')
                    ->helperText('تصویر کاور داستان را آپلود کنید'),
                FileUpload::make('voice_file_name')
                    ->label('فایل صوتی')
                    ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'])
                    ->disk('public')
                    ->directory('stories/voices')
                    ->visibility('public')
                    ->maxSize(50 * 1024) // 50MB
                    ->helperText('فایل صوتی داستان را آپلود کنید (حداکثر 50MB)'),
                TextInput::make('rate')
                    ->label('امتیاز')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(5)
                    ->default(0)
                    ->helperText('امتیاز بین 0 تا 5'),
                TextInput::make('total_rates')
                    ->label('تعداد امتیازها')
                    ->numeric()
                    ->default(1)
                    ->helperText('تعداد کل امتیازهای ثبت شده'),
                Toggle::make('is_free')
                    ->label('استوری رایگان')
                    ->default(false)
                    ->helperText('اگر فعال باشد، این استوری برای همه کاربران رایگان خواهد بود')
                    ->inline(false),
            ]);
    }
}
