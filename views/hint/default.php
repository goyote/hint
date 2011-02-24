<ul class="messages">
	<?php foreach ($messages as $message): ?>
		<li class="<?php echo $message['type'] ?>">
			<p><?php echo $message['text'] ?></p>
		</li>
	<?php endforeach ?>
</ul>
