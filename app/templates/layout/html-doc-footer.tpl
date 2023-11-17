<?php if (!ENV_PRODUCTION): ?>
<aside style="background-color:#f7dec3; color:#333; display:inline-block; padding:0.3em 0.6em; position:fixed; bottom:2%; right:2%; z-index:999;">
<?php echo 'Processing-Time: <b>'.round((microtime(true)-APP_START)*1000,2) . '</b>ms' ;?>
</aside>
<?php endif; ?>

<?php if (!empty($page['js'])):?>
<?php foreach ($page['js'] as $js): ?>
<script type="text/javascript" src="<?=$js?>"></script>

<?php endforeach ?>
<?php endif ?>
</body>
</html>