<dialog class="orientation-notify" data-orientation-notify-modal aria-labelledby="orientation-notify-title">
    <form class="orientation-notify__form" method="POST" action="#">
        <?= csrf_field() ?>
        <input type="hidden" name="return_to" value="list" data-notify-return>
        <div class="orientation-notify__head">
            <div>
                <h2 id="orientation-notify-title">Notify Student</h2>
                <p>Send this orientation progress update to <strong data-notify-recipient></strong> <span data-notify-audience>in the student portal</span>.</p>
            </div>
            <button class="orientation-notify__close" type="button" data-notify-close aria-label="Close notification dialog">&times;</button>
        </div>
        <div class="field">
            <label for="orientation-notify-message">Message</label>
            <textarea class="input" id="orientation-notify-message" name="message" rows="6" maxlength="1000" required data-notify-message></textarea>
            <div class="help-text">The student will receive this in their portal notifications.</div>
        </div>
        <div class="form-actions">
            <button class="btn btn--ghost" type="button" data-notify-close>Cancel</button>
            <button class="btn btn--primary" type="submit"><?= App\Support\Icons::render('send', 14) ?> Send Notification</button>
        </div>
    </form>
</dialog>
