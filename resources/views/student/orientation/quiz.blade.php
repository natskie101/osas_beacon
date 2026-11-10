@extends('layouts.app')
@section('content')
<?php
/** @var array<string, mixed> $module */
/** @var array<string, mixed> $quiz */
/** @var list<array<string, mixed>> $questions */
/** @var array<string, mixed> $attempt */
$total = count($questions);
?>
<div class="or-quiz">
    <div class="or-quiz__top">
        <div class="or-quiz__badges">
            <span class="or-code">{{ $module['public_id'] }}</span>
            <span class="or-quiz__tag">ASSESSMENT</span>
            <span class="or-quiz__pass">PASSING SCORE REQUIRED: {!! number_format((float) $module['passing_rate'], 0) !!}%</span>
        </div>
        <span class="or-quiz__timer" data-quiz-countdown data-expires="{{ date(DATE_ATOM, strtotime((string) $attempt['expires_at'])) }}">20:00 Left</span>
    </div>

    <h1 class="or-quiz__title">{{ $module['title'] }}</h1>
    <div class="or-quiz__counter">Question <span data-quiz-current>1</span> of {!! number_format($total) !!} | Single Choice</div>
    <div class="or-quiz__progress"><span data-quiz-progress style="width: {!! $total > 0 ? round(100 / $total) : 0 !!}%"></span></div>

    <form method="POST" action="{!! url('/student/orientation/' . $module['public_id'] . '/quiz/submit') !!}" id="quiz-form" data-quiz-wizard data-total="{!! $total !!}">
        {!! csrf_field() !!}
        <?php foreach ($questions as $index => $question): ?>
            <section class="or-quiz__step{!! $index === 0 ? ' or-quiz__step--on' : '' !!}" data-step="{!! $index !!}">
                <div class="or-section-label">Question</div>
                <div class="or-quiz__prompt">{{ $question['prompt'] }}</div>
                <div class="or-quiz__options">
                    <?php foreach ($question['options'] as $optionIndex => $option): ?>
                        <label class="or-quiz__option">
                            <input type="radio" name="answers[{!! (int) $question['id'] !!}]" value="{!! (int) $option['id'] !!}">
                            <span class="or-quiz__letter">{!! chr(65 + $optionIndex) !!}.</span>
                            <span>{{ $option['label'] }}</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="or-quiz__controls">
            <button type="button" class="btn btn--ghost" data-quiz-prev disabled>&larr; Prev</button>
            <button type="button" class="btn or-btn-next" data-quiz-next>NEXT QUESTION &rarr;</button>
        </div>
        <button type="submit" class="btn or-btn-submit">SUBMIT ANSWER</button>
    </form>
</div>

@endsection
