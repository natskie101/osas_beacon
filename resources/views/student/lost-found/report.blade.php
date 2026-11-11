@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="{!! url('/student/lost-found') !!}">Lost and Found</a> / Report</div><h2 class="page-head__title">{{ $pageTitle }}</h2><p class="page-head__sub">{{ $pageSubtitle }}</p></div><a class="btn btn--ghost" href="{!! url('/student/lost-found') !!}">Cancel</a></div>
<?php if ($errors !== []): ?><div class="alert alert--danger"><span class="alert__ico">{!! App\Support\Icons::render('alert', 18) !!}</span><div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text">{{ reset($errors) }}</div></div></div><?php endif; ?>
<form class="card form-card" method="POST" action="{!! url('/student/lost-found/report') !!}" enctype="multipart/form-data">
    {!! csrf_field() !!}
    <section class="form-section"><div class="section-title">{!! App\Support\Icons::render('search', 14) !!} Item Report</div>
        <div class="form-grid">
            <div class="form-grid form-grid--2">
                <div class="field"><label for="type">What are you reporting?</label><select class="input" id="type" name="type"><?php foreach (['Lost', 'Found'] as $type): ?><option value="{{ $type }}"{!! ($values['type'] ?? '') === $type ? ' selected' : '' !!}>{{ $type }}</option><?php endforeach; ?></select></div>
                <div class="field"><label for="category">Category</label><input class="input" id="category" name="category" value="{{ $values['category'] ?? '' }}" placeholder="e.g. Electronics" autocomplete="off" required></div>
            </div>
            <div class="field"><label for="title">Item title <span class="req">*</span></label><input class="input{!! $invalid('title') !!}" id="title" name="title" value="{{ $values['title'] ?? '' }}" placeholder="e.g. Black Jansport backpack" required><?php if ($errorFor('title')): ?><div class="form-error">{{ $errorFor('title') }}</div><?php endif; ?></div>
            <div class="field"><label for="description">Description <span class="req">*</span></label><textarea class="input" id="description" name="description" rows="5" required placeholder="e.g. Black Jansport backpack with a blue water bottle, left at the library 2nd floor around 4 PM.">{{ $values['description'] ?? '' }}</textarea><?php if ($errorFor('description')): ?><div class="form-error">{{ $errorFor('description') }}</div><?php endif; ?><div class="help-text">Describe the item without revealing every detail if it is a found item.</div></div>
            <div class="field">
                <label for="image">Item photo</label>
                <label class="upload-drop" for="image">
                    <span class="upload-drop__icon">{!! App\Support\Icons::render('upload', 24) !!}</span>
                    <span data-upload-prompt><strong>Click to upload an item photo</strong> or drag and drop</span>
                    <small data-upload-prompt>JPG or PNG &middot; up to 5 MB</small>
                    <span class="upload-drop__filename" data-upload-filename aria-live="polite" hidden></span>
                </label>
                <input class="upload-drop__input" id="image" name="image" type="file" accept="image/jpeg,image/png">
                <?php if ($errorFor('image')): ?><div class="form-error">{{ $errorFor('image') }}</div><?php endif; ?>
            </div>
            <div class="form-grid form-grid--2">
                <div class="field"><label for="location">Location lost or found</label><input class="input" id="location" name="location" value="{{ $values['location'] ?? '' }}" placeholder="e.g. Library 2nd floor" required></div>
                <div class="field"><label for="date_occurred">Date and time</label><input class="input" id="date_occurred" name="date_occurred" type="datetime-local" value="{{ $values['date_occurred'] ?? '' }}"></div>
            </div>
            <div class="field"><label for="contact">Contact details</label><input class="input" id="contact" name="contact" value="{{ $values['contact'] ?? '' }}" placeholder="name@uc.edu.ph or +63 9XX XXX XXXX" required><div class="help-text">Your email or phone number so OSAS can reach you.</div></div>
        </div>
    </section>
    <div class="form-actions"><a class="btn btn--ghost" href="{!! url('/student/lost-found') !!}">Cancel</a><button class="btn btn--primary" type="submit">Submit Report</button></div>
</form>

@endsection
