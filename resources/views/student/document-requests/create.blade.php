@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' input--error' : '';
$errorFor = static fn (string $field): ?string => $errors[$field] ?? null;
$types = ['Certificate of Good Moral Character', 'Certificate of Enrollment', 'Certificate of Completion', 'Transcript of Records', 'Student ID Replacement', 'Scholarship Certification', 'Organization Clearance', 'Other OSAS Document'];
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="{!! url('/student/document-requests') !!}">Document Requests</a> / New</div><h2 class="page-head__title">{{ $pageTitle }}</h2><p class="page-head__sub">{{ $pageSubtitle }}</p></div><a class="btn btn--ghost" href="{!! url('/student/document-requests') !!}">Cancel</a></div>
<?php if ($errors !== []): ?><div class="alert alert--danger"><span class="alert__ico">{!! App\Support\Icons::render('alert', 18) !!}</span><div><div class="alert__title">Please correct the highlighted fields</div><div class="alert__text">{{ reset($errors) }}</div></div></div><?php endif; ?>
<form class="card form-card" method="POST" action="{!! url('/student/document-requests') !!}">
    {!! csrf_field() !!}
    <section class="form-section"><div class="section-title">{!! App\Support\Icons::render('printer', 14) !!} Request Details</div><div class="form-grid">
        <div class="field"><label for="document_type">Document type <span class="req">*</span></label><select class="input{!! $invalid('document_type') !!}" id="document_type" name="document_type" required><option value="">Select a document</option><?php foreach ($types as $type): ?><option value="{{ $type }}"{!! ($values['document_type'] ?? '') === $type ? ' selected' : '' !!}>{{ $type }}</option><?php endforeach; ?></select><?php if ($errorFor('document_type')): ?><div class="form-error">{{ $errorFor('document_type') }}</div><?php endif; ?></div>
        <div class="field"><label for="purpose">Purpose <span class="req">*</span></label><textarea class="input{!! $invalid('purpose') !!}" id="purpose" name="purpose" rows="4" required placeholder="Explain where the document will be used.">{{ $values['purpose'] ?? '' }}</textarea><?php if ($errorFor('purpose')): ?><div class="form-error">{{ $errorFor('purpose') }}</div><?php endif; ?></div>
        <div class="form-grid form-grid--2">
            <div class="field"><label for="copies">Number of copies</label><input class="input" id="copies" name="copies" type="number" min="1" max="10" value="{{ $values['copies'] ?? 1 }}"></div>
            <div class="field"><label for="delivery_mode">Delivery mode</label><select class="input" id="delivery_mode" name="delivery_mode"><?php foreach (['Pick-up at OSAS', 'Email PDF copy', 'Courier arrangement'] as $mode): ?><option value="{{ $mode }}"{!! ($values['delivery_mode'] ?? '') === $mode ? ' selected' : '' !!}>{{ $mode }}</option><?php endforeach; ?></select></div>
        </div>
    </div></section>
    <section class="form-section"><div class="section-title">{!! App\Support\Icons::render('info', 14) !!} Processing Notes</div><p class="panel-card__text">Requests are reviewed in the order received. You will receive a portal notification whenever the status changes. Processing time is typically 2–3 working days.</p></section>
    <div class="form-actions"><a class="btn btn--ghost" href="{!! url('/student/document-requests') !!}">Cancel</a><button class="btn btn--primary" type="submit">Submit Request</button></div>
</form>

@endsection
