<?php
/** @var string|null $status */
/** @var string|null $error */
?>
<?php if (!empty($status)): ?>
    <div class="alert alert--success" role="status">
        <span class="alert__ico">{!! App\Support\Icons::render('checkCircle', 18) !!}</span>
        <div><div class="alert__text">{{ $status }}</div></div>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert--danger" role="alert">
        <span class="alert__ico">{!! App\Support\Icons::render('alert', 18) !!}</span>
        <div><div class="alert__text">{{ $error }}</div></div>
    </div>
<?php endif; ?>
