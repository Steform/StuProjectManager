<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StuProjectManager</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/favicon/favicon.png" sizes="32x32">
</head>
<body>
    <div class="container mt-4" role="main">
        <?php if (!empty($_SESSION['alertMessage'])): ?>
            <div id="main-alert" class="alert alert-<?= htmlspecialchars($_SESSION['alertType'] ?? 'info', ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show" role="alert" tabindex="-1" aria-live="polite">
                <?= htmlspecialchars($_SESSION['alertMessage'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close alert"></button>
            </div>
            <?php unset($_SESSION['alertMessage'], $_SESSION['alertType']); ?>
        <?php endif; ?>
        <?php if (isset($content)) echo $content; ?>
    </div>
    <script src="/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var alert = document.getElementById('main-alert');
        if (alert) {
            alert.focus();
            alert.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    });
    </script>
</body>
</html> 