<?php
/**
 * Animated "Polis Bantuan UiTM" night background for the home pages.
 * Include right after <body class="nv-home-night"> and before the .nv-shell.
 *
 * Swap-in: drop a rendered Blender clip at assets/video/pb-uitm.webm (or .mp4) and
 * it is used automatically instead of the CSS scene. (assets/video is created on demand.)
 */
if (!defined('NV_NIGHT_BG_RENDERED')) {
    define('NV_NIGHT_BG_RENDERED', true);

    echo '<link rel="stylesheet" href="/assets/css/nv-animated-bg.css">';

    $nvVid = null;
    $nvType = null;
    foreach (['webm' => 'video/webm', 'mp4' => 'video/mp4'] as $ext => $mime) {
        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/assets/video/pb-uitm.' . $ext)) {
            $nvVid = '/assets/video/pb-uitm.' . $ext;
            $nvType = $mime;
            break;
        }
    }
    ?>
    <div class="nv-night-bg" aria-hidden="true">
        <?php if ($nvVid): ?>
            <video class="nv-night-video" autoplay muted loop playsinline poster="/assets/images/neon-purple-bg.jpg">
                <source src="<?= htmlspecialchars($nvVid) ?>" type="<?= htmlspecialchars($nvType) ?>">
            </video>
        <?php else: ?>
            <div class="stars"></div>
            <div class="beam blue"></div>
            <div class="beam red"></div>
            <div class="road"></div>
        <?php endif; ?>
    </div>
    <?php
}
