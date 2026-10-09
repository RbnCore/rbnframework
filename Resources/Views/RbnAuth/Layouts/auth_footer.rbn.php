        </div><!-- /.col -->
    </div><!-- /.row -->
</div><!-- /.container -->

<!-- Standard Footer Bar -->
<footer class="text-center py-3 fs-xs" style="color: #64748b; z-index: 2;">
    <?= $auth_name ?? 'RbnAuth' ?><?= ($auth_version ?? '') !== '' ? ' v' . $auth_version : '' ?> | <a href="<?= $author_url ?? '#' ?>" target="_blank" class="text-decoration-none" style="color: #94a3b8; font-weight: 500;">
        <?= $author ?? '' ?>
    </a>
</footer>

<?= $footerAssets ?? '' ?>

<!-- Dynamic Script Initialization for Auth Layout -->
<script>
    if (window.rbnProcessQueue) window.rbnProcessQueue();
</script>

</body>

</html>
