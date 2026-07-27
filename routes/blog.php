<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard/blog', 'pages::blog.index')->name('blog.admin.index');
    Route::livewire('dashboard/blog/create', 'pages::blog.form')->name('blog.admin.create');
    Route::livewire('dashboard/blog/{post}/edit', 'pages::blog.form')->name('blog.admin.edit');
});
