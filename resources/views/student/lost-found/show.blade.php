@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $item */
/** @var array<string, mixed>|null $myClaim */
/** @var bool $ownReport */
/** @var list<array<string, mixed>> $claims */
/** @var array<string, mixed>|null $imageFile */
/** @var array<string, string> $errors */
?><div class="page-head"><div><div class="page-head__eyebrow"><a href="{!! url('/student/lost-found') !!}">Lost and Found</a> / {{ $item['public_id'] }}</div><h2 class="page-head__title">{{ $item['title'] }}</h2><p class="page-head__sub">{{ $item['type'] }} · {{ $item['category'] }} · {{ $item['status'] }}</p></div><span class="badge {{ status_class($item['status']) }}">{{ $item['status'] }}</span></div>
<div class="detail-grid">
    <div class="detail-grid__main">
        <section class="card panel-card"><div class="panel-card__head"><div><div class="panel-title">Item Information</div><p class="panel-card__sub">Posted {{ fmt_datetime($item['posted_at']) }}</p></div></div><p class="content-lede">{!! nl2br(e($item['description'] ?: 'No description provided.')) !!}</p><?php if ($imageFile !== null): ?><div class="lf-detail-media"><img src="{!! url('/student/lost-found/' . $item['public_id'] . '/image') !!}" alt="{{ $item['title'] }} photo" loading="lazy"><div class="cell-sub">{{ $imageFile['original_name'] ?? 'Item photo' }} · <a class="link" href="{!! url('/student/lost-found/' . $item['public_id'] . '/image') !!}" target="_blank" rel="noopener">Open photo</a></div></div><?php endif; ?><div class="event-facts"><div><span>Location</span><strong>{{ $item['location'] }}</strong></div><div><span>Date</span><strong>{{ fmt_datetime($item['date_occurred']) }}</strong></div><div><span>Custody</span><strong>{{ $item['custody'] }}</strong></div></div></section>
        <?php if ($ownReport && $claims !== []): ?>
            <section class="card panel-card"><div class="panel-title">Claims on Your Report</div><div class="claim-list"><?php foreach ($claims as $claim): ?><article class="claim-card"><div class="claim-card__head"><div><strong>{{ $claim['claimant_name'] }}</strong><span>{{ $claim['claimant_email'] }} · {{ fmt_datetime($claim['created_at']) }}</span></div><span class="badge {{ status_class($claim['status']) }}">{{ $claim['status'] }}</span></div><p>{{ $claim['message'] }}</p></article><?php endforeach; ?></div></section>
        <?php elseif ($myClaim): ?>
            <section class="card panel-card">
                <div class="panel-card__head">
                    <div>
                        <div class="panel-title">Your Ownership Claim</div>
                        <p class="panel-card__sub">Submitted on {{ fmt_datetime($myClaim['created_at']) }}</p>
                    </div>
                    <span class="badge {{ status_class($myClaim['status']) }}">{{ $myClaim['status'] }}</span>
                </div>
                <?php if ($myClaim['status'] === 'Pending'): ?>
                    <div class="dr-stepper-alert dr-stepper-alert--amber" style="margin: 0 0 14px;">
                        {!! App\Support\Icons::render('clock', 18) !!}
                        <div>
                            <strong>Claim Under Review</strong>
                            <div>Your proof of ownership is being verified by OSAS coordinators. You will receive an alert once verified.</div>
                        </div>
                    </div>
                <?php elseif ($myClaim['status'] === 'Verified'): ?>
                    <div class="dr-stepper-alert dr-stepper-alert--danger" style="margin: 0 0 14px; background: var(--green-bg); border-color: var(--green-line); color: var(--green);">
                        {!! App\Support\Icons::render('checkCircle', 18) !!}
                        <div>
                            <strong>Ownership Verified! Ready for Pickup</strong>
                            <div>Please proceed to the OSAS office with your Southway College Student ID to retrieve your item.</div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="detail-note">
                    <strong>Proof Details Provided</strong>
                    <p>{!! nl2br(e($myClaim['proof'])) !!}</p>
                </div>
                <div class="detail-note" style="margin-top: 10px;">
                    <strong>Message to OSAS</strong>
                    <p>{!! nl2br(e($myClaim['message'])) !!}</p>
                </div>
            </section>
        <?php elseif (!$ownReport): ?>
            <section class="card panel-card">
                <div class="panel-card__head">
                    <div>
                        <div class="panel-title">{!! $item['type'] === 'Found' ? 'Is this your item?' : 'Do you have information?' !!}</div>
                        <p class="panel-card__sub">Submit ownership verification details to the OSAS custody desk.</p>
                    </div>
                    {!! App\Support\Icons::render('shield', 22) !!}
                </div>
                <?php if ($errors !== []): ?><div class="alert alert--danger"><div class="alert__text">{{ reset($errors) }}</div></div><?php endif; ?>
                <?php if (in_array($item['status'], ['Open', 'Unclaimed', 'Claim Pending'], true)): ?>
                    <form method="POST" action="{!! url('/student/lost-found/' . $item['public_id'] . '/claim') !!}" class="form-grid" data-confirm="Submit this ownership claim to OSAS for verification?">
                        {!! csrf_field() !!}
                        <div class="field">
                            <label for="proof">Proof of Ownership / Identifying Characteristics <span class="req">*</span></label>
                            <textarea class="input" id="proof" name="proof" rows="3" required placeholder="Specify unique markings, serial numbers, case/brand, internal contents, or lockscreen/wallpaper that only the owner would know.">{{ old('proof') }}</textarea>
                            <div class="help-text">OSAS verifies these details against the physical item in custody before release.</div>
                        </div>
                        <div class="field">
                            <label for="message">Loss Details &amp; Contact Note <span class="req">*</span></label>
                            <textarea class="input" id="message" name="message" rows="2" required placeholder="e.g. When and where you misplaced it, your phone number, or preferred pickup time.">{{ old('message') }}</textarea>
                        </div>
                        <button class="btn btn--primary" type="submit">Submit Verification Claim</button>
                    </form>
                <?php else: ?>
                    <p class="panel-card__text">This item is no longer accepting claims.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
    <aside class="detail-grid__side"><section class="card panel-card"><div class="panel-title">Item Record</div><dl class="detail-list"><div><dt>Reference</dt><dd class="mono">{{ $item['public_id'] }}</dd></div><div><dt>Type</dt><dd>{{ $item['type'] }}</dd></div><div><dt>Status</dt><dd><span class="badge {{ status_class($item['status']) }}">{{ $item['status'] }}</span></dd></div></dl></section></aside>
</div>

@endsection
