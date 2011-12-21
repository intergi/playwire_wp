<? if($video->status == 'encoding'): ?>
Video is still encoding.
<? else: ?>
<?= $video->js_embed_code ?>
<? endif ?>