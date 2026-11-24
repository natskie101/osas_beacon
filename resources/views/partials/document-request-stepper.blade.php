<?php
/** @var array<string, mixed> $requestRecord */
$steps = [
    ['key' => 'Submitted', 'label' => 'Submitted', 'desc' => 'Request received'],
    ['key' => 'Under Review', 'label' => 'Under Review', 'desc' => 'Verifying record'],
    ['key' => 'Ready for Pick-up', 'label' => 'Ready for Pick-up', 'desc' => 'Document ready'],
    ['key' => 'Released', 'label' => 'Released', 'desc' => 'Claimed / Completed'],
];
$status = (string) ($requestRecord['status'] ?? 'Submitted');
$stepKeys = array_column($steps, 'key');
$currentIndex = array_search($status, $stepKeys, true);
if ($currentIndex === false) {
    $currentIndex = 0;
}
$isRejected = $status === 'Rejected';
$isCancelled = $status === 'Cancelled';
$progressPercent = $currentIndex > 0 ? round(($currentIndex / (count($steps) - 1)) * 100) : 0;
?>

<?php if ($isRejected): ?>
    <div class="dr-stepper-alert dr-stepper-alert--danger" role="alert">
        {!! App\Support\Icons::render('alert', 20) !!}
        <div>
            <strong>Document Request Rejected</strong>
            <div>This request cannot be fulfilled by OSAS. <?php if (!empty($requestRecord['remarks'])): ?>Reason: {{ $requestRecord['remarks'] }}<?php endif; ?></div>
        </div>
    </div>
<?php elseif ($isCancelled): ?>
    <div class="dr-stepper-alert dr-stepper-alert--amber" role="status">
        {!! App\Support\Icons::render('info', 20) !!}
        <div>
            <strong>Request Cancelled</strong>
            <div>This request was cancelled and will not be processed further.</div>
        </div>
    </div>
<?php else: ?>
    <section class="dr-stepper-card" aria-label="Request Progress Tracker">
        <div class="dr-stepper-head">
            <span class="dr-stepper-head__title">Processing Status</span>
            <span class="dr-stepper-head__percent">{!! $progressPercent !!}% Complete</span>
        </div>
        <div class="dr-stepper">
            <div class="dr-stepper__progress-bar" style="width: calc(({!! $progressPercent !!}% * (100% - 72px) / 100));"></div>
            <?php foreach ($steps as $idx => $step): ?>
                <?php
                $isCompleted = $idx < $currentIndex;
                $isCurrent = $idx === $currentIndex;
                $stepClass = $isCompleted ? 'is-completed' : ($isCurrent ? 'is-current' : 'is-upcoming');
                ?>
                <div class="dr-step {{ $stepClass }}">
                    <div class="dr-step__circle" aria-label="{{ $step['label'] }}">
                        <?php if ($isCompleted): ?>
                            {!! App\Support\Icons::render('check', 16) !!}
                        <?php else: ?>
                            <span>{!! $idx + 1 !!}</span>
                        <?php endif; ?>
                    </div>
                    <div class="dr-step__content">
                        <div class="dr-step__label">{{ $step['label'] }}</div>
                        <div class="dr-step__desc">{{ $step['desc'] }}</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
