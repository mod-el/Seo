<?php
$form = $this->model->element->getForm();
?>

<div class="flex-fields-wrap">
	<div>
		Title<br/>
		<?php $form['title']->render(); ?>
	</div>
	<div>
		Canonical<br/>
		<?php $form['canonical']->render(); ?>
	</div>
</div>

<div class="flex-fields-wrap">
	<div>
		Description<br/>
		<?php $form['description']->render(); ?>
	</div>
	<div>
		Keywords<br/>
		<?php $form['keywords']->render(); ?>
	</div>
</div>
