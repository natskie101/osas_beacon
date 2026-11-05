<?php
/** @var array<string, mixed>|null $question */
/** @var string $prefix */
/** @var string $fieldPrefix */
$fieldPrefix = $fieldPrefix ?? '';
$options = $question['options'] ?? [];
$correctIndex = null;

foreach ($options as $index => $option) {
    if ((int) ($option['is_correct'] ?? 0) === 1) {
        $correctIndex = $index;
        break;
    }
}

$name = static fn (string $field): string => $fieldPrefix === '' ? $field : $fieldPrefix . '[' . $field . ']';
$optionName = $fieldPrefix === '' ? 'options[]' : $fieldPrefix . '[options][]';
$correctName = $fieldPrefix === '' ? 'correct' : $fieldPrefix . '[correct]';
?>
<div class="field">
    <label for="<?= e($prefix) ?>-prompt">Question <span class="req">*</span></label>
    <textarea class="input" id="<?= e($prefix) ?>-prompt" name="<?= e($name('prompt')) ?>" rows="2" required><?= e($question['prompt'] ?? '') ?></textarea>
</div>
<div class="field">
    <label for="<?= e($prefix) ?>-points">Points</label>
    <input class="input" id="<?= e($prefix) ?>-points" name="<?= e($name('points')) ?>" type="number" min="1" max="100" value="<?= e($question['points'] ?? 1) ?>">
</div>
<div class="quiz-options-editor">
    <?php for ($i = 0; $i < 6; $i++): ?>
        <div class="quiz-option-row">
            <label class="checkbox">
                <input type="radio" name="<?= e($correctName) ?>" value="<?= $i ?>"<?= $correctIndex === $i ? ' checked' : '' ?>>
                <span>Correct</span>
            </label>
            <input class="input" name="<?= e($optionName) ?>" value="<?= e($options[$i]['label'] ?? '') ?>" placeholder="Option <?= $i + 1 ?>">
        </div>
    <?php endfor; ?>
</div>
