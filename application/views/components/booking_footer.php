<?php
/**
 * Local variables.
 *
 * @var bool $display_login_button
 * @var string $legal_notice_url
 * @var string $imprint_url
 */
?>

<div id="frame-footer" class="p-3 text-center border-top">
    <small class="d-block d-md-flex">
        <span class="footer-powered-by small d-block w-100 w-md-50 text-center text-md-start p-1 pe-md-0">
            <?php $footer_links = []; ?>
            <?php if ($powered_by = brand_powered_by_html()): ?>
                <?php $footer_links[] = $powered_by; ?>
            <?php endif; ?>
            <?php if (!empty($legal_notice_url)): ?>
                <?php $footer_links[] =
                    '<a href="' . e($legal_notice_url) . '" target="_blank">' . lang('legal_notice') . '</a>'; ?>
            <?php endif; ?>
            <?php if (!empty($imprint_url)): ?>
                <?php $footer_links[] =
                    '<a href="' . e($imprint_url) . '" target="_blank">' . lang('imprint') . '</a>'; ?>
            <?php endif; ?>
            <?= implode(' <span>|</span> ', $footer_links) ?>
        </span>

        <span class="footer-options d-block w-100 w-md-50 text-center text-md-end">
            <span id="select-language" class="badge bg-secondary d-inline-block my-1 my-md-0 p-1" style="min-width: 100px;">
                <i class="fas fa-language me-2"></i>
                <?= ucfirst(config('language')) ?>
            </span>
    
            <?php if ($display_login_button): ?>
                <a class="backend-link badge bg-primary text-decoration-none px-2 d-inline-block my-1 my-md-0 p-1"
                   href="<?= session('user_id') ? site_url('calendar') : site_url('login') ?>"
                   style="min-width: 120px;">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    <?= session('user_id') ? lang('backend_section') : lang('login') ?>
                </a>
            <?php endif; ?>
        </span>
    </small>
</div>
